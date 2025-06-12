<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $db;
    private $table = 'users';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all users with pagination and filters
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            // Build where clause from filters
            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $where[] = "(name LIKE ? OR email LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "created_at <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Build the query
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
            $totalStmt = $this->db->query($countSql, $params);
            $total = $totalStmt->fetch()['total'];
            
            // Get users
            $sql = "SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as orders_count,
                    (SELECT SUM(total_amount) FROM orders o WHERE o.user_id = u.id AND o.status = 'paid') as total_spent
                FROM {$this->table} u
                {$whereClause}
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->query($sql, $params);
            $users = $stmt->fetchAll();
            
            return [
                'data' => $users,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching users: " . $e->getMessage());
            throw new Exception("Failed to fetch users");
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById(int $id): ?array {
        try {
            $sql = "SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as orders_count,
                    (SELECT SUM(total_amount) FROM orders o WHERE o.user_id = u.id AND o.status = 'paid') as total_spent
                FROM {$this->table} u
                WHERE u.id = ?";
            
            $stmt = $this->db->query($sql, [$id]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Get recent orders
                $ordersSql = "SELECT 
                    o.*,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
                FROM orders o
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
                LIMIT 5";
                
                $ordersStmt = $this->db->query($ordersSql, [$id]);
                $user['recent_orders'] = $ordersStmt->fetchAll();
            }
            
            return $user ?: null;
            
        } catch (Exception $e) {
            error_log("Error fetching user: " . $e->getMessage());
            throw new Exception("Failed to fetch user details");
        }
    }
    
    /**
     * Create new user
     */
    public function create(array $data): int {
        try {
            // Validate email uniqueness
            $emailCheck = $this->db->query(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ?",
                [$data['email']]
            );
            
            if ($emailCheck->fetch()['count'] > 0) {
                throw new Exception("Email already exists");
            }
            
            // Hash password if provided
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            // Insert user
            $sql = "INSERT INTO {$this->table} (
                name, email, password, status, phone, address,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [
                $data['name'],
                $data['email'],
                $data['password'] ?? null,
                $data['status'] ?? 'active',
                $data['phone'] ?? null,
                $data['address'] ?? null
            ];
            
            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            throw new Exception($e->getMessage() ?: "Failed to create user");
        }
    }
    
    /**
     * Update user
     */
    public function update(int $id, array $data): bool {
        try {
            // Check if email is being changed and validate uniqueness
            if (!empty($data['email'])) {
                $emailCheck = $this->db->query(
                    "SELECT COUNT(*) as count FROM {$this->table} WHERE email = ? AND id != ?",
                    [$data['email'], $id]
                );
                
                if ($emailCheck->fetch()['count'] > 0) {
                    throw new Exception("Email already exists");
                }
            }
            
            $updates = [];
            $params = [];
            
            // Build update fields
            $allowedFields = [
                'name', 'email', 'status', 'phone', 'address'
            ];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            // Update password if provided
            if (!empty($data['password'])) {
                $updates[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $params[] = $id;
                
                $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($sql, $params);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            throw new Exception($e->getMessage() ?: "Failed to update user");
        }
    }
    
    /**
     * Delete user
     */
    public function delete(int $id): bool {
        try {
            // Check if user has any orders
            $orderCheck = $this->db->query(
                "SELECT COUNT(*) as count FROM orders WHERE user_id = ?",
                [$id]
            );
            
            if ($orderCheck->fetch()['count'] > 0) {
                throw new Exception("Cannot delete user with existing orders");
            }
            
            // Delete user
            $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Update user status
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            $validStatuses = ['active', 'inactive', 'banned'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$status, $id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating user status: " . $e->getMessage());
            throw new Exception("Failed to update user status");
        }
    }
    
    /**
     * Get user statistics
     */
    public function getStats(): array {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned_users,
                    (SELECT COUNT(*) FROM orders o WHERE o.status = 'paid') as total_orders,
                    (SELECT SUM(total_amount) FROM orders o WHERE o.status = 'paid') as total_revenue
                FROM {$this->table}";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error fetching user statistics: " . $e->getMessage());
            throw new Exception("Failed to fetch user statistics");
        }
    }
} 