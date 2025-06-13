<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once(__DIR__ . '/config/database.php');
    
    // Utilise la classe Database
    $db = Database::getInstance()->getConnection();
    
    if (!$db) {
        throw new Exception('Impossible d\'obtenir la connexion à la base de données');
    }

    // Supprime la table si elle existe
    $db->exec("DROP TABLE IF EXISTS payments");

    // Crée la table avec la bonne structure
    $sql = "CREATE TABLE payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id VARCHAR(100) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(3) NOT NULL,
        status VARCHAR(50) NOT NULL,
        payment_id VARCHAR(100) NOT NULL,
        user_id INT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_payment (payment_id),
        UNIQUE KEY unique_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->exec($sql);

    // Vérifie que la table a été créée
    $tableExists = $db->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;
    
    if (!$tableExists) {
        throw new Exception('La table n\'a pas été créée correctement');
    }

    // Récupère la structure de la table
    $columns = $db->query("SHOW COLUMNS FROM payments")->fetchAll(PDO::FETCH_ASSOC);
    
    // Vérifie que toutes les colonnes nécessaires existent
    $requiredColumns = ['id', 'order_id', 'amount', 'currency', 'status', 'payment_id', 'user_id', 'created_at'];
    $existingColumns = array_column($columns, 'Field');
    $missingColumns = array_diff($requiredColumns, $existingColumns);
    
    if (!empty($missingColumns)) {
        throw new Exception('Colonnes manquantes : ' . implode(', ', $missingColumns));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Table payments créée avec succès',
        'structure' => $columns
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log("Erreur dans test_db_structure.php : " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} 