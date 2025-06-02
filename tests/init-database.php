<?php
require_once __DIR__ . '/../config/init.php';

echo "Initializing database...\n\n";

try {
    // Create database if not exists
    $pdo = new PDO(
        "mysql:host=localhost",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS football_tickets CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database created/verified\n";
    
    // Switch to the database
    $pdo->exec("USE football_tickets");
    
    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing tables in reverse order of dependencies
    $tables = [
        'order_items',
        'orders',
        'ticket_categories',
        'matches',
        'teams',
        'stadiums',
        'users'
    ];
    
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS {$table}");
        echo "✅ Dropped table {$table} if existed\n";
    }
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✅ Database initialized successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database initialization error: " . $e->getMessage() . "\n";
    exit(1);
} 