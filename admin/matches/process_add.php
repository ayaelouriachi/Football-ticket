<?php
require_once(__DIR__ . '/../includes/layout.php');
require_once(__DIR__ . '/../api/config/database.php');

// Vérifier les permissions
if (!$auth->hasPermission('manage_matches')) {
    SessionManager::setFlashMessage('error', 'Vous n\'avez pas la permission d\'effectuer cette action.');
    header('Location: ../index.php');
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    SessionManager::setFlashMessage('error', 'Méthode non autorisée.');
    header('Location: add.php');
    exit;
}

// Valider les données
$errors = [];

if (empty($_POST['home_team_id'])) {
    $errors[] = "L'équipe domicile est requise.";
}

if (empty($_POST['away_team_id'])) {
    $errors[] = "L'équipe extérieur est requise.";
}

if ($_POST['home_team_id'] === $_POST['away_team_id']) {
    $errors[] = "Les équipes domicile et extérieur doivent être différentes.";
}

if (empty($_POST['stadium_id'])) {
    $errors[] = "Le stade est requis.";
}

if (empty($_POST['match_date'])) {
    $errors[] = "La date du match est requise.";
}

// Valider les catégories de billets
if (empty($_POST['category_name']) || empty($_POST['category_price']) || empty($_POST['category_capacity'])) {
    $errors[] = "Au moins une catégorie de billets est requise.";
}

// Si pas d'erreurs, procéder à l'insertion
if (empty($errors)) {
    try {
        $db = Database::getInstance();
        $db->beginTransaction();

        // Insérer le match
        $stmt = $db->prepare("
            INSERT INTO matches (
                home_team_id, away_team_id, stadium_id, match_date,
                status, created_at, updated_at
            ) VALUES (
                :home_team_id, :away_team_id, :stadium_id, :match_date,
                'draft', NOW(), NOW()
            )
        ");

        $stmt->execute([
            'home_team_id' => $_POST['home_team_id'],
            'away_team_id' => $_POST['away_team_id'],
            'stadium_id' => $_POST['stadium_id'],
            'match_date' => $_POST['match_date']
        ]);

        $matchId = $db->lastInsertId();

        // Insérer les catégories de billets
        $stmt = $db->prepare("
            INSERT INTO ticket_categories (
                match_id, name, price, capacity, description,
                created_at, updated_at
            ) VALUES (
                :match_id, :name, :price, :capacity, :description,
                NOW(), NOW()
            )
        ");

        foreach ($_POST['category_name'] as $key => $name) {
            if (!empty($name) && isset($_POST['category_price'][$key]) && isset($_POST['category_capacity'][$key])) {
                $stmt->execute([
                    'match_id' => $matchId,
                    'name' => $name,
                    'price' => $_POST['category_price'][$key],
                    'capacity' => $_POST['category_capacity'][$key],
                    'description' => $_POST['category_description'][$key] ?? null
                ]);
            }
        }

        $db->commit();
        SessionManager::setFlashMessage('success', 'Le match a été créé avec succès.');
        header('Location: index.php');
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur lors de la création du match : " . $e->getMessage());
        SessionManager::setFlashMessage('error', 'Une erreur est survenue lors de la création du match.');
        header('Location: add.php');
        exit;
    }
} else {
    // S'il y a des erreurs, les afficher et rediriger
    SessionManager::setFlashMessage('error', implode('<br>', $errors));
    header('Location: add.php');
    exit;
} 