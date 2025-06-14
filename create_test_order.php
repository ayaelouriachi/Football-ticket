<?php
try {
    $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    // 1. Créer un utilisateur de test s'il n'existe pas
    $email = 'test@example.com';
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute(['Utilisateur Test', $email, password_hash('test123', PASSWORD_DEFAULT)]);
        $userId = $conn->lastInsertId();
    } else {
        $userId = $user['id'];
    }
    
    // 2. Créer un match de test s'il n'existe pas
    $stmt = $conn->prepare("SELECT id FROM matches WHERE title = 'Match Test'");
    $stmt->execute();
    $match = $stmt->fetch();
    
    if (!$match) {
        // Créer équipes
        $stmt = $conn->prepare("INSERT INTO teams (name) VALUES (?)");
        $stmt->execute(['Équipe A']);
        $team1Id = $conn->lastInsertId();
        $stmt->execute(['Équipe B']);
        $team2Id = $conn->lastInsertId();
        
        // Créer stade
        $stmt = $conn->prepare("INSERT INTO stadiums (name, city, address) VALUES (?, ?, ?)");
        $stmt->execute(['Stade Test', 'Ville Test', '123 Rue Test']);
        $stadiumId = $conn->lastInsertId();
        
        // Créer match
        $stmt = $conn->prepare("
            INSERT INTO matches (title, team1_id, team2_id, stadium_id, match_date, competition) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute(['Match Test', $team1Id, $team2Id, $stadiumId, date('Y-m-d H:i:s', strtotime('+1 week')), 'Compétition Test']);
        $matchId = $conn->lastInsertId();
    } else {
        $matchId = $match['id'];
    }
    
    // 3. Créer une catégorie de ticket si elle n'existe pas
    $stmt = $conn->prepare("SELECT id FROM ticket_categories WHERE match_id = ? LIMIT 1");
    $stmt->execute([$matchId]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $stmt = $conn->prepare("
            INSERT INTO ticket_categories (match_id, name, description, price) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$matchId, 'Catégorie Test', 'Description de test', 100.00]);
        $categoryId = $conn->lastInsertId();
    } else {
        $categoryId = $category['id'];
    }
    
    // 4. Créer une commande
    $stmt = $conn->prepare("
        INSERT INTO orders (user_id, total_amount, status, created_at) 
        VALUES (?, ?, 'pending', NOW())
    ");
    $quantity = 2;
    $totalAmount = $quantity * 100.00; // Prix unitaire * quantité
    $stmt->execute([$userId, $totalAmount]);
    $orderId = $conn->lastInsertId();
    
    // 5. Ajouter les items de la commande
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, ticket_category_id, quantity, price_per_ticket) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$orderId, $categoryId, $quantity, 100.00]);
    
    // Valider la transaction
    $conn->commit();
    
    echo "Commande de test créée avec succès!\n";
    echo "ID de la commande: " . $orderId . "\n";
    echo "Utilisez cet ID pour tester le système de tickets.\n";
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollBack();
    }
    echo "Erreur: " . $e->getMessage() . "\n";
} 