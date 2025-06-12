<?php
require_once __DIR__ . '/../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if role column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role'");
    if (!$stmt->fetch()) {
        // Add role column
        $sql = "ALTER TABLE users 
                ADD COLUMN role ENUM('user', 'admin') NOT NULL DEFAULT 'user' AFTER password_hash";
        $db->exec($sql);
        echo "Added role column to users table\n";
    }

    // Check if status column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
    if (!$stmt->fetch()) {
        // Add status column
        $sql = "ALTER TABLE users 
                ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER role";
        $db->exec($sql);
        echo "Added status column to users table\n";
    }

    // Check if created_at column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    if (!$stmt->fetch()) {
        // Add created_at column
        $sql = "ALTER TABLE users 
                ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER status";
        $db->exec($sql);
        echo "Added created_at column to users table\n";
    }

    // Check if updated_at column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'updated_at'");
    if (!$stmt->fetch()) {
        // Add updated_at column
        $sql = "ALTER TABLE users 
                ADD COLUMN updated_at DATETIME NULL AFTER created_at";
        $db->exec($sql);
        echo "Added updated_at column to users table\n";
    }

    // Check if last_login column exists
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'last_login'");
    if (!$stmt->fetch()) {
        // Add last_login column
        $sql = "ALTER TABLE users 
                ADD COLUMN last_login DATETIME NULL AFTER updated_at";
        $db->exec($sql);
        echo "Added last_login column to users table\n";
    }

    // Add indexes if they don't exist
    $stmt = $db->query("SHOW INDEX FROM users WHERE Key_name = 'email'");
    if (!$stmt->fetch()) {
        $sql = "ALTER TABLE users ADD INDEX (email)";
        $db->exec($sql);
        echo "Added email index to users table\n";
    }

    $stmt = $db->query("SHOW INDEX FROM users WHERE Key_name = 'role'");
    if (!$stmt->fetch()) {
        $sql = "ALTER TABLE users ADD INDEX (role)";
        $db->exec($sql);
        echo "Added role index to users table\n";
    }

    echo "\nUsers table structure has been updated successfully!\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
} 