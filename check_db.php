<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "=== VÉRIFICATION DE LA BASE DE DONNÉES ===\n\n";

// Liste des tables
echo "1. Tables dans la base de données :\n";
$stmt = $db->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

// Structure de chaque table
foreach ($tables as $table) {
    echo "\n2. Structure de la table '$table' :\n";
    $stmt = $db->query("DESCRIBE $table");
    print_r($stmt->fetchAll());
}

// Données de test
echo "\n3. Données de la commande de test :\n";
$stmt = $db->query('SELECT * FROM orders WHERE id = 16');
print_r($stmt->fetch());

echo "\n4. Articles de la commande de test :\n";
$stmt = $db->query('SELECT * FROM order_items WHERE order_id = 16');
print_r($stmt->fetchAll()); 