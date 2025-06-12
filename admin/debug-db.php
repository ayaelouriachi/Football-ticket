<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/init.php');

echo "<h2>Diagnostic de la base de données</h2>";

try {
    // Vérifier les tables nécessaires
    $requiredTables = [
        'matches' => [
            'id',
            'match_date',
            'team1_id',
            'team2_id',
            'stadium_id',
            'status'
        ],
        'teams' => [
            'id',
            'name',
            'logo'
        ],
        'stadiums' => [
            'id',
            'name',
            'city'
        ],
        'ticket_categories' => [
            'id',
            'match_id',
            'name',
            'capacity',
            'price'
        ]
    ];

    echo "<h3>Vérification des tables</h3>";
    foreach ($requiredTables as $table => $columns) {
        // Vérifier si la table existe
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' existe<br>";
            
            // Vérifier les colonnes
            $stmt = $db->query("SHOW COLUMNS FROM $table");
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $missingColumns = array_diff($columns, $existingColumns);
            if (empty($missingColumns)) {
                echo "✅ Toutes les colonnes requises sont présentes<br>";
            } else {
                echo "❌ Colonnes manquantes : " . implode(', ', $missingColumns) . "<br>";
            }
            
            // Compter les enregistrements
            $stmt = $db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "📊 Nombre d'enregistrements : $count<br>";
            
            // Afficher quelques exemples
            if ($count > 0) {
                $stmt = $db->query("SELECT * FROM $table LIMIT 1");
                $example = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "📝 Exemple d'enregistrement :<br>";
                echo "<pre>" . print_r($example, true) . "</pre>";
            }
        } else {
            echo "❌ Table '$table' n'existe pas<br>";
        }
        echo "<hr>";
    }

    // Vérifier les relations
    echo "<h3>Vérification des relations</h3>";
    
    // Matches -> Teams (team1_id)
    $stmt = $db->query("
        SELECT COUNT(*) FROM matches m 
        LEFT JOIN teams t1 ON m.team1_id = t1.id 
        WHERE t1.id IS NULL AND m.team1_id IS NOT NULL
    ");
    $orphanedTeam1 = $stmt->fetchColumn();
    if ($orphanedTeam1 > 0) {
        echo "❌ $orphanedTeam1 match(es) avec team1_id invalide<br>";
    } else {
        echo "✅ Relation matches.team1_id -> teams.id OK<br>";
    }

    // Matches -> Teams (team2_id)
    $stmt = $db->query("
        SELECT COUNT(*) FROM matches m 
        LEFT JOIN teams t2 ON m.team2_id = t2.id 
        WHERE t2.id IS NULL AND m.team2_id IS NOT NULL
    ");
    $orphanedTeam2 = $stmt->fetchColumn();
    if ($orphanedTeam2 > 0) {
        echo "❌ $orphanedTeam2 match(es) avec team2_id invalide<br>";
    } else {
        echo "✅ Relation matches.team2_id -> teams.id OK<br>";
    }

    // Matches -> Stadiums
    $stmt = $db->query("
        SELECT COUNT(*) FROM matches m 
        LEFT JOIN stadiums s ON m.stadium_id = s.id 
        WHERE s.id IS NULL AND m.stadium_id IS NOT NULL
    ");
    $orphanedStadium = $stmt->fetchColumn();
    if ($orphanedStadium > 0) {
        echo "❌ $orphanedStadium match(es) avec stadium_id invalide<br>";
    } else {
        echo "✅ Relation matches.stadium_id -> stadiums.id OK<br>";
    }

    // Ticket Categories -> Matches
    $stmt = $db->query("
        SELECT COUNT(*) FROM ticket_categories tc 
        LEFT JOIN matches m ON tc.match_id = m.id 
        WHERE m.id IS NULL
    ");
    $orphanedCategories = $stmt->fetchColumn();
    if ($orphanedCategories > 0) {
        echo "❌ $orphanedCategories catégorie(s) de billets avec match_id invalide<br>";
    } else {
        echo "✅ Relation ticket_categories.match_id -> matches.id OK<br>";
    }

    echo "<hr>";
    
    // Afficher la requête complète pour debug
    echo "<h3>Test de la requête complète</h3>";
    $stmt = $db->query("
        SELECT 
            m.*,
            t1.name as home_team,
            t1.logo as home_team_logo,
            t2.name as away_team,
            t2.logo as away_team_logo,
            s.name as stadium,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.match_id = m.id) as tickets_sold,
            (SELECT SUM(tc.capacity) FROM ticket_categories tc WHERE tc.match_id = m.id) as total_capacity
        FROM matches m
        LEFT JOIN teams t1 ON m.team1_id = t1.id
        LEFT JOIN teams t2 ON m.team2_id = t2.id
        LEFT JOIN stadiums s ON m.stadium_id = s.id
        ORDER BY m.match_date DESC
        LIMIT 1
    ");
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "✅ La requête fonctionne<br>";
        echo "📝 Exemple de résultat :<br>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "❌ La requête ne retourne aucun résultat<br>";
    }

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<strong>Erreur :</strong><br>";
    echo "Message : " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    echo "</div>";
}
?> 