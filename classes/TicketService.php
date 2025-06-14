<?php

namespace FootballTickets;

class TicketService {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function processSuccessfulPayment($paymentData) {
        try {
            // 1. Validate payment data
            $this->validatePaymentData($paymentData);

            // 2. Get order details
            $order = $this->getOrderDetails($paymentData['order_id']);
            
            // 3. Get user details
            $user = $this->getUserDetails($order['user_id']);

            // 4. Update payment status in database
            $this->updatePaymentStatus($paymentData['order_id'], $paymentData['transaction_id']);

            // 5. Log successful payment
            $this->logSuccess($paymentData['order_id']);

            return true;
        } catch (\Exception $e) {
            // Log error
            error_log("Error processing payment: " . $e->getMessage());
            $this->logError($paymentData['order_id'], $e->getMessage());
            throw $e;
        }
    }

    private function validatePaymentData($paymentData) {
        if (empty($paymentData['order_id'])) {
            throw new \InvalidArgumentException("Order ID is required");
        }
        if (empty($paymentData['transaction_id'])) {
            throw new \InvalidArgumentException("Transaction ID is required");
        }
    }

    private function getOrderDetails($orderId) {
        $sql = "SELECT o.*, ot.ticket_id, t.match_id, t.section, t.row, t.seat, t.price 
                FROM orders o 
                JOIN order_tickets ot ON o.id = ot.order_id 
                JOIN tickets t ON ot.ticket_id = t.id 
                WHERE o.id = ?";
        
        $order = $this->db->query($sql, [$orderId]);
        if (!$order) {
            throw new \Exception("Order not found");
        }

        return [
            'id' => $order['id'],
            'user_id' => $order['user_id'],
            'transaction_id' => $order['transaction_id'],
            'total_amount' => $order['total_amount']
        ];
    }

    private function getUserDetails($userId) {
        $sql = "SELECT id, name, email FROM users WHERE id = ?";
        $user = $this->db->query($sql, [$userId]);
        if (!$user) {
            throw new \Exception("User not found");
        }
        return $user;
    }

    private function updatePaymentStatus($orderId, $transactionId) {
        $sql = "UPDATE orders SET 
                status = 'paid',
                transaction_id = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $this->db->query($sql, [$transactionId, $orderId]);
    }

    private function logSuccess($orderId) {
        $sql = "INSERT INTO ticket_generation_logs (order_id, status, message, created_at) 
                VALUES (?, 'success', 'Payment processed successfully', NOW())";
        $this->db->query($sql, [$orderId]);
    }

    private function logError($orderId, $error) {
        $sql = "INSERT INTO ticket_generation_logs (order_id, status, message, created_at) 
                VALUES (?, 'error', ?, NOW())";
        $this->db->query($sql, [$orderId, $error]);
    }
}