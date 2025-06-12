<?php
// Disable output buffering
ob_end_clean();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define BASE_URL for CLI
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/football_tickets/');
}

require_once(__DIR__ . '/../../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Starting admin system installation...\n";
    
    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing tables if they exist
    $db->exec("DROP TABLE IF EXISTS `system_logs`");
    $db->exec("DROP TABLE IF EXISTS `admin_users`");
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "Creating admin tables...\n";
    
    // Create admin_users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            role ENUM('super_admin', 'admin', 'content_manager') NOT NULL DEFAULT 'admin',
            status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
            last_login DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Created admin_users table\n";
    
    // Create system_logs table
    $db->exec("
        CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(50) NOT NULL,
            description TEXT,
            ip_address VARCHAR(45),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "Created system_logs table\n";
    
    // Insert default admin user
    $db->exec("
        INSERT INTO admin_users (email, password, name, role) VALUES (
            'admin@ticketfoot.com',
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            'Super Admin',
            'super_admin'
        )
    ");
    echo "Created default admin user\n";
    
    echo "\nAdmin system installed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Email: admin@ticketfoot.com\n";
    echo "Password: password\n";
    echo "\nPlease change these credentials after first login.\n";
    
} catch (PDOException $e) {
    echo "Error installing admin system: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "General error: " . $e->getMessage() . "\n";
    exit(1);
} 