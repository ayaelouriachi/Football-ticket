<?php
require_once 'config/init.php';
require_once 'config/database.php';

function processPayment($paypalTransactionId, $orderId) {
    try {
        $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Start transaction for payment processing
        $conn->beginTransaction();
        
        try {
            // Check if order exists and is pending
            $checkStmt = $conn->prepare("
                SELECT o.id, o.status, o.total_amount, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?
            ");
            $checkStmt->execute([$orderId]);
            $order = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception("Commande non trouvée");
            }
            
            if ($order['status'] !== 'pending') {
                throw new Exception("Commande déjà traitée (statut: {$order['status']})");
            }
            
            // Insert payment record
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
            
            // Update order status
            $updateStmt = $conn->prepare("
                UPDATE orders 
                SET status = 'completed',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $updateStmt->execute([$orderId]);
            
            // Update ticket availability
            $updateTicketsStmt = $conn->prepare("
                UPDATE ticket_categories tc
                JOIN order_items oi ON tc.id = oi.ticket_category_id
                SET tc.available_tickets = tc.available_tickets - oi.quantity
                WHERE oi.order_id = ?
            ");
            $updateTicketsStmt->execute([$orderId]);
            
            // Commit payment transaction
            $conn->commit();
            
            // Now handle email sending separately (don't rollback payment if email fails)
            try {
                $emailResult = sendTicketEmail($orderId);
                if ($emailResult !== true) {
                    error_log("[WARNING] Email sending failed but payment was successful - Order: $orderId, Email: {$order['email']}");
                }
            } catch (Exception $e) {
                error_log("[WARNING] Email sending failed but payment was successful - Order: $orderId - " . $e->getMessage());
            }
            
            return [
                'success' => true,
                'message' => 'Paiement confirmé',
                'order_id' => $orderId
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erreur traitement paiement commande $orderId: " . $e->getMessage());
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Erreur globale process_payment_success: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Handle incoming request
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    header('Content-Type: application/json');
    
    try {
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
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}