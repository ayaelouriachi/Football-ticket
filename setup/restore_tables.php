<?php
require_once __DIR__ . '/../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Drop any columns we added to users table
    $sql = "ALTER TABLE users 
            DROP COLUMN IF EXISTS is_active,
            DROP COLUMN IF EXISTS role,
            DROP COLUMN IF EXISTS created_at,
            DROP COLUMN IF EXISTS updated_at,
            DROP COLUMN IF EXISTS last_login";
    
    $db->exec($sql);
    echo "Users table restored to original structure\n";

    // Drop login_attempts table if it exists
    $sql = "DROP TABLE IF EXISTS login_attempts";
    $db->exec($sql);
    echo "Dropped login_attempts table\n";

    // Restore admins table to original structure
    $sql = "DROP TABLE IF EXISTS admin_activity_log";
    $db->exec($sql);
    
    $sql = "DROP TABLE IF EXISTS admins";
    $db->exec($sql);
    
    // Recreate admins table with original structure
    $sql = "CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        last_login DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        INDEX (username),
        INDEX (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    
    // Create admin activity log
    $sql = "CREATE TABLE admin_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45) NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (admin_id) REFERENCES admins(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);

    // Create default admin account
    $admin = [
        'username' => 'admin',
        'email' => 'admin@admin.com',
        'password' => 'Admin123!',
        'full_name' => 'System Administrator'
    ];

    $sql = "INSERT INTO admins (username, email, password_hash, full_name, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $admin['username'],
        $admin['email'],
        password_hash($admin['password'], PASSWORD_DEFAULT),
        $admin['full_name']
    ]);
    
    echo "\nTables restored to original structure\n";
    echo "\nDefault admin account:\n";
    echo "Username: " . $admin['username'] . "\n";
    echo "Email: " . $admin['email'] . "\n";
    echo "Password: " . $admin['password'] . "\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} 