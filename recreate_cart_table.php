<?php
require_once __DIR__ . '/config/database.php';

try {
    // Drop existing table if exists
    $db->exec("DROP TABLE IF EXISTS cart_items");
    
    // Create new table with correct structure
    $sql = "CREATE TABLE cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        session_id VARCHAR(255) NOT NULL,
        ticket_category_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    $db->exec($sql);
    echo "Table cart_items recreated successfully!";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 