<?php
require_once 'config/database.php';
require_once 'classes/Match.php';

// À ajouter TEMPORAIREMENT au début de matches.php après les require

$db = Database::getInstance()->getConnection();

// 1. Vérifier les matchs
$stmt = $db->query("SELECT COUNT(*) as total FROM matches");
$totalMatches = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as future FROM matches WHERE match_date > NOW()");
$futureMatches = $stmt->fetch()['future'];

// 2. Requête corrigée avec home_team_id et away_team_id
$stmt = $db->query("
    SELECT m.*, m.competition,
           t1.name as home_team_name, t1.logo as home_team_logo,
           t2.name as away_team_name, t2.logo as away_team_logo,
           s.name as stadium_name,
           COUNT(DISTINCT o.id) as order_count,
           SUM(oi.quantity) as tickets_sold,
           (SELECT SUM(tc.capacity) FROM ticket_categories tc WHERE tc.match_id = m.id) as total_capacity,
           (SELECT MIN(tc.price) FROM ticket_categories tc WHERE tc.match_id = m.id) as min_price
    FROM matches m
    LEFT JOIN teams t1 ON m.home_team_id = t1.id
    LEFT JOIN teams t2 ON m.away_team_id = t2.id
    LEFT JOIN stadiums s ON m.stadium_id = s.id
    LEFT JOIN ticket_categories tc ON tc.match_id = m.id
    LEFT JOIN order_items oi ON oi.ticket_category_id = tc.id
    LEFT JOIN orders o ON o.id = oi.order_id
    WHERE m.match_date > NOW()
    GROUP BY m.id, m.competition, m.match_date, m.home_team_id, m.away_team_id, m.stadium_id,
             t1.name, t1.logo, t2.name, t2.logo, s.name
    ORDER BY m.match_date DESC
    LIMIT 10
");
$testResults = $stmt->fetchAll();

// Affichage debug
echo "<div style='background: #f0f8ff; padding: 15px; margin: 10px; border: 2px solid #0066cc; font-family: monospace;'>";
echo "<h3>🔍 DIAGNOSTIC DE LA BASE DE DONNÉES</h3>";
echo "<p><strong>Total matchs:</strong> $totalMatches</p>";
echo "<p><strong>Matchs futurs:</strong> $futureMatches</p>";
echo "<p><strong>Résultats de la requête complète:</strong> " . count($testResults) . "</p>";

if (!empty($testResults)) {
    echo "<h4>Exemple de match trouvé:</h4>";
    $match = $testResults[0];
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $match['id'] . "</li>";
    echo "<li><strong>Date:</strong> " . $match['match_date'] . "</li>";
    echo "<li><strong>Compétition:</strong> " . ($match['competition'] ?? 'NULL') . "</li>";
    echo "<li><strong>Équipe domicile:</strong> " . ($match['home_team_name'] ?? 'NULL') . "</li>";
    echo "<li><strong>Équipe visiteur:</strong> " . ($match['away_team_name'] ?? 'NULL') . "</li>";
    echo "<li><strong>Stade:</strong> " . ($match['stadium_name'] ?? 'NULL') . "</li>";
    echo "</ul>";
} else {
    echo "<p style='color: red;'><strong>❌ Aucun résultat trouvé avec la requête complète</strong></p>";
    
    // Test sans jointures
    $stmt = $db->query("SELECT * FROM matches WHERE match_date > NOW() LIMIT 5");
    $simpleResults = $stmt->fetchAll();
    echo "<p><strong>Test sans jointures:</strong> " . count($simpleResults) . " résultats</p>";
    
    if (!empty($simpleResults)) {
        echo "<p style='color: orange;'>⚠️ Les matchs existent mais il y a un problème avec les jointures</p>";
        $match = $simpleResults[0];
        echo "<p>Exemple: Match ID " . $match['id'] . " - Date: " . $match['match_date'] . "</p>";
    }
}

echo "</div>";

$matchObj = new FootballMatch();

// 🔍 DEBUG: Afficher les paramètres reçus
echo "<!-- DEBUG: GET parameters -->";
echo "<!-- Competition: " . ($_GET['competition'] ?? 'none') . " -->";

// Préparation des filtres
$filters = [];
$competition = $_GET['competition'] ?? '';

if ($competition) {
    $filters['competition'] = $competition;
    echo "<!-- DEBUG: Filter applied for competition: $competition -->";
}

// 🔍 DEBUG: Tester d'abord sans filtre
$allMatches = $matchObj->getAllMatches(1, 50, []);
echo "<!-- DEBUG: Total matches without filter: " . count($allMatches) . " -->";

// Récupération des matchs avec filtres
$matches = $matchObj->getAllMatches(1, 50, $filters);
echo "<!-- DEBUG: Matches with filter: " . count($matches) . " -->";

// 🔍 DEBUG: Afficher la requête SQL (temporaire)
if ($competition) {
    echo "<!-- DEBUG: Looking for competition: '$competition' -->";
}

require_once 'includes/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Tous les matchs</h1>
            <p>Découvrez tous les matchs disponibles à la réservation</p>
        </div>

        <!-- 🔍 DEBUG INFO (à supprimer en production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-size: 12px;">
            <strong>DEBUG INFO:</strong><br>
            Competition filter: <?= htmlspecialchars($competition ?: 'none') ?><br>
            Total matches found: <?= count($matches) ?><br>
            Filters applied: <?= json_encode($filters) ?>
        </div>
        <?php endif; ?>

        <!-- Filtres -->
        <div class="filters-section">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label for="competition">Compétition :</label>
                    <select name="competition" id="competition" class="filter-select">
                        <option value="">Toutes les compétitions</option>
                        <option value="Botola Pro" <?= $competition === 'Botola Pro' ? 'selected' : '' ?>>Botola Pro</option>
                        <option value="Coupe du Trône" <?= $competition === 'Coupe du Trône' ? 'selected' : '' ?>>Coupe du Trône</option>
                        <option value="Champions League" <?= $competition === 'Champions League' ? 'selected' : '' ?>>Champions League</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filtrer</button>
                
                <?php if ($competition): ?>
                <a href="matches.php" class="btn btn-outline">Réinitialiser</a>
                <?php endif; ?>
                
                <!-- Lien de debug -->
                <a href="?<?= $_SERVER['QUERY_STRING'] ?>&debug=1" style="margin-left: 10px; font-size: 12px;">Debug</a>
            </form>
        </div>

        <!-- Liste des matchs -->
        <div class="matches-list">
            <?php if (empty($matches)): ?>
            <div class="no-matches">
                <p>Aucun match trouvé pour cette compétition.</p>
                <a href="matches.php" class="btn btn-primary">Voir tous les matchs</a>
            </div>
            <?php else: ?>
            <?php foreach ($matches as $match): ?>
            <div class="match-card">
                <div class="teams">
                    <div class="team home">
                        <img src="assets/images/teams/<?= $match['home_team_id'] ?>.png" 
                             alt="<?= htmlspecialchars($match['home_team_name']) ?>"
                             class="team-logo"
                             onerror="this.src='assets/images/default-team.png'">
                        <h3><?= htmlspecialchars($match['home_team_name']) ?></h3>
                    </div>
                    <div class="vs">VS</div>
                    <div class="team away">
                        <img src="assets/images/teams/<?= $match['away_team_id'] ?>.png"
                             alt="<?= htmlspecialchars($match['away_team_name']) ?>"
                             class="team-logo"
                             onerror="this.src='assets/images/default-team.png'">
                        <h3><?= htmlspecialchars($match['away_team_name']) ?></h3>
                    </div>
                </div>
                
                <div class="match-content">
                    <div class="match-header">
                        <span class="competition-tag"><?= htmlspecialchars($match['competition'] ?? 'N/A') ?></span>
                        <span class="match-time"><?= date('H:i', strtotime($match['match_date'])) ?></span>
                    </div>
                    
                    <div class="match-venue">
                        <i class="icon-location"></i>
                        <?= htmlspecialchars($match['stadium_name'] ?? 'N/A') ?>
                    </div>
                </div>
                
                <div class="match-actions">
                    <div class="price-from">
                        À partir de<br>
                        <strong><?= isset($match['min_price']) ? number_format($match['min_price'], 2) . ' MAD' : 'Prix à définir' ?></strong>
                    </div>
                    <a href="match-details.php?id=<?= $match['id'] ?>" class="btn btn-primary">
                        Réserver
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>