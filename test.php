<?php
echo "<h1>Test de configuration</h1>";
echo "<p>Le serveur web fonctionne correctement.</p>";
echo "<p>Chemin du script : " . $_SERVER['SCRIPT_NAME'] . "</p>";
echo "<p>URI demandée : " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p>Répertoire de base : " . dirname($_SERVER['SCRIPT_NAME']) . "</p>";

// Tester la connexion à la base de données
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p style='color: green;'>Connexion à la base de données réussie !</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur de connexion à la base de données : " . $e->getMessage() . "</p>";
}

// Tester les sessions
session_start();
$_SESSION['test'] = 'Test de session';
if (isset($_SESSION['test'])) {
    echo "<p style='color: green;'>Les sessions fonctionnent correctement !</p>";
} else {
    echo "<p style='color: red;'>Problème avec les sessions</p>";
}

// Afficher les modules Apache chargés
echo "<h2>Modules Apache chargés</h2>";
echo "<pre>";
print_r(apache_get_modules());
echo "</pre>";

// Afficher la configuration PHP
echo "<h2>Configuration PHP</h2>";
echo "<pre>";
print_r([
    'PHP Version' => phpversion(),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'session.save_handler' => ini_get('session.save_handler'),
    'session.save_path' => ini_get('session.save_path'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
]);
echo "</pre>";
?> 