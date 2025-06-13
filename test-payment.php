<?php
require_once(__DIR__ . '/config/init.php');

try {
    // Ajouter des styles CSS pour une meilleure présentation
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .success { color: green; }
        .pending { color: orange; }
        .failed { color: red; }
        h2 { color: #333; margin-top: 20px; }
    </style>";

    // Récupérer les 5 derniers paiements avec les détails de la commande
    $sql = "SELECT p.*, o.total_amount as order_amount, o.status as order_status,
                   u.name as user_name, u.email as user_email,
                   COUNT(oi.id) as number_of_tickets
            FROM payments p 
            JOIN orders o ON p.order_id = o.id 
            JOIN users u ON o.user_id = u.id 
            LEFT JOIN order_items oi ON o.id = oi.order_id
            GROUP BY p.id
            ORDER BY p.created_at DESC 
            LIMIT 5";
            
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Derniers paiements</h2>";
    
    if (empty($payments)) {
        echo "<p>Aucun paiement trouvé dans la base de données.</p>";
    } else {
        echo "<table>";
        echo "<tr>
                <th>ID Paiement</th>
                <th>ID Commande</th>
                <th>Client</th>
                <th>Transaction ID</th>
                <th>Montant</th>
                <th>Nb Billets</th>
                <th>Date</th>
                <th>Statut Paiement</th>
                <th>Statut Commande</th>
              </tr>";
              
        foreach ($payments as $payment) {
            $statusClass = $payment['status'] === 'completed' ? 'success' : 
                         ($payment['status'] === 'pending' ? 'pending' : 'failed');
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($payment['id']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['order_id']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['user_name']) . "<br>(" . htmlspecialchars($payment['user_email']) . ")</td>";
            echo "<td>" . htmlspecialchars($payment['transaction_id']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['amount']) . " " . htmlspecialchars($payment['currency']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['number_of_tickets']) . "</td>";
            echo "<td>" . htmlspecialchars($payment['created_at']) . "</td>";
            echo "<td class='" . $statusClass . "'>" . htmlspecialchars($payment['status']) . "</td>";
            echo "<td class='" . ($payment['order_status'] === 'completed' ? 'success' : 'pending') . "'>" 
                 . htmlspecialchars($payment['order_status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Afficher les commandes sans paiement
    $sql = "SELECT o.*, u.name as user_name, u.email as user_email,
                   COUNT(oi.id) as number_of_tickets
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            JOIN users u ON o.user_id = u.id
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE p.id IS NULL
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 5";
            
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $pendingOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Commandes en attente de paiement</h2>";
    
    if (empty($pendingOrders)) {
        echo "<p>Aucune commande en attente de paiement.</p>";
    } else {
        echo "<table>";
        echo "<tr>
                <th>ID Commande</th>
                <th>Client</th>
                <th>Montant</th>
                <th>Nb Billets</th>
                <th>Date</th>
                <th>Statut</th>
              </tr>";
              
        foreach ($pendingOrders as $order) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($order['id']) . "</td>";
            echo "<td>" . htmlspecialchars($order['user_name']) . "<br>(" . htmlspecialchars($order['user_email']) . ")</td>";
            echo "<td>" . htmlspecialchars($order['total_amount']) . " MAD</td>";
            echo "<td>" . htmlspecialchars($order['number_of_tickets']) . "</td>";
            echo "<td>" . htmlspecialchars($order['created_at']) . "</td>";
            echo "<td class='pending'>" . htmlspecialchars($order['status']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
    error_log("Test payment error: " . $e->getMessage());
} 