<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- DEBUG: Début du fichier -->\n";
echo "Test de base - Page fonctionne\n";

// Test des chemins
echo "\nChemin absolu : " . __DIR__ . "\n";
echo "Document Root : " . $_SERVER['DOCUMENT_ROOT'] . "\n";

// Test de l'inclusion de base
$configPath = __DIR__ . '/../../config/init.php';
echo "\nTest d'inclusion de init.php (" . $configPath . ") :\n";
if (file_exists($configPath)) {
    echo "Le fichier init.php existe\n";
    include_once($configPath);
    echo "init.php inclus avec succès\n";
} else {
    echo "ERREUR: init.php n'existe pas\n";
}

// Test de la session
echo "\nTest de la session :\n";
session_start();
echo "Session ID: " . session_id() . "\n";

// Test de PDO si disponible
if (class_exists('PDO')) {
    echo "\nPDO est disponible\n";
} else {
    echo "\nPDO n'est pas disponible\n";
}

phpinfo();
?> 