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
        throw new Exception('Le nom du stade est obligatoire');
    }
    if (empty($_POST['city'])) {
        throw new Exception('La ville est obligatoire');
    }

    $name = trim($_POST['name']);
    $city = trim($_POST['city']);
    $capacity = !empty($_POST['capacity']) ? (int)$_POST['capacity'] : null;

    // Vérification si le stade existe déjà
    $stmt = $db->prepare("SELECT id FROM stadiums WHERE name = ? AND city = ?");
    $stmt->execute([$name, $city]);
    if ($stmt->fetch()) {
        throw new Exception('Un stade avec ce nom existe déjà dans cette ville');
    }

    // Insertion dans la base de données
    $stmt = $db->prepare("
        INSERT INTO stadiums (name, city, capacity, created_at, created_by)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $name,
        $city,
        $capacity,
        $_SESSION['admin_id']
    ]);

    $stadiumId = $db->lastInsertId();

    // Log de l'action
    logAdminAction('Création d\'un stade', "Stade créé : $name ($city)");

    // Réponse
    echo json_encode([
        'success' => true,
        'message' => 'Stade créé avec succès',
        'stadium' => [
            'id' => $stadiumId,
            'name' => $name,
            'city' => $city,
            'capacity' => $capacity
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 