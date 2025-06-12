<?php
header('Content-Type: application/json');
require_once('../../config/init.php');

// Ensure admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get overall statistics
    $sql = "SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
        AVG(CASE WHEN status = 'completed' THEN total_amount ELSE NULL END) as avg_order_value,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders
    FROM orders";
    
    $stmt = $db->query($sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate completion rate
    $stats['completion_rate'] = $stats['total_orders'] > 0 
        ? ($stats['completed_orders'] / $stats['total_orders']) * 100 
        : 0;
    
    // Get recent activity
    $activitySql = "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC";
    
    $stmt = $db->query($activitySql);
    $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'activity' => $activity
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'An error occurred while fetching dashboard statistics'
    ]);
    error_log("Dashboard stats error: " . $e->getMessage());
} 