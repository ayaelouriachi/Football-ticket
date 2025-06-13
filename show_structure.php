<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    $result = $db->query('SHOW CREATE TABLE payments');
    $structure = $result->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'structure' => $structure
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} 