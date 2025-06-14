<?php
/**
 * Test de la configuration de la base de données
 * Ce fichier vérifie que toutes les constantes sont correctement définies
 */

require_once 'config/constants.php';
require_once 'config/database.php';

echo "Test des constantes de base de données :\n";
echo "----------------------------------------\n";
echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NON DÉFINI') . "\n";
echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NON DÉFINI') . "\n";
echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NON DÉFINI') . "\n";
echo "DB_PASSWORD: " . (defined('DB_PASSWORD') ? '[MASQUÉ]' : 'NON DÉFINI') . "\n";
echo "DB_CHARSET: " . (defined('DB_CHARSET') ? DB_CHARSET : 'NON DÉFINI') . "\n";
echo "DB_COLLATION: " . (defined('DB_COLLATION') ? DB_COLLATION : 'NON DÉFINI') . "\n";
echo "\n";

echo "Test de connexion à la base de données :\n";
echo "----------------------------------------\n";
try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    // Test simple de requête
    $stmt = $connection->query("SELECT 1");
    echo "✅ Connexion réussie\n";
    
    // Test de la configuration
    $stmt = $connection->query("SHOW VARIABLES LIKE 'character_set_database'");
    $charset = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Charset de la base : " . $charset['Value'] . "\n";
    
    $stmt = $connection->query("SHOW VARIABLES LIKE 'collation_database'");
    $collation = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Collation de la base : " . $collation['Value'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage() . "\n";
} 