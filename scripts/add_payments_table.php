<?php
require_once __DIR__ . '/../config/init.php';

try {
    // Create payments table
    $db->exec("
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
    $db->exec("
        ALTER TABLE orders 
        ADD COLUMN IF NOT EXISTS payment_id INT,
        ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50),
        ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL,
        ADD FOREIGN KEY IF NOT EXISTS (payment_id) REFERENCES payments(id)
    ");
    
    echo "✅ Payments table and columns added successfully!\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage() . "\n");
} 