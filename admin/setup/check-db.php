<?php
require_once(__DIR__ . '/../includes/config.php');

try {
    // Vérifier la table admin_users
    $stmt = $adminDb->query("SELECT * FROM admin_users");
    echo "<h3>Table admin_users :</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Vérifier la table admin_settings
    $stmt = $adminDb->query("SELECT * FROM admin_settings");
    echo "<h3>Table admin_settings :</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Vérifier la table admin_failed_logins
    $stmt = $adminDb->query("SELECT * FROM admin_failed_logins");
    echo "<h3>Table admin_failed_logins :</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h3>Erreur :</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?> 