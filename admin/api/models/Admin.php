<?php
require_once __DIR__ . '/../config/database.php';

class Admin {
    private $db;
    private $table = 'admin_users';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Authenticate admin user
     */
    public function authenticate(string $email, string $password): ?array {
        try {
            $stmt = $this->db->query(
                "SELECT * FROM {$this->table} WHERE email = ? AND status = 'active' LIMIT 1",
                [$email]
            );
            
            $admin = $stmt->fetch();
            if (!$admin || !password_verify($password, $admin['password'])) {
                return null;
            }
            
            // Remove sensitive data
            unset($admin['password']);
            
            // Log successful login
            $this->logActivity($admin['id'], 'login', 'Successfully logged in');
            
            return $admin;
        } catch (Exception $e) {
            error_log("Admin authentication error: " . $e->getMessage());
            throw new Exception("Authentication failed");
        }
    }
    
    /**
     * Get admin by ID
     */
    public function getById(int $id): ?array {
        try {
            $stmt = $this->db->query(
                "SELECT id, name, email, role, status, last_login, created_at, updated_at 
                 FROM {$this->table} 
                 WHERE id = ? AND status = 'active'",
                [$id]
            );
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            error_log("Error fetching admin: " . $e->getMessage());
            throw new Exception("Failed to fetch admin details");
        }
    }
    
    /**
     * Update admin profile
     */
    public function updateProfile(int $id, array $data): bool {
        try {
            $this->db->beginTransaction();
            
            $allowedFields = ['name', 'email', 'password'];
            $updates = [];
            $params = [];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    if ($field === 'password') {
                        $value = password_hash($value, PASSWORD_DEFAULT);
                    }
                    $updates[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $params[] = $id;
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            
            $this->db->query($sql, $params);
            $this->logActivity($id, 'profile_update', 'Updated profile information');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error updating admin profile: " . $e->getMessage());
            throw new Exception("Failed to update profile");
        }
    }
    
    /**
     * Log admin activity
     */
    private function logActivity(int $adminId, string $action, string $details): void {
        try {
            $this->db->query(
                "INSERT INTO system_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
                [$adminId, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']
            );
        } catch (Exception $e) {
            error_log("Error logging admin activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get admin permissions
     */
    public function getPermissions(int $adminId): array {
        try {
            $stmt = $this->db->query(
                "SELECT permissions FROM {$this->table} WHERE id = ?",
                [$adminId]
            );
            $result = $stmt->fetch();
            return $result ? json_decode($result['permissions'], true) : [];
        } catch (Exception $e) {
            error_log("Error fetching admin permissions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if admin has specific permission
     */
    public function hasPermission(int $adminId, string $permission): bool {
        $permissions = $this->getPermissions($adminId);
        return in_array($permission, $permissions);
    }
} 