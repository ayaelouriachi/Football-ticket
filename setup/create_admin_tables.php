<?php
require_once __DIR__ . '/../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Create admins table
    $sql = "CREATE TABLE IF NOT EXISTS admins (
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
    echo "Admins table created successfully\n";

    // Create admin activity log table
    $sql = "CREATE TABLE IF NOT EXISTS admin_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45) NULL,
        created_at DATETIME NOT NULL,
        FOREIGN KEY (admin_id) REFERENCES admins(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "Admin activity log table created successfully\n";

    // Create login attempts table
    $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        attempt_time DATETIME NOT NULL,
        INDEX (email, attempt_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "Login attempts table created successfully\n";

    // Create default admin account
    $adminExists = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    
    if ($adminExists == 0) {
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

        echo "\nDefault admin account created:\n";
        echo "Username: " . $admin['username'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Password: " . $admin['password'] . "\n";
    }
    
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
} 