<?php
require_once(__DIR__ . '/../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier si la colonne last_login existe
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if ($stmt->rowCount() === 0) {
        // Ajouter la colonne last_login
        $db->exec("ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL DEFAULT NULL");
        echo "✅ Colonne last_login ajoutée avec succès\n";
    } else {
        echo "La colonne last_login existe déjà\n";
    }
    
    echo "\nStructure de la base de données mise à jour avec succès !\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
} 