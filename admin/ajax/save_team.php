<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Vérification de la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF invalide']);
    exit;
}

try {
    // Validation des données
    if (empty($_POST['name'])) {
        throw new Exception('Le nom de l\'équipe est obligatoire');
    }

    $name = trim($_POST['name']);
    $logoPath = null;

    // Traitement du logo si présent
    if (!empty($_FILES['logo']['tmp_name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5 MB

        if (!in_array($_FILES['logo']['type'], $allowedTypes)) {
            throw new Exception('Format de logo non valide. Formats acceptés : JPG, PNG, WEBP');
        }

        if ($_FILES['logo']['size'] > $maxSize) {
            throw new Exception('Le logo est trop volumineux. Taille maximale : 5 MB');
        }

        // Génération du nom de fichier unique
        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('team_') . '.' . $extension;
        $uploadPath = __DIR__ . '/../../uploads/teams/' . $fileName;

        // Création du dossier si nécessaire
        if (!is_dir(dirname($uploadPath))) {
            mkdir(dirname($uploadPath), 0777, true);
        }

        // Upload du fichier
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
            throw new Exception('Erreur lors de l\'upload du logo');
        }

        $logoPath = 'uploads/teams/' . $fileName;
    }

    // Insertion dans la base de données
    $stmt = $db->prepare("
        INSERT INTO teams (name, logo, created_at, created_by)
        VALUES (?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $name,
        $logoPath,
        $_SESSION['admin_id']
    ]);

    $teamId = $db->lastInsertId();

    // Log de l'action
    logAdminAction('Création d\'une équipe', "Équipe créée : $name");

    // Réponse
    echo json_encode([
        'success' => true,
        'message' => 'Équipe créée avec succès',
        'team' => [
            'id' => $teamId,
            'name' => $name,
            'logo' => $logoPath
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 