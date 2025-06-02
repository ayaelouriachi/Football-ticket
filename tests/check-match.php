<?php
require_once __DIR__ . '/../config/init.php';

echo "Vérification des données du match...\n\n";

try {
    // 1. Vérifier les matchs
    $stmt = $db->query("SELECT * FROM matches");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Matchs trouvés : " . count($matches) . "\n";
    foreach ($matches as $match) {
        echo "\nMatch ID: " . $match['id'] . "\n";
        echo "Titre: " . $match['title'] . "\n";
        echo "Date: " . $match['match_date'] . "\n";
        echo "Compétition: " . $match['competition'] . "\n";
        
        // 2. Vérifier les catégories pour ce match
        $stmt = $db->prepare("SELECT * FROM ticket_categories WHERE match_id = ?");
        $stmt->execute([$match['id']]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nCatégories de billets pour ce match : " . count($categories) . "\n";
        foreach ($categories as $cat) {
            echo "- " . $cat['name'] . " : " . $cat['remaining_tickets'] . "/" . $cat['capacity'] . " places à " . $cat['price'] . " MAD\n";
        }
        echo "\n" . str_repeat("-", 50) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
} 