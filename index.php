<?php
require_once 'config/database.php';
require_once 'classes/Match.php';
require_once 'includes/header.php';

$matchObj = new FootballMatch();
$upcomingMatches = $matchObj->getAllMatches(6);
?>

<main class="main-content">
    <!-- Section Hero -->
    <section class="hero-section">
        <div class="hero-overlay">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Réservez vos billets de football</h1>
                    <p class="hero-subtitle">Les meilleurs matchs du championnat marocain</p>
                    <a href="matches.php" class="btn btn-primary btn-large">
                        Voir tous les matchs
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Matchs à venir -->
    <section class="matches-section">
        <div class="container">
            <h2 class="section-title">Matchs à venir</h2>
            <div class="matches-grid">
                <?php foreach ($upcomingMatches as $match): ?>
                <div class="match-card">
                    <div class="match-header">
                        <span class="match-competition"><?= htmlspecialchars($match['competition']) ?></span>
                        <span class="match-date"><?= date('d/m/Y à H:i', strtotime($match['match_date'])) ?></span>
                    </div>
                    
                    <div class="match-teams">
                        <div class="team home-team">
                            <img src="assets/images/teams/<?= $match['home_team_id'] ?>.png" 
                                 alt="<?= htmlspecialchars($match['home_team']) ?>" 
                                 class="team-logo" 
                                 onerror="this.src='assets/images/default-team.png'">
                            <span class="team-name"><?= htmlspecialchars($match['home_team']) ?></span>
                        </div>
                        
                        <div class="match-vs">VS</div>
                        
                        <div class="team away-team">
                            <img src="assets/images/teams/<?= $match['away_team_id'] ?>.png" 
                                 alt="<?= htmlspecialchars($match['away_team']) ?>" 
                                 class="team-logo"
                                 onerror="this.src='assets/images/default-team.png'">
                            <span class="team-name"><?= htmlspecialchars($match['away_team']) ?></span>
                        </div>
                    </div>
                    
                    <div class="match-info">
                        <div class="stadium-info">
                            <i class="icon-location"></i>
                            <span><?= htmlspecialchars($match['stadium_name']) ?>, <?= htmlspecialchars($match['city']) ?></span>
                        </div>
                        
                        <div class="price-info">
                            À partir de <strong><?= number_format($match['min_price'], 2) ?> MAD</strong>
                        </div>
                    </div>
                    
                    <div class="match-actions">
                        <a href="match-details.php?id=<?= $match['id'] ?>" class="btn btn-primary btn-full">
                            Réserver maintenant
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (count($upcomingMatches) >= 6): ?>
            <div class="text-center">
                <a href="matches.php" class="btn btn-outline">Voir tous les matchs</a>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Section Avantages -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Pourquoi choisir notre plateforme ?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-shield"></i>
                    </div>
                    <h3 class="feature-title">Paiement sécurisé</h3>
                    <p class="feature-description">Transactions 100% sécurisées avec PayPal</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-ticket"></i>
                    </div>
                    <h3 class="feature-title">Billets instantanés</h3>
                    <p class="feature-description">Recevez vos billets immédiatement par email</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="icon-support"></i>
                    </div>
                    <h3 class="feature-title">Support 24/7</h3>
                    <p class="feature-description">Notre équipe est là pour vous aider</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>