<?php
require_once __DIR__ . '/../config/init.php';

echo "Testing database connection...\n\n";

try {
    // Test basic connection
    $db->query("SELECT 1");
    echo "✅ Database connection successful\n";
    
    // Test required tables
    $tables = ['matches', 'teams', 'ticket_categories', 'stadiums', 'users'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '{$table}' exists\n";
        } else {
            echo "❌ Table '{$table}' does not exist\n";
        }
    }
    
    // Test for data
    $stmt = $db->query("SELECT COUNT(*) FROM matches");
    $matchCount = $stmt->fetchColumn();
    echo "\nFound {$matchCount} matches in database\n";
    
    $stmt = $db->query("SELECT COUNT(*) FROM ticket_categories");
    $categoryCount = $stmt->fetchColumn();
    echo "Found {$categoryCount} ticket categories in database\n";
    
    echo "\nAll database tests completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} 