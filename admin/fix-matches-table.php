<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/init.php');

echo "<h2>Correction de la table matches</h2>";

try {
    // 1. Vérifier la structure actuelle
    echo "<h3>1. Structure actuelle</h3>";
    $columns = $db->query("SHOW COLUMNS FROM matches")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";

    // 2. Vérifier les noms des colonnes pour les équipes
    $hasTeam1 = false;
    $hasTeam2 = false;
    $hasHomeTeam = false;
    $hasAwayTeam = false;

    foreach ($columns as $column) {
        if ($column['Field'] === 'team1_id') $hasTeam1 = true;
        if ($column['Field'] === 'team2_id') $hasTeam2 = true;
        if ($column['Field'] === 'home_team_id') $hasHomeTeam = true;
        if ($column['Field'] === 'away_team_id') $hasAwayTeam = true;
    }

    // 3. Corriger la structure si nécessaire
    if ($hasTeam1 && $hasTeam2 && !$hasHomeTeam && !$hasAwayTeam) {
        // Renommer team1_id en home_team_id et team2_id en away_team_id
        echo "<h3>2. Renommage des colonnes</h3>";
        
        try {
            // Vérifier les clés étrangères
            $foreignKeys = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'matches' 
                AND (COLUMN_NAME = 'team1_id' OR COLUMN_NAME = 'team2_id')
                AND CONSTRAINT_NAME != 'PRIMARY'
            ")->fetchAll(PDO::FETCH_COLUMN);

            // Supprimer les clés étrangères existantes
            foreach ($foreignKeys as $fk) {
                $db->exec("ALTER TABLE matches DROP FOREIGN KEY `{$fk}`");
                echo "✅ Clé étrangère supprimée : {$fk}<br>";
            }

            // Renommer les colonnes
            $db->exec("ALTER TABLE matches 
                      CHANGE COLUMN team1_id home_team_id INT NOT NULL,
                      CHANGE COLUMN team2_id away_team_id INT NOT NULL");
            echo "✅ Colonnes renommées<br>";

            // Recréer les clés étrangères
            $db->exec("ALTER TABLE matches 
                      ADD CONSTRAINT fk_matches_home_team 
                      FOREIGN KEY (home_team_id) REFERENCES teams(id)");
            $db->exec("ALTER TABLE matches 
                      ADD CONSTRAINT fk_matches_away_team 
                      FOREIGN KEY (away_team_id) REFERENCES teams(id)");
            echo "✅ Nouvelles clés étrangères créées<br>";

            echo "✅ Mise à jour réussie<br>";

        } catch (Exception $e) {
            throw $e;
        }
    } elseif ($hasHomeTeam && $hasAwayTeam && !$hasTeam1 && !$hasTeam2) {
        echo "✅ La structure est déjà correcte (utilise home_team_id et away_team_id)<br>";
    } elseif ($hasTeam1 && $hasTeam2 && $hasHomeTeam && $hasAwayTeam) {
        echo "⚠️ La table contient les deux ensembles de colonnes. Nettoyage nécessaire.<br>";
    } else {
        echo "❌ Structure inattendue. Vérification manuelle requise.<br>";
    }

    // 4. Vérifier les données
    echo "<h3>3. Vérification des données</h3>";
    $stmt = $db->query("
        SELECT 
            m.id,
            m.match_date,
            COALESCE(t1.name, 'ÉQUIPE MANQUANTE') as home_team,
            COALESCE(t2.name, 'ÉQUIPE MANQUANTE') as away_team,
            COALESCE(s.name, 'STADE MANQUANT') as stadium
        FROM matches m
        LEFT JOIN teams t1 ON m.home_team_id = t1.id
        LEFT JOIN teams t2 ON m.away_team_id = t2.id
        LEFT JOIN stadiums s ON m.stadium_id = s.id
        ORDER BY m.match_date DESC
        LIMIT 5
    ");
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($matches);
    echo "</pre>";

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<strong>Erreur :</strong><br>";
    echo "Message : " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    echo "</div>";
}
?> 