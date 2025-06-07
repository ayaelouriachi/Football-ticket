<?php
// Liste des icônes à télécharger
$icons = [
    'visa' => 'https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/visa.svg',
    'mastercard' => 'https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/mastercard.svg',
    'paypal' => 'https://raw.githubusercontent.com/simple-icons/simple-icons/develop/icons/paypal.svg'
];

// Dossier de destination
$targetDir = __DIR__ . '/../assets/images/';

// Créer le dossier s'il n'existe pas
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Télécharger chaque icône
foreach ($icons as $name => $url) {
    $targetFile = $targetDir . $name . '.png';
    
    // Vérifier si le fichier existe déjà
    if (!file_exists($targetFile)) {
        // Télécharger l'icône
        $content = file_get_contents($url);
        if ($content !== false) {
            file_put_contents($targetFile, $content);
            echo "Icône $name téléchargée avec succès\n";
        } else {
            echo "Erreur lors du téléchargement de l'icône $name\n";
        }
    } else {
        echo "L'icône $name existe déjà\n";
    }
}

echo "Terminé!\n"; 