<?php
// Configuration des logs
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/database_update.log');

echo "=== Mise à jour de la structure de la base de données ===\n\n";

require_once 'config/constants.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Ajouter la colonne is_active si elle n'existe pas
    $db->exec("
        ALTER TABLE users
        ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE,
        ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user'
    ");
    
    // Créer un utilisateur admin par défaut s'il n'existe pas
    $stmt = $db->prepare("SELECT id FROM users WHERE email = 'admin@footballtickets.ma'");
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password, role, is_active)
            VALUES ('Admin', 'admin@footballtickets.ma', ?, 'admin', TRUE)
        ");
        $stmt->execute([$password]);
        echo "✅ Utilisateur admin créé avec succès\n";
    }
    
    echo "✅ Structure de la base de données mise à jour avec succès\n";
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
}
?> 