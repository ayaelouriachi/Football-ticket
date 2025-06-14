<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tester la connexion
$email = 'admin@example.com';
$password = 'admin123';

echo "=== Test de connexion admin ===\n\n";

// 1. Vérifier que l'utilisateur existe
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ Utilisateur trouvé\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "is_admin: " . ($user['is_admin'] ? 'Oui' : 'Non') . "\n";
        echo "role: " . $user['role'] . "\n\n";
    } else {
        die("❌ Utilisateur non trouvé\n");
    }
} catch (PDOException $e) {
    die("❌ Erreur base de données: " . $e->getMessage() . "\n");
}

// 2. Tester la connexion
echo "Test de la fonction login()...\n";
if (login($email, $password)) {
    echo "✅ Connexion réussie\n";
    echo "Variables de session :\n";
    echo "user_id: " . $_SESSION['user_id'] . "\n";
    echo "is_admin: " . ($_SESSION['is_admin'] ? 'Oui' : 'Non') . "\n";
    echo "user_email: " . $_SESSION['user_email'] . "\n";
    echo "user_name: " . $_SESSION['user_name'] . "\n\n";
} else {
    die("❌ Échec de la connexion\n");
}

// 3. Vérifier les droits d'administration
echo "Test de la fonction isAdmin()...\n";
if (isAdmin()) {
    echo "✅ L'utilisateur a les droits d'administration\n";
} else {
    echo "❌ L'utilisateur n'a pas les droits d'administration\n";
}

// 4. Tester la déconnexion
echo "\nTest de la fonction logout()...\n";
logout();
if (!isLoggedIn()) {
    echo "✅ Déconnexion réussie\n";
} else {
    echo "❌ Échec de la déconnexion\n";
} 