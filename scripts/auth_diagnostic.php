<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../classes/Auth.php');

// Initialize session
SessionManager::init();

echo "\n=== DIAGNOSTIC AUTHENTIFICATION ===\n\n";

// 1. Test de connexion à la base de données
echo "1. TEST CONNEXION BASE DE DONNÉES\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion DB réussie\n";
    
    // Test simple query
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Nombre total d'utilisateurs: $count\n";
} catch (PDOException $e) {
    echo "❌ ERREUR CONNEXION DB: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Inspection des utilisateurs
echo "\n2. INSPECTION DES UTILISATEURS\n";
try {
    $stmt = $db->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "\n--- Utilisateur ID: {$user['id']} ---\n";
        echo "Nom: {$user['name']}\n";
        echo "Email: {$user['email']}\n";
        echo "Mot de passe (20 premiers chars): " . substr($user['password'], 0, 20) . "...\n";
        echo "Longueur mot de passe: " . strlen($user['password']) . "\n";
        echo "Format bcrypt ($2y$): " . (strpos($user['password'], '$2y$') === 0 ? 'Oui' : 'Non') . "\n";
        echo "Format bcrypt ($2a$): " . (strpos($user['password'], '$2a$') === 0 ? 'Oui' : 'Non') . "\n";
        echo "Format bcrypt ($2b$): " . (strpos($user['password'], '$2b$') === 0 ? 'Oui' : 'Non') . "\n";
        echo "Is active: {$user['is_active']}\n";
        echo "Role: {$user['role']}\n";
        echo "Created at: {$user['created_at']}\n";
    }
} catch (PDOException $e) {
    echo "❌ ERREUR LECTURE USERS: " . $e->getMessage() . "\n";
}

// 3. Test de bcrypt
echo "\n3. TEST BCRYPT\n";
$testPassword = "test123";
$hash1 = password_hash($testPassword, PASSWORD_DEFAULT);
$hash2 = password_hash($testPassword, PASSWORD_DEFAULT);

echo "Mot de passe test: $testPassword\n";
echo "Hash 1: $hash1\n";
echo "Hash 2: $hash2\n";
echo "Hashes différents (normal): " . ($hash1 !== $hash2 ? "Oui" : "Non") . "\n";
echo "Verify hash1: " . (password_verify($testPassword, $hash1) ? "✅" : "❌") . "\n";
echo "Verify hash2: " . (password_verify($testPassword, $hash2) ? "✅" : "❌") . "\n";
echo "Verify wrong: " . (password_verify("mauvais", $hash1) ? "❌" : "✅") . "\n";

// 4. Test avec un utilisateur réel
echo "\n4. TEST AVEC UTILISATEUR RÉEL\n";
try {
    // Test avec l'email elouriachi.aya@etu.uae.ac.ma
    $email = "elouriachi.aya@etu.uae.ac.ma";
    $testPassword = "test123"; // Remplacer par le vrai mot de passe pour le test
    
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Utilisateur trouvé: {$user['email']}\n";
        echo "Hash en base: {$user['password']}\n";
        echo "Test password_verify: " . (password_verify($testPassword, $user['password']) ? "✅" : "❌") . "\n";
        
        // Test avec trim
        $trimmedPassword = trim($testPassword);
        echo "Test avec trim: " . (password_verify($trimmedPassword, $user['password']) ? "✅" : "❌") . "\n";
        
        // Test encodage
        echo "Encodage password: " . mb_detect_encoding($testPassword) . "\n";
        echo "Encodage hash: " . mb_detect_encoding($user['password']) . "\n";
    } else {
        echo "❌ Utilisateur non trouvé\n";
    }
} catch (PDOException $e) {
    echo "❌ ERREUR TEST USER: " . $e->getMessage() . "\n";
}

// 5. Test de session
echo "\n5. TEST SESSION\n";
echo "Session ID: " . session_id() . "\n";
echo "Session data: " . print_r($_SESSION, true) . "\n";
echo "User in session: " . (isset($_SESSION['user_id']) ? "Oui" : "Non") . "\n";

// 6. Test Auth class
echo "\n6. TEST CLASSE AUTH\n";
try {
    $auth = new Auth();
    $testLogin = $auth->login("elouriachi.aya@etu.uae.ac.ma", "test123");
    echo "Test login: " . ($testLogin ? "✅" : "❌") . "\n";
    if (!$testLogin) {
        echo "Raison possible: Vérifiez les logs PHP pour plus de détails\n";
    }
} catch (Exception $e) {
    echo "❌ ERREUR AUTH: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n"; 