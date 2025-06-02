<?php
require_once __DIR__ . '/../config/init.php';

echo "Ajout des catégories de billets...\n\n";

try {
    // Récupérer l'ID du match test
    $stmt = $db->query("SELECT id FROM matches WHERE title = 'Test Match' LIMIT 1");
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match test non trouvé");
    }
    
    $matchId = $match['id'];
    
    // Supprimer les catégories existantes pour ce match
    $db->prepare("DELETE FROM ticket_categories WHERE match_id = ?")->execute([$matchId]);
    
    // Définir les catégories de billets
    $categories = [
        [
            'name' => 'VIP',
            'price' => 1000.00,
            'capacity' => 100,
            'description' => 'Places VIP avec accès au salon privé et service de restauration inclus'
        ],
        [
            'name' => 'Tribune Principale',
            'price' => 500.00,
            'capacity' => 500,
            'description' => 'Places centrales avec une vue parfaite sur le terrain'
        ],
        [
            'name' => 'Tribune Latérale',
            'price' => 300.00,
            'capacity' => 1000,
            'description' => 'Places avec une bonne vue sur le terrain'
        ],
        [
            'name' => 'Virage',
            'price' => 150.00,
            'capacity' => 2000,
            'description' => 'Places derrière les buts, ambiance garantie'
        ]
    ];
    
    // Insérer les nouvelles catégories
    $stmt = $db->prepare("
        INSERT INTO ticket_categories 
        (match_id, name, price, capacity, remaining_tickets, description) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($categories as $category) {
        $stmt->execute([
            $matchId,
            $category['name'],
            $category['price'],
            $category['capacity'],
            $category['capacity'], // Au début, remaining_tickets = capacity
            $category['description']
        ]);
        echo "✅ Catégorie '{$category['name']}' ajoutée\n";
    }
    
    echo "\n✅ Toutes les catégories ont été ajoutées avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de l'ajout des catégories: " . $e->getMessage() . "\n";
    exit(1);
} 