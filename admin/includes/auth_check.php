<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // Rediriger vers la page de connexion avec un message d'erreur
    $_SESSION['flash'] = [
        'danger' => 'Vous devez être connecté en tant qu\'administrateur pour accéder à cette page.'
    ];
    header('Location: /football_tickets/login.php');
    exit;
}

// Récupérer les informations de l'administrateur connecté
try {
    require_once __DIR__ . '/../../config/database.php';
    
    $stmt = $conn->prepare("
        SELECT id, name, email, role, last_login 
        FROM users 
        WHERE id = ? AND role = 'admin' AND is_active = 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        // Si l'utilisateur n'existe plus ou n'est plus admin
        session_destroy();
        header('Location: /football_tickets/login.php');
        exit;
    }
    
} catch (PDOException $e) {
    error_log('Auth check error: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'danger' => 'Une erreur est survenue lors de la vérification de l\'authentification.'
    ];
    header('Location: /football_tickets/login.php');
    exit;
} 