<?php
/**
 * Fonctions utilitaires pour l'interface d'administration
 */

/**
 * Échappe les caractères spéciaux HTML
 * @param string|array $data Les données à échapper
 * @return string|array Les données échappées
 */
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Formate une date au format français
 * @param string $date La date à formater
 * @return string La date formatée
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formate une heure au format 24h
 * @param string $time L'heure à formater
 * @return string L'heure formatée
 */
function formatTime($time) {
    return date('H:i', strtotime($time));
}

/**
 * Formate un prix en dirhams
 * @param float $price Le prix à formater
 * @return string Le prix formaté
 */
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' DH';
}

/**
 * Upload une image
 * @param array $file Les données du fichier ($_FILES['field'])
 * @param string $targetDir Le dossier cible
 * @return string|false Le nom du fichier uploadé ou false en cas d'erreur
 */
if (!function_exists('uploadImage')) {
    function uploadImage($file, $targetDir) {
        // Vérification du dossier cible
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Génération d'un nom unique
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('match_') . '.' . $extension;
        $targetFile = $targetDir . $fileName;

        // Upload du fichier
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return $fileName;
        }

        return false;
    }
}

/**
 * Supprime une image
 * @param string $fileName Le nom du fichier
 * @param string $targetDir Le dossier cible
 * @return bool
 */
function deleteImage($fileName, $targetDir) {
    $filePath = $targetDir . $fileName;
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

/**
 * Vérifie si une date est valide
 * @param string $date La date à vérifier (format Y-m-d)
 * @return bool
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Vérifie si une heure est valide
 * @param string $time L'heure à vérifier (format H:i)
 * @return bool
 */
function isValidTime($time) {
    $t = DateTime::createFromFormat('H:i', $time);
    return $t && $t->format('H:i') === $time;
}

/**
 * Génère un slug à partir d'une chaîne
 * @param string $string La chaîne à convertir
 * @return string Le slug
 */
function generateSlug($string) {
    // Translittération des caractères accentués
    $string = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string);
    
    // Remplacement des caractères spéciaux par des tirets
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    
    // Suppression des tirets multiples
    $string = preg_replace('/-+/', '-', $string);
    
    // Suppression des tirets en début et fin
    return trim($string, '-');
}

/**
 * Tronque un texte à une longueur donnée
 * @param string $text Le texte à tronquer
 * @param int $length La longueur maximale
 * @param string $suffix Le suffixe à ajouter
 * @return string Le texte tronqué
 */
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Formate un statut de match
 * @param string $status Le statut à formater
 * @return string Le statut formaté
 */
function formatMatchStatus($status) {
    $statuses = [
        'scheduled' => 'Programmé',
        'in_progress' => 'En cours',
        'completed' => 'Terminé',
        'postponed' => 'Reporté',
        'cancelled' => 'Annulé'
    ];
    
    return $statuses[$status] ?? $status;
}

/**
 * Formate un statut de vente
 * @param string $status Le statut à formater
 * @return string Le statut formaté
 */
function formatSaleStatus($status) {
    $statuses = [
        'pending' => 'En attente',
        'open' => 'Ouverte',
        'closed' => 'Fermée',
        'sold_out' => 'Complet'
    ];
    
    return $statuses[$status] ?? $status;
}

/**
 * Calcule le pourcentage de places vendues
 * @param int $capacity Capacité totale
 * @param int $sold Nombre de places vendues
 * @return float Le pourcentage
 */
function calculateSoldPercentage($capacity, $sold) {
    if ($capacity <= 0) {
        return 0;
    }
    return round(($sold / $capacity) * 100, 1);
}

/**
 * Vérifie si un match est complet
 * @param array $match Les données du match
 * @return bool
 */
function isMatchSoldOut($match) {
    $categories = ['vip', 'covered', 'popular', 'lawn'];
    foreach ($categories as $category) {
        $capacityKey = "{$category}_capacity";
        $soldKey = "{$category}_sold";
        if (isset($match[$capacityKey]) && isset($match[$soldKey])) {
            if ($match[$capacityKey] > $match[$soldKey]) {
                return false;
            }
        }
    }
    return true;
}

/**
 * Génère un token CSRF
 * @return string Le token généré
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token Le token à vérifier
 * @return bool True si le token est valide
 */
function verifyCsrfToken($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enregistre une action dans les logs admin
 * @param string $action Description de l'action
 * @param string $details Détails supplémentaires
 */
function logAdminAction($action, $details = '') {
    global $db;
    $stmt = $db->prepare("
        INSERT INTO admin_logs (admin_id, action, details, ip_address)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['admin_id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR']
    ]);
}

/**
 * Vérifie si l'utilisateur a une permission spécifique
 * @param string $permission La permission à vérifier
 * @return bool True si l'utilisateur a la permission
 */
function hasPermission($permission) {
    // À implémenter selon votre système de permissions
    return true; // Pour l'instant, on retourne toujours true
}

/**
 * Vérifie si l'utilisateur est connecté en tant qu'admin
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Définit un message flash
 * @param string $type Type de message (success, error, warning, info)
 * @param string $message Le message
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Récupère et supprime les messages flash
 * @return array Les messages flash
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Affiche les messages flash
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    foreach ($messages as $message) {
        $type = $message['type'];
        $text = $message['message'];
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
        echo escape($text);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
} 