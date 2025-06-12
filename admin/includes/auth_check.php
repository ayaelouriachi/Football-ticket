<?php
session_start();

// Inclure la classe Database et obtenir une connexion
require_once __DIR__ . '/../../config/database.php';
$db = Database::getInstance();
$conn = $db->getConnection();

// Vérifier si l'utilisateur est connecté en tant qu'admin
if (!isset($_SESSION['admin_id'])) {
    // Rediriger vers la page de connexion admin avec un message d'erreur
    $_SESSION['flash'] = [
        'danger' => 'Vous devez être connecté en tant qu\'administrateur pour accéder à cette page.'
    ];
    header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login.php');
    exit;
}

// Récupérer les informations de l'administrateur connecté
try {
    $stmt = $conn->prepare("
        SELECT id, CONCAT(first_name, ' ', last_name) as name, email, role, last_login 
        FROM admin_users 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminUser) {
        // Si l'utilisateur n'existe plus ou n'est plus admin
        session_destroy();
        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login.php');
        exit;
    }
    
    // Mettre à jour les informations de session admin
    $_SESSION['admin_name'] = $adminUser['name'];
    $_SESSION['admin_role'] = $adminUser['role'];
    
} catch (PDOException $e) {
    error_log('Auth check error: ' . $e->getMessage());
    $_SESSION['flash'] = [
        'danger' => 'Une erreur est survenue lors de la vérification de l\'authentification.'
    ];
    header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login.php');
    exit;
} 