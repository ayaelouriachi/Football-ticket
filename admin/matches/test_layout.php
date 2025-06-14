<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test début<br>";

// Test des inclusions une par une
try {
    echo "Test config...<br>";
    require_once(__DIR__ . '/../includes/config.php');
    echo "Config OK<br>";

    echo "Test auth...<br>";
    require_once(__DIR__ . '/../includes/auth.php');
    echo "Auth OK<br>";

    echo "Test layout...<br>";
    $pageTitle = "Test Layout";
    require_once(__DIR__ . '/../includes/layout.php');
    echo "Layout OK<br>";
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "<br>";
}

echo "Test fin<br>";
?> 