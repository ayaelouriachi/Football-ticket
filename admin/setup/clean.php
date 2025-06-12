<?php
require_once(__DIR__ . '/../includes/config.php');

try {
    // Désactiver les contraintes de clés étrangères
    $adminDb->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    // Liste des tables à supprimer
    $tables = [
        'admin_notifications',
        'admin_password_resets',
        'admin_settings',
        'admin_failed_logins',
        'admin_activity_logs',
        'admin_users'
    ];
    
    // Supprimer chaque table
    foreach ($tables as $table) {
        try {
            $adminDb->exec("DROP TABLE IF EXISTS $table");
            echo "Table $table supprimée avec succès<br>";
        } catch (PDOException $e) {
            echo "Erreur lors de la suppression de la table $table: " . $e->getMessage() . "<br>";
        }
    }
    
    // Réactiver les contraintes de clés étrangères
    $adminDb->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "<br>Nettoyage terminé. <a href='install.php'>Cliquez ici pour réinstaller</a>";
    
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?> 