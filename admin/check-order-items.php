<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/init.php');

echo "<h2>Vérification de la table order_items</h2>";

try {
    // Vérifier si la table existe
    $stmt = $db->query("SHOW TABLES LIKE 'order_items'");
    if ($stmt->rowCount() === 0) {
        echo "❌ La table order_items n'existe pas<br>";
    } else {
        echo "✅ La table order_items existe<br><br>";
        
        // Afficher la structure
        echo "<h3>Structure de la table :</h3>";
        $columns = $db->query("SHOW COLUMNS FROM order_items")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
        
        // Vérifier les clés étrangères
        echo "<h3>Clés étrangères :</h3>";
        $foreignKeys = $db->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_NAME = 'order_items'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        print_r($foreignKeys);
        echo "</pre>";
        
        // Afficher quelques données
        echo "<h3>Exemple de données :</h3>";
        $data = $db->query("SELECT * FROM order_items LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<strong>Erreur :</strong><br>";
    echo "Message : " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    echo "</div>";
}
?> 