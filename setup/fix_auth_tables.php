<?php
require_once __DIR__ . '/../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Vérifier et corriger la table users
    echo "Vérification de la table users...\n";
    
    // Vérifier si la colonne password_hash existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    if (!$stmt->fetch()) {
        // Ajouter la colonne password_hash
        $db->exec("ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL AFTER email");
        echo "✅ Colonne password_hash ajoutée\n";
    }
    
    // Vérifier si la colonne status existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER role");
        echo "✅ Colonne status ajoutée\n";
    }
    
    // Vérifier si la colonne role existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password_hash");
        echo "✅ Colonne role ajoutée\n";
    }
    
    // 2. Créer la table password_resets si elle n'existe pas
    $db->exec("CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        used TINYINT(1) DEFAULT 0,
        INDEX (email),
        INDEX (token)
    )");
    echo "✅ Table password_resets vérifiée\n";
    
    // 3. Mettre à jour les mots de passe par défaut si nécessaire
    $stmt = $db->prepare("SELECT id, email FROM users WHERE password_hash = '' OR password_hash IS NULL");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        $defaultPassword = 'ChangeMe2024!';
        $defaultHash = password_hash($defaultPassword, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        foreach ($users as $user) {
            $stmt->execute([$defaultHash, $user['id']]);
            echo "✅ Mot de passe par défaut défini pour {$user['email']}\n";
        }
    }
    
    echo "\n✅ Configuration de l'authentification terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
} 