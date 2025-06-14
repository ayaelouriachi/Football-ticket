<?php
require_once dirname(__DIR__) . '/config/database.php';

try {
    // Ajout du champ role à la table users s'il n'existe pas déjà
    $db->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user'
    ");

    // Création de la table admin_logs
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id)
        )
    ");

    // Création de la table admin_settings
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(255) NOT NULL UNIQUE,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    echo "✅ Tables d'administration créées avec succès!";

} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage());
} 