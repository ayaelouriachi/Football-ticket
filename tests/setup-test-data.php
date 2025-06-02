<?php
require_once __DIR__ . '/../config/init.php';

echo "Setting up test data...\n\n";

try {
    // Start transaction
    $db->beginTransaction();
    
    // Add test stadium
    $stmt = $db->prepare("
        INSERT IGNORE INTO stadiums (name, city, address, capacity) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute(['Test Stadium', 'Test City', '123 Test St', 50000]);
    $stadiumId = $db->lastInsertId() ?: 1;
    
    // Add test teams
    $stmt = $db->prepare("
        INSERT IGNORE INTO teams (name, logo) 
        VALUES (?, ?), (?, ?)
    ");
    $stmt->execute([
        'Test Team 1', '/uploads/teams/test-team1.png',
        'Test Team 2', '/uploads/teams/test-team2.png'
    ]);
    $team1Id = $db->lastInsertId() ?: 1;
    $team2Id = $team1Id + 1;
    
    // Add test match
    $stmt = $db->prepare("
        INSERT IGNORE INTO matches (
            title, team1_id, team2_id, stadium_id, match_date, 
            competition, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        'Test Match',
        $team1Id,
        $team2Id,
        $stadiumId,
        date('Y-m-d H:i:s', strtotime('+1 day')),
        'Test Competition',
        'upcoming'
    ]);
    $matchId = $db->lastInsertId() ?: 1;
    
    // Add test ticket categories
    $stmt = $db->prepare("
        INSERT IGNORE INTO ticket_categories (
            match_id, name, price, capacity, remaining_tickets,
            description
        ) VALUES (?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $matchId, 'VIP', 1000, 100, 100, 'VIP Section',
        $matchId, 'Standard', 500, 1000, 1000, 'Standard Section'
    ]);
    
    // Commit transaction
    $db->commit();
    
    echo "✅ Test data setup completed successfully!\n";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollBack();
    echo "❌ Error setting up test data: " . $e->getMessage() . "\n";
    exit(1);
} 