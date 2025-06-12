<?php
require_once __DIR__ . '/../config/database.php';

class Dashboard {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats(): array {
        try {
            $stats = [
                'total_users' => $this->getTotalUsers(),
                'total_orders' => $this->getTotalOrders(),
                'total_revenue' => $this->getTotalRevenue(),
                'active_matches' => $this->getActiveMatches(),
                'recent_orders' => $this->getRecentOrders(5),
                'recent_users' => $this->getRecentUsers(5),
                'revenue_by_month' => $this->getRevenueByMonth(),
                'popular_matches' => $this->getPopularMatches(5)
            ];
            return $stats;
        } catch (Exception $e) {
            error_log("Error fetching dashboard stats: " . $e->getMessage());
            throw new Exception("Failed to fetch dashboard statistics");
        }
    }
    
    /**
     * Get total number of users
     */
    private function getTotalUsers(): int {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM users WHERE status = 'active'");
        return (int) $stmt->fetch()['total'];
    }
    
    /**
     * Get total number of orders
     */
    private function getTotalOrders(): int {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM orders WHERE status != 'cancelled'");
        return (int) $stmt->fetch()['total'];
    }
    
    /**
     * Get total revenue
     */
    private function getTotalRevenue(): float {
        $stmt = $this->db->query(
            "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'"
        );
        return (float) $stmt->fetch()['total'] ?? 0;
    }
    
    /**
     * Get number of active matches
     */
    private function getActiveMatches(): int {
        $stmt = $this->db->query(
            "SELECT COUNT(*) as total FROM matches 
             WHERE match_date >= CURDATE() AND status = 'active'"
        );
        return (int) $stmt->fetch()['total'];
    }
    
    /**
     * Get recent orders
     */
    private function getRecentOrders(int $limit): array {
        $stmt = $this->db->query(
            "SELECT o.*, u.name as user_name, u.email as user_email
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             ORDER BY o.created_at DESC
             LIMIT ?",
            [$limit]
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent users
     */
    private function getRecentUsers(int $limit): array {
        $stmt = $this->db->query(
            "SELECT id, name, email, created_at, status
             FROM users
             ORDER BY created_at DESC
             LIMIT ?",
            [$limit]
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Get revenue by month
     */
    private function getRevenueByMonth(): array {
        $stmt = $this->db->query(
            "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(total_amount) as revenue,
                COUNT(*) as order_count
             FROM orders
             WHERE status = 'completed'
             AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month DESC"
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Get popular matches
     */
    private function getPopularMatches(int $limit): array {
        $stmt = $this->db->query(
            "SELECT 
                m.*,
                COUNT(DISTINCT o.id) as order_count,
                SUM(oi.quantity) as tickets_sold,
                (SELECT COUNT(*) 
                 FROM ticket_categories tc 
                 WHERE tc.match_id = m.id) as total_categories,
                (SELECT SUM(tc.capacity) 
                 FROM ticket_categories tc 
                 WHERE tc.match_id = m.id) as total_capacity
             FROM matches m
             LEFT JOIN order_items oi ON oi.match_id = m.id
             LEFT JOIN orders o ON o.id = oi.order_id
             WHERE m.match_date >= CURDATE()
             GROUP BY m.id
             ORDER BY tickets_sold DESC
             LIMIT ?",
            [$limit]
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities(int $limit = 10): array {
        $stmt = $this->db->query(
            "SELECT 
                sl.*,
                au.name as admin_name,
                au.email as admin_email
             FROM system_logs sl
             LEFT JOIN admin_users au ON sl.admin_id = au.id
             ORDER BY sl.created_at DESC
             LIMIT ?",
            [$limit]
        );
        return $stmt->fetchAll();
    }
    
    /**
     * Get revenue data with date range
     */
    public function getRevenueData(string $startDate, string $endDate): array {
        $stmt = $this->db->query(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as order_count,
                SUM(total_amount) as revenue,
                AVG(total_amount) as average_order_value
             FROM orders
             WHERE status = 'completed'
             AND created_at BETWEEN ? AND ?
             GROUP BY DATE(created_at)
             ORDER BY date",
            [$startDate, $endDate]
        );
        return $stmt->fetchAll();
    }
} 