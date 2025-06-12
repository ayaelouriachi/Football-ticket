<?php
require_once __DIR__ . '/../config/database.php';

class Order {
    private $db;
    private $table = 'orders';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all orders with pagination and filters
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            // Build where clause from filters
            if (!empty($filters['status'])) {
                $where[] = "o.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['user_id'])) {
                $where[] = "o.user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['match_id'])) {
                $where[] = "EXISTS (
                    SELECT 1 FROM order_items oi 
                    WHERE oi.order_id = o.id 
                    AND oi.match_id = ?
                )";
                $params[] = $filters['match_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "o.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "o.created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Build the query
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} o {$whereClause}";
            $totalStmt = $this->db->query($countSql, $params);
            $total = $totalStmt->fetch()['total'];
            
            // Get orders
            $sql = "SELECT 
                    o.*,
                    u.name as user_name,
                    u.email as user_email,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count,
                    (SELECT GROUP_CONCAT(DISTINCT m.id) 
                     FROM order_items oi 
                     JOIN matches m ON oi.match_id = m.id 
                     WHERE oi.order_id = o.id) as match_ids
                FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.id
                {$whereClause}
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->query($sql, $params);
            $orders = $stmt->fetchAll();
            
            return [
                'data' => $orders,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching orders: " . $e->getMessage());
            throw new Exception("Failed to fetch orders");
        }
    }
    
    /**
     * Get order by ID
     */
    public function getById(int $id): ?array {
        try {
            $sql = "SELECT 
                    o.*,
                    u.name as user_name,
                    u.email as user_email
                FROM {$this->table} o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = ?";
            
            $stmt = $this->db->query($sql, [$id]);
            $order = $stmt->fetch();
            
            if ($order) {
                // Get order items
                $itemsSql = "SELECT 
                    oi.*,
                    m.match_date,
                    m.kickoff_time,
                    ht.name as home_team_name,
                    at.name as away_team_name,
                    tc.name as ticket_category_name
                FROM order_items oi
                JOIN matches m ON oi.match_id = m.id
                JOIN teams ht ON m.home_team_id = ht.id
                JOIN teams at ON m.away_team_id = at.id
                JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
                WHERE oi.order_id = ?";
                
                $itemsStmt = $this->db->query($itemsSql, [$id]);
                $order['items'] = $itemsStmt->fetchAll();
                
                // Get payment info if exists
                $paymentSql = "SELECT * FROM payments WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
                $paymentStmt = $this->db->query($paymentSql, [$id]);
                $order['payment'] = $paymentStmt->fetch();
            }
            
            return $order ?: null;
            
        } catch (Exception $e) {
            error_log("Error fetching order: " . $e->getMessage());
            throw new Exception("Failed to fetch order details");
        }
    }
    
    /**
     * Update order status
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            $validStatuses = ['pending', 'paid', 'cancelled', 'refunded'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$status, $id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating order status: " . $e->getMessage());
            throw new Exception("Failed to update order status");
        }
    }
    
    /**
     * Process refund
     */
    public function processRefund(int $id, string $reason): bool {
        try {
            $this->db->beginTransaction();
            
            // Get order details
            $order = $this->getById($id);
            
            if (!$order) {
                throw new Exception("Order not found");
            }
            
            if ($order['status'] !== 'paid') {
                throw new Exception("Only paid orders can be refunded");
            }
            
            // Update order status
            $this->updateStatus($id, 'refunded');
            
            // Create refund record
            $sql = "INSERT INTO refunds (
                order_id, amount, reason, status, created_at, updated_at
            ) VALUES (?, ?, ?, 'completed', NOW(), NOW())";
            
            $this->db->query($sql, [
                $id,
                $order['total_amount'],
                $reason
            ]);
            
            // Release ticket inventory
            $itemsSql = "UPDATE ticket_categories tc
                        JOIN order_items oi ON tc.id = oi.ticket_category_id
                        SET tc.available_tickets = tc.available_tickets + oi.quantity
                        WHERE oi.order_id = ?";
            
            $this->db->query($itemsSql, [$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error processing refund: " . $e->getMessage());
            throw new Exception("Failed to process refund");
        }
    }
    
    /**
     * Get order statistics
     */
    public function getStats(string $startDate = null, string $endDate = null): array {
        try {
            $params = [];
            $dateFilter = "";
            
            if ($startDate && $endDate) {
                $dateFilter = "WHERE created_at BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
            }
            
            $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                    SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded_orders,
                    SUM(total_amount) as total_revenue,
                    AVG(CASE WHEN status = 'paid' THEN total_amount ELSE NULL END) as average_order_value
                FROM {$this->table}
                {$dateFilter}";
            
            $stmt = $this->db->query($sql, $params);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error fetching order statistics: " . $e->getMessage());
            throw new Exception("Failed to fetch order statistics");
        }
    }
} 