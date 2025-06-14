<?php
require_once(__DIR__ . '/config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    // Créer la table admin_users
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('super_admin', 'admin', 'moderator') NOT NULL DEFAULT 'admin',
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Vérifier si un admin existe déjà
    $stmt = $db->query("SELECT COUNT(*) FROM admin_users");
    $adminCount = $stmt->fetchColumn();
    
    // Créer un admin par défaut si aucun n'existe
    if ($adminCount == 0) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO admin_users (first_name, last_name, email, password_hash, role) 
            VALUES ('Admin', 'System', 'admin@example.com', ?, 'super_admin')
        ");
        $stmt->execute([$password]);
        echo "✅ Admin par défaut créé avec succès!\n";
        echo "Email: admin@example.com\n";
        echo "Mot de passe: admin123\n";
    }
    
    echo "✅ Table admin_users configurée avec succès!\n";
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
} 