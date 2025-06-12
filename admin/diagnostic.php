<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__ . '/../config/init.php');

echo "<h2>Diagnostic du système</h2>";

try {
    echo "<h3>1. Test de connexion à la base de données</h3>";
    $db->query("SELECT 1");
    echo "✅ Connexion à la base de données réussie<br><br>";

    echo "<h3>2. Vérification des tables</h3>";
    $tables = ['matches', 'teams', 'stadiums', 'ticket_categories', 'orders', 'order_items'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'")->rowCount();
        if ($result > 0) {
            echo "✅ Table '$table' existe<br>";
            // Afficher la structure de la table
            $columns = $db->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>Structure de la table :\n";
            print_r($columns);
            echo "</pre><br>";
        } else {
            echo "❌ Table '$table' n'existe pas<br><br>";
        }
    }

    echo "<h3>3. Test des requêtes principales</h3>";
    
    // Test de la requête de comptage
    echo "<strong>Requête de comptage :</strong><br>";
    $countSql = "SELECT COUNT(*) as total FROM matches m
                 LEFT JOIN teams t1 ON m.team1_id = t1.id
                 LEFT JOIN teams t2 ON m.team2_id = t2.id
                 LEFT JOIN stadiums s ON m.stadium_id = s.id";
    $stmt = $db->prepare($countSql);
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    echo "Nombre total de matchs : $total<br><br>";

    // Test de la requête principale avec EXPLAIN
    echo "<strong>Analyse de la requête principale :</strong><br>";
    $sql = "SELECT 
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
            LIMIT 1";
    
    echo "<pre>Plan d'exécution :\n";
    $explain = $db->query("EXPLAIN " . $sql)->fetchAll(PDO::FETCH_ASSOC);
    print_r($explain);
    echo "</pre><br>";

    // Test d'exécution de la requête
    echo "<strong>Résultat de la requête :</strong><br>";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($result);
    echo "</pre>";

    echo "<h3>4. Vérification des relations</h3>";
    
    // Vérifier les équipes orphelines
    $orphanTeams = $db->query("
        SELECT m.id, m.team1_id, m.team2_id 
        FROM matches m 
        LEFT JOIN teams t1 ON m.team1_id = t1.id 
        LEFT JOIN teams t2 ON m.team2_id = t2.id 
        WHERE t1.id IS NULL OR t2.id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($orphanTeams)) {
        echo "❌ Matchs avec des équipes manquantes :<br>";
        print_r($orphanTeams);
    } else {
        echo "✅ Toutes les relations équipes sont valides<br>";
    }

    // Vérifier les stades orphelins
    $orphanStadiums = $db->query("
        SELECT m.id, m.stadium_id 
        FROM matches m 
        LEFT JOIN stadiums s ON m.stadium_id = s.id 
        WHERE s.id IS NULL AND m.stadium_id IS NOT NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($orphanStadiums)) {
        echo "❌ Matchs avec des stades manquants :<br>";
        print_r($orphanStadiums);
    } else {
        echo "✅ Toutes les relations stades sont valides<br>";
    }

} catch (PDOException $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<strong>Erreur PDO :</strong><br>";
    echo "Message : " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
    echo "<strong>Erreur :</strong><br>";
    echo "Message : " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    echo "</div>";
}
?> 