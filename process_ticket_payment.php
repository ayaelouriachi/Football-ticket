<?php
require_once 'generate_ticket_pdf.php';
require_once 'send_ticket_email.php';

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/payment_errors.log');

function processPayment($paypalTransactionId, $orderId) {
    try {
        // Connexion DB avec gestion d'erreur
        $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Commencer une transaction
        $conn->beginTransaction();
        
        try {
            // Vérifier que la commande existe et est en attente
            $checkStmt = $conn->prepare("
                SELECT o.id, o.status, o.total_amount, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?
            ");
            $checkStmt->execute([$orderId]);
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                error_log("[ERROR] Commande $orderId non trouvée dans la base de données");
                throw new Exception("Commande non trouvée");
            }
            
            error_log("[INFO] Commande $orderId trouvée - Status: {$order['status']}, Email: {$order['email']}");
            
            if ($order['status'] !== 'pending') {
                error_log("[ERROR] Commande $orderId déjà traitée (statut: {$order['status']})");
                throw new Exception("Commande déjà traitée (statut: {$order['status']})");
            }
            
            // Vérifier la disponibilité des tickets
            $availabilityStmt = $conn->prepare("
                SELECT 
                    tc.id,
                    tc.available_tickets,
                    oi.quantity
                FROM order_items oi
                JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
                WHERE oi.order_id = ?
            ");
            $availabilityStmt->execute([$orderId]);
            $tickets = $availabilityStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($tickets as $ticket) {
                if ($ticket['available_tickets'] < $ticket['quantity']) {
                    throw new Exception("Plus assez de tickets disponibles dans la catégorie " . $ticket['id']);
                }
            }
            
            // Envoyer les tickets par email AVANT de valider la transaction
            try {
                // Petit délai pour s'assurer que les données sont bien enregistrées
                sleep(2);
                
                error_log("[INFO] Tentative d'envoi des tickets pour la commande $orderId");
                $emailResult = sendTicketEmail($orderId);
                
                if ($emailResult !== true) {
                    error_log("[ERROR] Échec de l'envoi des tickets - Détails: " . print_r($emailResult, true));
                    throw new Exception("Échec de l'envoi des tickets par email: " . $emailResult);
                }
                
                error_log("[SUCCESS] Tickets envoyés avec succès pour la commande $orderId à {$order['email']}");
                
                // Si l'email est envoyé avec succès, on procède aux mises à jour
                
                // Mettre à jour le statut de la commande
                $updateStmt = $conn->prepare("
                    UPDATE orders 
                    SET 
                        status = 'completed',
                        updated_at = CURRENT_TIMESTAMP,
                        payment_transaction_id = ?
                    WHERE id = ?
                ");
                $updateStmt->execute([$paypalTransactionId, $orderId]);
                
                // Mettre à jour le nombre de tickets disponibles
                $updateTicketsStmt = $conn->prepare("
                    UPDATE ticket_categories tc
                    JOIN order_items oi ON tc.id = oi.ticket_category_id
                    SET tc.available_tickets = tc.available_tickets - oi.quantity
                    WHERE oi.order_id = ?
                ");
                $updateTicketsStmt->execute([$orderId]);
                
                // Enregistrer le paiement
                $paymentStmt = $conn->prepare("
                    INSERT INTO payments (
                        order_id, 
                        transaction_id, 
                        amount, 
                        status, 
                        payment_method,
                        created_at
                    ) VALUES (?, ?, ?, 'completed', 'paypal', CURRENT_TIMESTAMP)
                ");
                $paymentStmt->execute([$orderId, $paypalTransactionId, $order['total_amount']]);
                
                // Valider la transaction seulement après l'envoi réussi de l'email
                $conn->commit();
                
                return [
                    'success' => true,
                    'message' => 'Paiement confirmé et tickets envoyés par email',
                    'order_id' => $orderId
                ];
                
            } catch (Exception $e) {
                error_log("[CRITICAL] Erreur lors de l'envoi des tickets - Commande $orderId: " . $e->getMessage());
                throw new Exception("Erreur lors de l'envoi des tickets. Le paiement n'a pas été validé.");
            }
            
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollBack();
            error_log("Erreur traitement commande $orderId: " . $e->getMessage());
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Erreur globale process_ticket_payment: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Si le script est appelé directement
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('Content-Type: application/json');
    
    try {
        // Accepter les données en POST ou JSON
        $input = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            if (!empty($jsonInput)) {
                $input = json_decode($jsonInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Données JSON invalides: " . json_last_error_msg());
                }
            }
        } else {
            $input = $_POST;
        }
        
        error_log("[DEBUG] Données reçues : " . print_r($input, true));
        
        $paypalTransactionId = $input['paypal_transaction_id'] ?? '';
        $orderId = $input['order_id'] ?? '';
        
        if (empty($paypalTransactionId) || empty($orderId)) {
            throw new Exception("Paramètres manquants: " . 
                (empty($paypalTransactionId) ? 'paypal_transaction_id ' : '') . 
                (empty($orderId) ? 'order_id' : '')
            );
        }
        
        $result = processPayment($paypalTransactionId, $orderId);
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Erreur globale process_ticket_payment: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} 