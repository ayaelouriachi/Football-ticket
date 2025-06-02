<?php
$directories = [
    'config',
    'includes',
    'classes',
    'admin',
    'admin/matches',
    'admin/categories',
    'admin/orders',
    'admin/includes',
    'assets',
    'assets/css',
    'assets/js',
    'assets/images',
    'assets/images/matches',
    'assets/images/teams',
    'assets/uploads',
    'assets/uploads/matches',
    'assets/uploads/teams',
    'pages',
    'api',
    'tests'
];

echo "<h2>Création de la structure du projet</h2>";

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Dossier créé : $dir<br>";
        } else {
            echo "❌ Erreur création : $dir<br>";
        }
    } else {
        echo "📁 Dossier existant : $dir<br>";
    }
}

echo "<br><h3>Structure créée avec succès !</h3>";
?>