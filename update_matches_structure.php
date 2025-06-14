<?php
require_once 'includes/config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mettre à jour la table matches
    $pdo->exec("ALTER TABLE matches 
        CHANGE team1_id home_team_id INT NOT NULL,
        CHANGE team2_id away_team_id INT NOT NULL,
        ADD COLUMN ticket_price DECIMAL(10,2) NOT NULL AFTER match_date,
        ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER ticket_price,
        MODIFY match_date DATE NOT NULL,
        ADD COLUMN match_time TIME NOT NULL AFTER match_date,
        DROP FOREIGN KEY matches_ibfk_3,
        DROP COLUMN stadium_id,
        DROP COLUMN title,
        DROP COLUMN competition
    ");
    
    echo "✅ Structure de la table matches mise à jour avec succès!\n";
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
} 