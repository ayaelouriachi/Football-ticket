<?php
try {
    $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $conn->query("
        SELECT 
            o.id,
            o.total_amount,
            o.status,
            o.created_at,
            u.name as user_name,
            u.email as user_email,
            COUNT(oi.id) as number_of_tickets
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    
    echo "Dernières commandes :\n";
    echo "==========================================\n";
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: " . $row['id'] . "\n";
        echo "Client: " . $row['user_name'] . "\n";
        echo "Email: " . $row['user_email'] . "\n";
        echo "Montant: " . $row['total_amount'] . " MAD\n";
        echo "Nombre de tickets: " . $row['number_of_tickets'] . "\n";
        echo "Statut: " . $row['status'] . "\n";
        echo "Date: " . $row['created_at'] . "\n";
        echo "------------------------------------------\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
} 