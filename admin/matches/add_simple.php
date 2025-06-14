<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- DEBUG: Début du fichier -->\n";

// Test des inclusions une par une
$includes = [
    'init' => __DIR__ . '/../../config/init.php',
    'auth' => __DIR__ . '/../includes/auth.php',
    'config' => __DIR__ . '/../includes/config.php',
    'layout' => __DIR__ . '/../includes/layout.php'
];

foreach ($includes as $name => $path) {
    echo "\nTest inclusion $name ($path):\n";
    if (file_exists($path)) {
        echo "Le fichier $name existe\n";
        try {
            require_once($path);
            echo "$name inclus avec succès\n";
        } catch (Exception $e) {
            echo "ERREUR lors de l'inclusion de $name: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ERREUR: $name n'existe pas\n";
    }
}

// Test de la connexion DB
echo "\nTest de la connexion DB:\n";
if (isset($db) && $db instanceof PDO) {
    echo "Connexion DB OK\n";
    
    // Test requête simple
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM teams");
        $count = $stmt->fetchColumn();
        echo "Nombre d'équipes: $count\n";
    } catch (PDOException $e) {
        echo "ERREUR DB: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERREUR: Pas de connexion DB\n";
}

// Test de l'authentification
echo "\nTest de l'authentification:\n";
if (isset($auth)) {
    echo "Classe Auth disponible\n";
    if ($auth->isLoggedIn()) {
        echo "Utilisateur connecté\n";
    } else {
        echo "Utilisateur non connecté\n";
    }
} else {
    echo "ERREUR: Auth non disponible\n";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Ajout Match</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light">
    <div class="container mt-5">
        <h1>Test Ajout Match (Version Simple)</h1>
        
        <form method="POST" class="mt-4">
            <div class="mb-3">
                <label class="form-label">Titre du match</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Équipe domicile</label>
                <select name="home_team_id" class="form-select" required>
                    <option value="">Sélectionner une équipe</option>
                    <?php
                    try {
                        $teams = $db->query("SELECT id, name FROM teams ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($teams as $team) {
                            echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['name']) . '</option>';
                        }
                    } catch (Exception $e) {
                        echo '<option value="">Erreur: ' . $e->getMessage() . '</option>';
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Équipe extérieur</label>
                <select name="away_team_id" class="form-select" required>
                    <option value="">Sélectionner une équipe</option>
                    <?php
                    if (isset($teams)) {
                        foreach ($teams as $team) {
                            echo '<option value="' . $team['id'] . '">' . htmlspecialchars($team['name']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Date</label>
                <input type="date" name="match_date" class="form-control" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Heure</label>
                <input type="time" name="match_time" class="form-control" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Tester l'ajout</button>
        </form>
        
        <?php
        // Test du traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "<pre class='mt-4'>";
            echo "Données reçues :\n";
            print_r($_POST);
            echo "</pre>";
        }
        ?>
    </div>
</body>
</html> 