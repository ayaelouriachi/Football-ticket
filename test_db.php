<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once(__DIR__ . '/config/database.php');
    
    // Utilise la classe Database existante
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        throw new Exception('Impossible d\'obtenir la connexion à la base de données');
    }

    // Récupère la liste des tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $database_structure = [];
    
    foreach ($tables as $table) {
        // Structure de la table
        $columns = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        // Clés et contraintes
        $keys = $db->query("SHOW KEYS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        
        // Exemple de données (limité à 1 ligne)
        $data_sample = $db->query("SELECT * FROM `$table` LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        $database_structure[$table] = [
            'columns' => $columns,
            'keys' => $keys,
            'sample_data' => $data_sample
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Structure de la base de données',
        'database_name' => DB_NAME,
        'tables' => $database_structure
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Erreur dans test_db.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} 