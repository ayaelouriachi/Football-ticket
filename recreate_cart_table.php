<?php
require_once 'config/init.php';

try {
    // Drop existing cart_items table
    $db->exec("DROP TABLE IF EXISTS cart_items");
    echo "✅ Dropped existing cart_items table\n";
    
    // Create cart_items table
    $db->exec("
        CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            session_id VARCHAR(255),
            ticket_category_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id)
        )
    ");
    echo "✅ Created cart_items table with new structure\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 