<?php
require_once(__DIR__ . '/config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    // Afficher la structure de la table
    $stmt = $db->query("DESCRIBE admin_users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Structure de la table admin_users:\n";
    foreach ($columns as $column) {
        echo "{$column['Field']} - {$column['Type']} - {$column['Null']} - {$column['Key']} - {$column['Default']}\n";
    }
    
    // Vérifier les données existantes
    $stmt = $db->query("SELECT id, email, role, status FROM admin_users");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAdministrateurs existants:\n";
    foreach ($admins as $admin) {
        echo "ID: {$admin['id']} - Email: {$admin['email']} - Role: {$admin['role']} - Status: {$admin['status']}\n";
    }
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
} 