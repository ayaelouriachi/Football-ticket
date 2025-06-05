<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../classes/Auth.php');

echo "\n=== TEST DE CONNEXION ===\n\n";

// 1. Test de connexion à la base de données
echo "1. CONNEXION À LA BASE DE DONNÉES\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion DB réussie\n";
    
    // Vérifier la table users
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Colonnes de la table users: " . implode(', ', $columns) . "\n";
} catch (PDOException $e) {
    echo "❌ ERREUR DB: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Vérification des utilisateurs existants
echo "\n2. VÉRIFICATION DES UTILISATEURS\n";
try {
    $stmt = $db->query("SELECT id, email, password, is_active FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        echo "\nUtilisateur ID: {$user['id']}\n";
        echo "Email: {$user['email']}\n";
        echo "Hash du mot de passe: " . substr($user['password'], 0, 20) . "...\n";
        echo "Format bcrypt: " . (strpos($user['password'], '$2y$') === 0 ? "✅" : "❌") . "\n";
        echo "Compte actif: " . ($user['is_active'] ? "✅" : "❌") . "\n";
    }
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

// 3. Test de connexion avec un utilisateur réel
echo "\n3. TEST DE CONNEXION\n";
$testEmail = "elouriachi.aya@etu.uae.ac.ma";
$testPassword = "123456789"; // Mot de passe défini précédemment

try {
    $auth = new Auth();
    
    // Test avant connexion
    echo "Session avant connexion: " . print_r($_SESSION, true) . "\n";
    
    // Tentative de connexion
    $result = $auth->login($testEmail, $testPassword);
    
    echo "Résultat de la connexion: " . ($result ? "✅ Succès" : "❌ Échec") . "\n";
    
    if ($result) {
        echo "Données utilisateur: " . print_r($result, true) . "\n";
        echo "Session après connexion: " . print_r($_SESSION, true) . "\n";
    } else {
        // Test du mot de passe manuellement
        $stmt = $db->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $storedHash = $stmt->fetchColumn();
        
        if ($storedHash) {
            echo "Hash stocké: $storedHash\n";
            echo "Test password_verify: " . (password_verify($testPassword, $storedHash) ? "✅" : "❌") . "\n";
            echo "Info hash: " . print_r(password_get_info($storedHash), true) . "\n";
        } else {
            echo "❌ Utilisateur non trouvé\n";
        }
    }
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DES TESTS ===\n"; 