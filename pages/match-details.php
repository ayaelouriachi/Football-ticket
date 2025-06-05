<?php
require_once '../config/database.php';
require_once '../classes/Match.php';
require_once '../classes/TicketCategory.php';
require_once '../config/session.php';
require_once '../config/init.php';

SessionManager::init();

// Debug information
error_log("Session ID: " . session_id());
error_log("Session contents: " . print_r($_SESSION, true));

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: matches.php');
    exit;
}

$matchObj = new FootballMatch();
$match = $matchObj->getMatchById($_GET['id']);

if (!$match) {
    header('Location: matches.php');
    exit;
}

// Gérer l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("match-details.php - POST request received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("Session before cart operation: " . print_r($_SESSION, true));
    
    if (isset($_POST['category_id']) && isset($_POST['quantity'])) {
        $cart = new Cart($db, $_SESSION);
        $result = $cart->addItem($_POST['category_id'], $_POST['quantity']);
        
        error_log("Cart operation result: " . print_r($result, true));
        error_log("Session after cart operation: " . print_r($_SESSION, true));
        
        if ($result['success']) {
            $_SESSION['flash']['success'] = $result['message'];
            error_log("Redirecting to cart.php");
            
            // CORRECTION: Utiliser le chemin relatif correct
            // Debug pour identifier le bon chemin
            error_log("Current file path: " . __FILE__);
            error_log("Current directory: " . __DIR__);
            error_log("Looking for cart.php at: " . __DIR__ . '/../cart.php');
            error_log("Cart file exists: " . (file_exists(__DIR__ . '/../cart.php') ? 'YES' : 'NO'));
            
            // Essayer différents chemins possibles
            if (file_exists(__DIR__ . '/../cart.php')) {
                header('Location: ../cart.php');
            } elseif (file_exists(__DIR__ . '/../../cart.php')) {
                header('Location: ../../cart.php');
            } elseif (file_exists(__DIR__ . '/cart.php')) {
                header('Location: cart.php');
            } else {
                // Utiliser une URL absolue basée sur BASE_URL si définie
                if (defined('BASE_URL')) {
                    header('Location: ' . BASE_URL . 'cart.php');
                } else {
                    // Construire l'URL manuellement
                    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                    $host = $_SERVER['HTTP_HOST'];
                    $path = dirname($_SERVER['REQUEST_URI']);
                    
                    // Remonter d'un niveau dans le chemin
                    $cart_url = $protocol . $host . dirname($path) . '/cart.php';
                    error_log("Constructed cart URL: " . $cart_url);
                    header('Location: ' . $cart_url);
                }
            }
            exit;
        } else {
            $_SESSION['flash']['error'] = $result['message'];
            error_log("Staying on match-details.php due to error");
            // Stay on the same page if there's an error
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

$categories = $matchObj->getTicketCategories($_GET['id']);

// Debug après avoir récupéré les données
error_log("Match data: " . print_r($match, true));
error_log("Categories data: " . print_r($categories, true));

if (isset($_GET['debug'])) {
    echo "<pre style='background: #f0f8ff; padding: 15px; margin: 10px; border: 2px solid #0066cc;'>";
    echo "<h3>🔍 DEBUG - Informations du match</h3>";
    echo "<p><strong>ID du match:</strong> " . $_GET['id'] . "</p>";
    echo "<p><strong>Match trouvé:</strong> " . ($match ? 'Oui' : 'Non') . "</p>";
    
    if ($match) {
        echo "\n<strong>Détails du match :</strong>\n";
        print_r($match);
        
        echo "\n<strong>Catégories de billets :</strong>\n";
        print_r($categories);
    }
    
    // Debug pour les chemins
    echo "\n<strong>Debug chemins :</strong>\n";
    echo "Fichier actuel: " . __FILE__ . "\n";
    echo "Répertoire actuel: " . __DIR__ . "\n";
    echo "cart.php existe à ../cart.php: " . (file_exists(__DIR__ . '/../cart.php') ? 'OUI' : 'NON') . "\n";
    echo "cart.php existe à ../../cart.php: " . (file_exists(__DIR__ . '/../../cart.php') ? 'OUI' : 'NON') . "\n";
    echo "cart.php existe à ./cart.php: " . (file_exists(__DIR__ . '/cart.php') ? 'OUI' : 'NON') . "\n";
    
    echo "</pre>";
}

require_once '../includes/header.php';
?>
<style>
    /* Variables CSS pour la cohérence */
:root {
    --primary-color: #2c3e50;
    --secondary-color: #3498db;
    --success-color: #27ae60;
    --warning-color: #f39c12;
    --danger-color: #e74c3c;
    --light-bg: #f8f9fa;
    --white: #ffffff;
    --text-dark: #2c3e50;
    --text-muted: #6c757d;
    --border-color: #dee2e6;
    --shadow: 0 2px 10px rgba(0,0,0,0.1);
    --border-radius: 8px;
}

/* Layout principal */
.main-content {
    background-color: var(--light-bg);
    min-height: 100vh;
    padding: 20px 0;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Breadcrumb */
.breadcrumb {
    display: flex;
    align-items: center;
    margin-bottom: 30px;
    padding: 15px 20px;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.breadcrumb a {
    color: var(--secondary-color);
    text-decoration: none;
    font-weight: 500;
}

.breadcrumb .separator {
    margin: 0 10px;
    color: var(--text-muted);
}

.breadcrumb .current {
    color: var(--text-dark);
    font-weight: 600;
}

/* En-tête du match */
.match-header-detail {
    background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
    color: var(--white);
    border-radius: var(--border-radius);
    padding: 40px;
    margin-bottom: 30px;
    box-shadow: var(--shadow);
}

.competition-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 20px;
    backdrop-filter: blur(10px);
}

.teams-display {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 40px;
    align-items: center;
    margin: 30px 0;
}

.team-detail {
    text-align: center;
}

.team-detail.away {
    text-align: center;
}

.team-logo-large {
    width: 80px;
    height: 80px;
    object-fit: contain;
    margin-bottom: 15px;
    filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
}

.team-name {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.match-center {
    text-align: center;
    border-left: 2px solid rgba(255,255,255,0.3);
    border-right: 2px solid rgba(255,255,255,0.3);
    padding: 0 30px;
}

.match-date-time .date {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 5px;
}

.match-date-time .time {
    font-size: 16px;
    opacity: 0.9;
    margin-bottom: 15px;
}

.vs-text {
    font-size: 28px;
    font-weight: 900;
    letter-spacing: 2px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.stadium-info-detail {
    text-align: center;
    font-size: 16px;
    opacity: 0.9;
    margin-top: 20px;
}

.stadium-info-detail i {
    margin-right: 8px;
}

/* Section billets */
.tickets-section {
    margin-bottom: 40px;
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 30px;
    text-align: center;
}

.tickets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 30px;
}

.ticket-category-card {
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.ticket-category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-color: var(--secondary-color);
}

.ticket-category-card.sold-out {
    opacity: 0.6;
    background: #f8f9fa;
}

.ticket-category-card.sold-out:hover {
    transform: none;
    border-color: transparent;
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-bg);
}

.category-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0;
}

.category-price {
    font-size: 24px;
    font-weight: 900;
    color: var(--secondary-color);
}

.category-description {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 15px;
    line-height: 1.5;
}

.category-availability {
    margin-bottom: 20px;
}

.available {
    color: var(--success-color);
    font-weight: 600;
    font-size: 14px;
}

.sold-out {
    color: var(--danger-color);
    font-weight: 600;
    font-size: 14px;
}

.quantity-selector {
    margin-bottom: 20px;
}

.quantity-selector label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-dark);
}

.quantity-select {
    width: 100%;
    padding: 12px;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 16px;
    background: var(--white);
    transition: border-color 0.3s ease;
}

.quantity-select:focus {
    outline: none;
    border-color: var(--secondary-color);
}

/* Boutons */
.btn {
    display: inline-block;
    padding: 12px 24px;
    border: none;
    border-radius: var(--border-radius);
    font-size: 16px;
    font-weight: 600;
    text-align: center;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    line-height: 1.5;
}

.btn-primary {
    background: var(--secondary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-disabled {
    background: var(--text-muted);
    color: var(--white);
    cursor: not-allowed;
}

.btn-full {
    width: 100%;
}

.btn-outline {
    background: transparent;
    color: var(--secondary-color);
    border: 2px solid var(--secondary-color);
}

.btn-outline:hover {
    background: var(--secondary-color);
    color: var(--white);
}

/* Informations supplémentaires */
.match-details-extra {
    margin-top: 50px;
}

.info-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
}

.info-card {
    background: var(--white);
    padding: 30px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.info-card h4 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--light-bg);
}

.info-card p {
    margin-bottom: 10px;
    line-height: 1.6;
}

.info-card ul {
    list-style: none;
    padding: 0;
}

.info-card li {
    padding: 8px 0;
    border-bottom: 1px solid var(--light-bg);
    position: relative;
    padding-left: 20px;
}

.info-card li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--success-color);
    font-weight: bold;
}

.info-card li:last-child {
    border-bottom: none;
}

/* Message d'absence de billets */
.no-tickets {
    text-align: center;
    padding: 60px 20px;
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.no-tickets p {
    font-size: 18px;
    color: var(--text-muted);
    margin: 0;
}

/* Modal */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--white);
    border-radius: var(--border-radius);
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 2px solid var(--light-bg);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: var(--text-dark);
    font-size: 20px;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-muted);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 30px;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 2px solid var(--light-bg);
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 0 15px;
    }
    
    .match-header-detail {
        padding: 30px 20px;
    }
    
    .teams-display {
        grid-template-columns: 1fr;
        gap: 20px;
        text-align: center;
    }
    
    .match-center {
        border: none;
        padding: 20px 0;
        border-top: 2px solid rgba(255,255,255,0.3);
        border-bottom: 2px solid rgba(255,255,255,0.3);
    }
    
    .team-name {
        font-size: 20px;
    }
    
    .tickets-grid {
        grid-template-columns: 1fr;
    }
    
    .info-cards-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 20px;
    }
    
    .breadcrumb {
        flex-wrap: wrap;
        padding: 15px;
    }
    
    .breadcrumb .separator {
        margin: 0 5px;
    }
}

@media (max-width: 480px) {
    .match-header-detail {
        padding: 20px 15px;
    }
    
    .section-title {
        font-size: 24px;
    }
    
    .ticket-category-card {
        padding: 20px;
    }
    
    .category-name {
        font-size: 18px;
    }
    
    .category-price {
        font-size: 20px;
    }
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.ticket-category-card {
    animation: fadeIn 0.6s ease forwards;
}

.ticket-category-card:nth-child(2) { animation-delay: 0.1s; }
.ticket-category-card:nth-child(3) { animation-delay: 0.2s; }
.ticket-category-card:nth-child(4) { animation-delay: 0.3s; }

/* Styles pour les cartes de catégories de billets */
.ticket-categories {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.ticket-category-card {
    background: var(--white);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    padding: 20px;
    transition: transform 0.2s;
}

.ticket-category-card:hover {
    transform: translateY(-2px);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}

.category-header h3 {
    margin: 0;
    font-size: 18px;
    color: var(--text-dark);
}

.price {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-color);
}

.availability {
    margin-bottom: 20px;
}

.progress {
    height: 8px;
    background: var(--border-color);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-bar {
    height: 100%;
    background: var(--success-color);
    transition: width 0.3s ease;
}

.remaining {
    font-size: 14px;
    color: var(--text-muted);
}

.ticket-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.quantity-selector label {
    font-size: 14px;
    color: var(--text-dark);
}

.form-control {
    padding: 8px;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    width: 80px;
}

.sold-out {
    background: var(--danger-color);
    color: var(--white);
    text-align: center;
    padding: 10px;
    border-radius: 4px;
    font-weight: 600;
}

/* Styles pour les informations supplémentaires */
.additional-info {
    margin-top: 40px;
    background: var(--white);
    border-radius: var(--border-radius);
    padding: 30px;
    box-shadow: var(--shadow);
}

.stadium-details h3 {
    margin-top: 0;
    margin-bottom: 20px;
    color: var(--text-dark);
}

.stadium-details p {
    margin: 10px 0;
    color: var(--text-muted);
}

.stadium-details i {
    margin-right: 10px;
    color: var(--secondary-color);
}

/* Styles pour les boutons */
.btn {
    display: inline-block;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background: var(--secondary-color);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: var(--white);
}
</style>
<main class="main-content">
    <div class="container">
        <!-- Fil d'Ariane -->
        <div class="breadcrumb">
            <a href="../index.php">Accueil</a>
            <span class="separator">/</span>
            <a href="matches.php">Matchs</a>
            <span class="separator">/</span>
            <span class="current"><?= htmlspecialchars($match['home_team_name']) ?> vs <?= htmlspecialchars($match['away_team_name']) ?></span>
        </div>

        <!-- En-tête du match -->
        <div class="match-header-detail">
            <span class="competition-badge"><?= htmlspecialchars($match['competition']) ?></span>
            
            <div class="teams-display">
                <div class="team-detail">
                    <img src="<?= htmlspecialchars($match['home_team_logo'] ?? '../assets/images/default-team.png') ?>" 
                         alt="<?= htmlspecialchars($match['home_team_name']) ?>" 
                         class="team-logo-large"
                         onerror="this.src='../assets/images/default-team.png'">
                    <h2 class="team-name"><?= htmlspecialchars($match['home_team_name']) ?></h2>
                </div>
                
                <div class="match-center">
                    <div class="match-date-time">
                        <div class="date"><?= date('d M Y', strtotime($match['match_date'])) ?></div>
                        <div class="time"><?= date('H:i', strtotime($match['match_date'])) ?></div>
                    </div>
                    <div class="vs-text">VS</div>
                </div>
                
                <div class="team-detail away">
                    <img src="<?= htmlspecialchars($match['away_team_logo'] ?? '../assets/images/default-team.png') ?>" 
                         alt="<?= htmlspecialchars($match['away_team_name']) ?>" 
                         class="team-logo-large"
                         onerror="this.src='../assets/images/default-team.png'">
                    <h2 class="team-name"><?= htmlspecialchars($match['away_team_name']) ?></h2>
                </div>
            </div>
            
            <div class="stadium-info-detail">
                <i class="fas fa-map-marker-alt"></i>
                <?= htmlspecialchars($match['stadium_name']) ?>, <?= htmlspecialchars($match['stadium_city']) ?>
            </div>
        </div>

        <!-- Section des billets -->
        <div class="tickets-section">
            <h2>Catégories de billets disponibles</h2>
            
            <?php if (empty($categories)): ?>
            <div class="no-tickets">
                <p>Aucun billet n'est disponible pour le moment.</p>
            </div>
            <?php else: ?>
            <div class="ticket-categories">
                <?php foreach ($categories as $category): ?>
                <div class="ticket-category-card">
                    <div class="category-header">
                        <h3><?= htmlspecialchars($category['name']) ?></h3>
                        <span class="price"><?= number_format($category['price'], 2) ?> MAD</span>
                    </div>
                    
                    <div class="category-details">
                        <div class="availability">
                            <div class="progress">
                                <div class="progress-bar" style="width: <?= $category['sold_percentage'] ?>%"></div>
                            </div>
                            <span class="remaining">
                                <?= $category['remaining_tickets'] ?> places restantes
                            </span>
                        </div>
                        
                        <?php if ($category['remaining_tickets'] > 0): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $match['id']; ?>" class="ticket-form">
                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                            <div class="quantity-selector">
                                <label for="quantity-<?php echo $category['id']; ?>">Quantité:</label>
                                <select name="quantity" id="quantity-<?php echo $category['id']; ?>" class="form-control">
                                    <?php for ($i = 1; $i <= min(10, $category['remaining_tickets']); $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Ajouter au panier
                            </button>
                        </form>
                        <?php else: ?>
                        <div class="sold-out">
                            Complet
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Informations supplémentaires -->
        <div class="additional-info">
            <div class="stadium-details">
                <h3>Informations sur lejj stade</h3>
                <p><i class="fas fa-map-marked-alt"></i> <?= htmlspecialchars($match['address']) ?></p>
                <p><i class="fas fa-users"></i> Capacité: <?= number_format($match['capacity']) ?> places</p>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>