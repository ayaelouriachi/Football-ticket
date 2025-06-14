<?php
/**
 * Fonctions de validation pour les données des matches
 */

/**
 * Valide les données d'un match
 * @param array $data Les données à valider
 * @return array Tableau des erreurs (vide si aucune erreur)
 */
function validateMatchData($data) {
    $errors = [];

    // Validation du titre
    if (!isset($data['title']) || empty(trim($data['title']))) {
        $errors['title'] = "Le titre du match est obligatoire";
    }

    // Validation des équipes
    if (!isset($data['team1_id']) || empty($data['team1_id'])) {
        $errors['team1_id'] = "L'équipe à domicile est obligatoire";
    }
    if (!isset($data['team2_id']) || empty($data['team2_id'])) {
        $errors['team2_id'] = "L'équipe visiteuse est obligatoire";
    }
    if (isset($data['team1_id']) && isset($data['team2_id']) && 
        !empty($data['team1_id']) && !empty($data['team2_id']) && 
        $data['team1_id'] === $data['team2_id']) {
        $errors['teams'] = "Les deux équipes doivent être différentes";
    }

    // Validation du stade
    if (!isset($data['stadium_id']) || empty($data['stadium_id'])) {
        $errors['stadium_id'] = "Le stade est obligatoire";
    }

    // Validation de la date et l'heure
    $currentDate = date('Y-m-d');
    if (!isset($data['match_date']) || empty($data['match_date'])) {
        $errors['match_date'] = "La date du match est obligatoire";
    } elseif (!isValidDate($data['match_date'])) {
        $errors['match_date'] = "La date du match n'est pas valide";
    } elseif ($data['match_date'] < $currentDate) {
        $errors['match_date'] = "La date du match doit être future";
    }

    if (!isset($data['match_time']) || empty($data['match_time'])) {
        $errors['match_time'] = "L'heure du match est obligatoire";
    } elseif (!isValidTime($data['match_time'])) {
        $errors['match_time'] = "L'heure du match n'est pas valide";
    }

    // Validation des prix et capacités
    $categories = [
        'vip' => 'VIP',
        'covered' => 'Tribune couverte',
        'popular' => 'Tribune populaire',
        'lawn' => 'Pelouse'
    ];

    $hasValidCategory = false;
    foreach ($categories as $key => $label) {
        $priceKey = "{$key}_price";
        $capacityKey = "{$key}_capacity";

        if (isset($data[$priceKey]) && $data[$priceKey] !== '') {
            if (!is_numeric($data[$priceKey]) || $data[$priceKey] < 0) {
                $errors[$priceKey] = "Le prix pour {$label} doit être un nombre positif";
            }

            if (!isset($data[$capacityKey]) || $data[$capacityKey] === '' || 
                !is_numeric($data[$capacityKey]) || $data[$capacityKey] < 1) {
                $errors[$capacityKey] = "La capacité pour {$label} doit être un nombre positif";
            }

            $hasValidCategory = true;
        }
    }

    if (!$hasValidCategory) {
        $errors['categories'] = "Au moins une catégorie de billets doit être définie";
    }

    // Validation de la date limite de vente
    if (isset($data['sale_end_date']) && !empty($data['sale_end_date'])) {
        if (!isValidDate($data['sale_end_date'])) {
            $errors['sale_end_date'] = "La date limite de vente n'est pas valide";
        } elseif (isset($data['match_date']) && $data['sale_end_date'] > $data['match_date']) {
            $errors['sale_end_date'] = "La date limite de vente doit être antérieure à la date du match";
        }
    }

    // Validation du statut
    $validStatuses = ['scheduled', 'in_progress', 'completed', 'postponed', 'cancelled'];
    if (isset($data['status']) && !empty($data['status']) && !in_array($data['status'], $validStatuses)) {
        $errors['status'] = "Le statut n'est pas valide";
    }

    // Validation de la compétition
    if (!isset($data['competition']) || empty(trim($data['competition']))) {
        $errors['competition'] = "La compétition est obligatoire";
    }

    return $errors;
}

/**
 * Valide une image uploadée
 * @param array $file Les données du fichier ($_FILES['field'])
 * @return string|null Message d'erreur ou null si valide
 */
function validateImage($file) {
    if (empty($file['tmp_name'])) {
        return null; // L'image n'est pas obligatoire
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5 MB
    $maxDimension = 2048; // 2048px max

    // Vérification du type MIME
    if (!in_array($file['type'], $allowedTypes)) {
        return "Le format de l'image n'est pas valide. Formats acceptés : JPG, PNG, WEBP";
    }

    // Vérification de la taille
    if ($file['size'] > $maxSize) {
        return "L'image est trop volumineuse. Taille maximale : 5 MB";
    }

    // Vérification des dimensions
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return "Le fichier n'est pas une image valide";
    }

    list($width, $height) = $imageInfo;
    if ($width > $maxDimension || $height > $maxDimension) {
        return "Les dimensions de l'image sont trop grandes. Maximum : {$maxDimension}x{$maxDimension} pixels";
    }

    return null;
}

/**
 * Nettoie et prépare les données d'un match
 * @param array $data Les données brutes
 * @return array Les données nettoyées
 */
function sanitizeMatchData($data) {
    $clean = [];

    // Données textuelles
    $clean['title'] = isset($data['title']) ? trim(strip_tags($data['title'])) : '';
    $clean['description'] = isset($data['description']) ? trim(strip_tags($data['description'])) : '';
    $clean['competition'] = isset($data['competition']) ? trim(strip_tags($data['competition'])) : '';
    $clean['tv_channel'] = isset($data['tv_channel']) ? trim(strip_tags($data['tv_channel'])) : '';

    // IDs et nombres
    $clean['team1_id'] = isset($data['team1_id']) ? filter_var($data['team1_id'], FILTER_VALIDATE_INT) : null;
    $clean['team2_id'] = isset($data['team2_id']) ? filter_var($data['team2_id'], FILTER_VALIDATE_INT) : null;
    $clean['stadium_id'] = isset($data['stadium_id']) ? filter_var($data['stadium_id'], FILTER_VALIDATE_INT) : null;

    // Prix et capacités
    $categories = ['vip', 'covered', 'popular', 'lawn'];
    foreach ($categories as $category) {
        $priceKey = "{$category}_price";
        $capacityKey = "{$category}_capacity";
        
        $clean[$priceKey] = isset($data[$priceKey]) ? filter_var($data[$priceKey], FILTER_VALIDATE_FLOAT) : null;
        $clean[$capacityKey] = isset($data[$capacityKey]) ? filter_var($data[$capacityKey], FILTER_VALIDATE_INT) : null;
    }

    // Booléens
    $clean['featured'] = isset($data['featured']) ? 1 : 0;
    $clean['tv_broadcast'] = isset($data['tv_broadcast']) ? 1 : 0;

    // Dates
    $clean['match_date'] = isset($data['match_date']) ? trim($data['match_date']) : '';
    $clean['match_time'] = isset($data['match_time']) ? trim($data['match_time']) : '';
    $clean['sale_end_date'] = isset($data['sale_end_date']) ? trim($data['sale_end_date']) : null;

    // Statuts
    $clean['status'] = isset($data['status']) ? trim($data['status']) : 'scheduled';
    $clean['sale_status'] = isset($data['sale_status']) ? trim($data['sale_status']) : 'closed';

    return $clean;
} 