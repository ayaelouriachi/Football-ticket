<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful!\n\n";
    
    // Test matches table
    $stmt = $db->query("SELECT COUNT(*) as count FROM matches");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📊 Number of matches in database: " . $result['count'] . "\n";
    
    // Test ticket categories table
    $stmt = $db->query("SELECT COUNT(*) as count FROM ticket_categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "🎫 Number of ticket categories: " . $result['count'] . "\n";
    
    // Test teams table
    $stmt = $db->query("SELECT COUNT(*) as count FROM teams");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "⚽ Number of teams: " . $result['count'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage();
} 