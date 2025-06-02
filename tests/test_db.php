<?php
require_once __DIR__ . '/../config/database.php';

echo "<h2>Test de connexion à la base de données</h2>";

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Test simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    
    echo "✅ Connexion réussie à la base de données<br>";
    echo "📊 Nombre d'utilisateurs : " . $result['count'] . "<br>";
    
    // Test des tables principales
    $tables = ['users', 'matches', 'teams', 'stadiums', 'ticket_categories'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch();
        echo "📋 Table $table : " . $result['count'] . " enregistrements<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>