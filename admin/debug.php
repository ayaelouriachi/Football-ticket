<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/includes/config.php');

echo "<h2>Vérification de la configuration</h2>";

// Vérifier la connexion à la base de données principale
echo "<h3>Connexion base de données principale</h3>";
try {
    $db->query("SELECT 1");
    echo "✅ Connexion principale OK<br>";
} catch (PDOException $e) {
    echo "❌ Erreur connexion principale : " . $e->getMessage() . "<br>";
}

// Vérifier la connexion à la base de données admin
echo "<h3>Connexion base de données admin</h3>";
try {
    $adminDb->query("SELECT 1");
    echo "✅ Connexion admin OK<br>";
} catch (PDOException $e) {
    echo "❌ Erreur connexion admin : " . $e->getMessage() . "<br>";
}

// Vérifier les tables nécessaires
echo "<h3>Vérification des tables</h3>";
$requiredTables = ['matches', 'orders', 'order_items', 'users', 'ticket_categories'];
foreach ($requiredTables as $table) {
    try {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table $table existe<br>";
        } else {
            echo "❌ Table $table manquante<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur vérification table $table : " . $e->getMessage() . "<br>";
    }
}

// Vérifier la session
echo "<h3>Vérification de la session</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session status: " . session_status() . "<br>";
echo "<pre>SESSION: " . print_r($_SESSION, true) . "</pre>";

// Vérifier les constantes importantes
echo "<h3>Vérification des constantes</h3>";
$constants = [
    'BASE_URL',
    'ADMIN_URL',
    'DB_HOST',
    'DB_NAME',
    'DB_USER'
];

foreach ($constants as $constant) {
    if (defined($constant)) {
        echo "✅ $constant = " . constant($constant) . "<br>";
    } else {
        echo "❌ $constant non définie<br>";
    }
} 