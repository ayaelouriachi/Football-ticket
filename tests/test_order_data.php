<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';

// Configuration des logs
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/test_order.log');

echo "=== Test des données de commande ===\n\n";

try {
    // Connexion à la base de données
    $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Créer des données de test
    $conn->beginTransaction();
    
    try {
        // Créer un utilisateur de test
        $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email) VALUES (?, ?)");
        $stmt->execute(['Test User', SMTP_USERNAME]);
        $userId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM users WHERE email = '" . SMTP_USERNAME . "'")->fetchColumn();
        echo "✓ Utilisateur de test créé/trouvé (ID: $userId)\n";
        
        // Créer un stade de test si nécessaire
        $stmt = $conn->prepare("INSERT IGNORE INTO stadiums (name, city, address) VALUES (?, ?, ?)");
        $stmt->execute(['Stade Test', 'Ville Test', 'Adresse Test']);
        $stadiumId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM stadiums WHERE name = 'Stade Test'")->fetchColumn();
        echo "✓ Stade de test créé/trouvé (ID: $stadiumId)\n";
        
        // Créer des équipes de test si nécessaire
        $stmt = $conn->prepare("INSERT IGNORE INTO teams (name) VALUES (?)");
        $stmt->execute(['Équipe A']);
        $team1Id = $conn->lastInsertId() ?: $conn->query("SELECT id FROM teams WHERE name = 'Équipe A'")->fetchColumn();
        
        $stmt->execute(['Équipe B']);
        $team2Id = $conn->lastInsertId() ?: $conn->query("SELECT id FROM teams WHERE name = 'Équipe B'")->fetchColumn();
        echo "✓ Équipes de test créées/trouvées (IDs: $team1Id, $team2Id)\n";
        
        // Créer un match de test
        $stmt = $conn->prepare("
            INSERT IGNORE INTO matches (
                title, match_date, competition, team1_id, team2_id, stadium_id
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'Match Test',
            date('Y-m-d H:i:s', strtotime('+1 week')),
            'Compétition Test',
            $team1Id,
            $team2Id,
            $stadiumId
        ]);
        $matchId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM matches WHERE title = 'Match Test'")->fetchColumn();
        echo "✓ Match de test créé/trouvé (ID: $matchId)\n";
        
        // Créer une catégorie de ticket de test
        $stmt = $conn->prepare("
            INSERT IGNORE INTO ticket_categories (
                match_id, name, description, price, available_tickets
            ) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $matchId,
            'Catégorie Test',
            'Description de test',
            100.00,
            100
        ]);
        $categoryId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM ticket_categories WHERE name = 'Catégorie Test'")->fetchColumn();
        echo "✓ Catégorie de ticket créée/trouvée (ID: $categoryId)\n";
        
        // Créer une commande de test
        $stmt = $conn->prepare("
            INSERT INTO orders (
                user_id, total_amount, status, created_at
            ) VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            $userId,
            200.00,
            'pending'
        ]);
        $orderId = $conn->lastInsertId();
        echo "✓ Commande de test créée (ID: $orderId)\n";
        
        // Ajouter des items à la commande
        $stmt = $conn->prepare("
            INSERT INTO order_items (
                order_id, ticket_category_id, quantity, price_per_ticket
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $orderId,
            $categoryId,
            2,
            100.00
        ]);
        echo "✓ Items de commande créés\n";
        
        $conn->commit();
        echo "✓ Transaction validée\n\n";
        
        // 2. Tester la génération de PDF
        echo "Test de génération du PDF...\n";
        require_once __DIR__ . '/../generate_ticket_pdf.php';
        $pdfContent = generateFootballTicketPDF($orderId);
        echo "✓ PDF généré avec succès\n\n";
        
        // 3. Tester l'envoi d'email
        echo "Test d'envoi de l'email...\n";
        require_once __DIR__ . '/../send_ticket_email.php';
        $emailSent = sendTicketEmail($orderId);
        
        if ($emailSent) {
            echo "✓ Email envoyé avec succès\n";
        } else {
            echo "❌ Échec de l'envoi de l'email\n";
        }
        
        // 4. Afficher les données de la commande
        echo "\nDonnées de la commande :\n";
        $stmt = $conn->prepare("
            SELECT 
                o.id as order_id,
                o.total_amount,
                o.status,
                u.name as user_name,
                u.email as user_email,
                m.title as match_title,
                m.match_date,
                t1.name as team1_name,
                t2.name as team2_name,
                s.name as stadium_name,
                tc.name as category_name,
                oi.quantity,
                oi.price_per_ticket
            FROM orders o
            JOIN users u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
            JOIN matches m ON tc.match_id = m.id
            JOIN teams t1 ON m.team1_id = t1.id
            JOIN teams t2 ON m.team2_id = t2.id
            JOIN stadiums s ON m.stadium_id = s.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        print_r($orderData);
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Trace : \n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Fin des tests ===\n";
?> 