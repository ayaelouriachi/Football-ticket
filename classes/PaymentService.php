<?php
require_once dirname(__DIR__) . '/config/init.php';

class PaymentService {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function processPayment($orderId, $transactionId, $amount) {
        try {
            $this->db->beginTransaction();
            
            // 1. Create payment record
            $paymentSql = "INSERT INTO payments (
                order_id, 
                transaction_id, 
                amount, 
                payment_method,
                status,
                created_at
            ) VALUES (?, ?, ?, 'paypal', 'completed', NOW())";
            
            $stmt = $this->db->prepare($paymentSql);
            $stmt->execute([$orderId, $transactionId, $amount]);
            $paymentId = $this->db->lastInsertId();
            
            // 2. Update order status
            $orderSql = "UPDATE orders 
                        SET status = 'completed',
                            payment_id = ?,
                            transaction_id = ?,
                            payment_method = 'paypal',
                            paid_at = NOW(),
                            updated_at = NOW()
                        WHERE id = ?";
            
            $stmt = $this->db->prepare($orderSql);
            $stmt->execute([$paymentId, $transactionId, $orderId]);
            
            // 3. Update ticket availability
            $ticketSql = "UPDATE ticket_categories tc
                         JOIN order_items oi ON tc.id = oi.ticket_category_id
                         SET tc.remaining_tickets = tc.remaining_tickets - oi.quantity
                         WHERE oi.order_id = ?";
            
            $stmt = $this->db->prepare($ticketSql);
            $stmt->execute([$orderId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Payment processing error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getPaymentByOrderId($orderId) {
        $sql = "SELECT p.*, o.status as order_status 
                FROM payments p 
                JOIN orders o ON p.order_id = o.id 
                WHERE p.order_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function createPaymentsTable() {
        try {
            // Create payments table
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS payments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    transaction_id VARCHAR(255) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    payment_method ENUM('paypal', 'credit_card', 'other') NOT NULL,
                    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id)
                )
            ");
            
            // Add payment-related columns to orders table
            $this->db->exec("
                ALTER TABLE orders 
                ADD COLUMN IF NOT EXISTS payment_id INT,
                ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50),
                ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL
            ");
            
            // Add foreign key if it doesn't exist
            $this->db->exec("
                ALTER TABLE orders
                ADD CONSTRAINT IF NOT EXISTS fk_orders_payment
                FOREIGN KEY (payment_id) REFERENCES payments(id)
            ");
            
            return true;
        } catch (Exception $e) {
            error_log("Error creating payments table: " . $e->getMessage());
            throw $e;
        }
    }
} 