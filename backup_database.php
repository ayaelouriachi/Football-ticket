<?php
require_once 'config/constants.php';

// Créer le dossier de sauvegarde s'il n'existe pas
$backupDir = __DIR__ . '/backup_files';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$date = date('Y-m-d_H-i-s');
$backupFile = $backupDir . "/backup_" . $date . ".sql";

// Chemin vers mysqldump dans XAMPP
$mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump';

// Commande pour créer la sauvegarde
$command = sprintf(
    '"%s" -h %s -u %s %s %s > %s',
    $mysqldump,
    DB_HOST,
    DB_USER,
    (DB_PASSWORD ? '-p' . DB_PASSWORD : ''),
    DB_NAME,
    $backupFile
);

try {
    // Exécuter la commande de sauvegarde
    system($command, $returnValue);
    
    if ($returnValue === 0) {
        echo "✅ Sauvegarde créée avec succès dans : " . $backupFile . "\n";
        
        // Maintenant, exécutons le script de mise à jour
        echo "\nMise à jour de la base de données...\n";
        require_once 'update_database_structure.php';
        
    } else {
        throw new Exception("Erreur lors de la création de la sauvegarde");
    }
} catch (Exception $e) {
    die("❌ Erreur : " . $e->getMessage() . "\n");
} 