<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Ajouter des équipes
    $teams = [
        ['Raja Casablanca', 'raja.png'],
        ['Wydad Casablanca', 'wydad.png'],
        ['FAR Rabat', 'far.png'],
        ['RS Berkane', 'rsb.png']
    ];
    
    foreach ($teams as $team) {
        $stmt = $db->prepare("INSERT INTO teams (name, logo) VALUES (?, ?)");
        $stmt->execute($team);
    }
    
    // Ajouter des stades
    $stadiums = [
        ['Stade Mohammed V', 'Casablanca', 'Boulevard Zerktouni', 45000],
        ['Complexe Moulay Abdallah', 'Rabat', 'Avenue Hassan II', 53000],
        ['Stade Municipal de Berkane', 'Berkane', 'Avenue Mohammed V', 15000]
    ];
    
    foreach ($stadiums as $stadium) {
        $stmt = $db->prepare("INSERT INTO stadiums (name, city, address, capacity) VALUES (?, ?, ?, ?)");
        $stmt->execute($stadium);
    }
    
    // Ajouter des matchs
    $matches = [
        ['Raja vs Wydad', 1, 2, 1, '2024-03-15 20:00:00', 'Botola Pro'],
        ['FAR vs RS Berkane', 3, 4, 2, '2024-03-20 19:00:00', 'Coupe du Trône']
    ];
    
    foreach ($matches as $match) {
        $stmt = $db->prepare("INSERT INTO matches (title, team1_id, team2_id, stadium_id, match_date, competition) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute($match);
    }
    
    // Ajouter des catégories de billets
    $categories = [
        [1, 'Tribune Nord', 'Place assise en tribune nord', 150.00],
        [1, 'Tribune Sud', 'Place assise en tribune sud', 150.00],
        [1, 'Virage', 'Place debout en virage', 100.00],
        [2, 'Tribune Centrale', 'Place assise en tribune centrale', 200.00],
        [2, 'Virage', 'Place debout en virage', 120.00]
    ];
    
    foreach ($categories as $category) {
        $stmt = $db->prepare("INSERT INTO ticket_categories (match_id, name, description, price) VALUES (?, ?, ?, ?)");
        $stmt->execute($category);
    }
    
    echo "✅ Données de test ajoutées avec succès!\n";
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
}
?> 