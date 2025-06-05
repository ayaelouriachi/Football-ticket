<?php
require_once(__DIR__ . '/../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier si la colonne is_active existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_active'");
    if ($stmt->rowCount() === 0) {
        // Ajouter la colonne is_active
        $db->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1");
        echo "✅ Colonne is_active ajoutée avec succès\n";
        
        // Mettre à jour les utilisateurs existants
        $db->exec("UPDATE users SET is_active = 1");
        echo "✅ Tous les utilisateurs existants ont été activés\n";
    } else {
        echo "La colonne is_active existe déjà\n";
    }
    
    // Vérifier les mots de passe
    $stmt = $db->query("SELECT id, email, password FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        if (!str_starts_with($user['password'], '$2y$')) {
            echo "⚠️ Le mot de passe pour {$user['email']} n'est pas au format bcrypt\n";
        }
    }
    
    echo "\nStructure de la base de données mise à jour avec succès !\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
} 