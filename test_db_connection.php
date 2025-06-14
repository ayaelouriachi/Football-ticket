<?php
/**
 * Script de test de la connexion à la base de données
 */

// Affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chargement des fichiers nécessaires
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "Test de connexion à la base de données\n";
echo "=====================================\n\n";

// Affichage des paramètres (sans le mot de passe)
echo "Paramètres :\n";
echo "- Hôte : " . DB_HOST . "\n";
echo "- Base de données : " . DB_NAME . "\n";
echo "- Utilisateur : " . DB_USER . "\n";
echo "- Charset : " . DB_CHARSET . "\n";
echo "- Collation : " . DB_COLLATION . "\n\n";

try {
    // Test de la connexion
    $db = Database::getInstance()->getConnection();
    
    // Test d'une requête simple
    $stmt = $db->query("SELECT 1");
    $result = $stmt->fetch();
    
    echo "✅ Connexion réussie !\n";
    echo "✅ Requête test exécutée avec succès\n";
    
    // Test des tables principales
    $tables = ['matches', 'teams', 'stadiums', 'users', 'orders'];
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT 1 FROM $table LIMIT 1");
            echo "✅ Table '$table' accessible\n";
        } catch (PDOException $e) {
            echo "❌ Table '$table' non trouvée ou inaccessible\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de connexion :\n";
    echo $e->getMessage() . "\n";
    
    // Vérifications supplémentaires
    echo "\nVérifications :\n";
    
    // Vérification de MySQL
    if (!extension_loaded('pdo_mysql')) {
        echo "❌ Extension PDO MySQL non chargée\n";
    } else {
        echo "✅ Extension PDO MySQL chargée\n";
    }
    
    // Vérification du serveur MySQL
    $socket = @fsockopen(DB_HOST, 3306, $errno, $errstr, 5);
    if (!$socket) {
        echo "❌ Impossible de se connecter au serveur MySQL sur le port 3306\n";
        echo "   Erreur : $errstr ($errno)\n";
    } else {
        fclose($socket);
        echo "✅ Serveur MySQL accessible sur le port 3306\n";
    }
} 