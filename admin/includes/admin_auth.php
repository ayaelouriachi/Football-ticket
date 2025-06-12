<?php
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../config/session.php');

class AdminAuth {
    private $db;
    private $table = 'admin_users';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Authenticate admin user and create session
     * @param string $email Admin email
     * @param string $password Admin password
     * @return array Success status and message
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, password, name, role, status 
                FROM {$this->table} 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin || !password_verify($password, $admin['password'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }
            
            // Create admin session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['admin_last_activity'] = time();
            
            // Log the successful login
            $this->logActivity($admin['id'], 'login', 'Admin login successful');
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'admin' => [
                    'id' => $admin['id'],
                    'email' => $admin['email'],
                    'name' => $admin['name'],
                    'role' => $admin['role']
                ]
            ];
            
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login'
            ];
        }
    }
    
    /**
     * Log out admin user
     * @return array Success status and message
     */
    public function logout() {
        try {
            // Log the logout activity before destroying session
            if (isset($_SESSION['admin_id'])) {
                $this->logActivity($_SESSION['admin_id'], 'logout', 'Admin logout');
            }
            
            // Clear admin session data
            unset(
                $_SESSION['admin_id'],
                $_SESSION['admin_email'],
                $_SESSION['admin_name'],
                $_SESSION['admin_role'],
                $_SESSION['admin_last_activity']
            );
            
            return [
                'success' => true,
                'message' => 'Logout successful'
            ];
            
        } catch (Exception $e) {
            error_log("Admin logout error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during logout'
            ];
        }
    }
    
    /**
     * Check if user has admin access
     * @param string $requiredRole Minimum required role
     * @return bool True if user has access
     */
    public function hasAccess($requiredRole = null) {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        if ($requiredRole === null) {
            return true;
        }
        
        $roleHierarchy = [
            'super_admin' => 3,
            'admin' => 2,
            'content_manager' => 1
        ];
        
        $userRole = $_SESSION['admin_role'] ?? '';
        $userRoleLevel = $roleHierarchy[$userRole] ?? 0;
        $requiredRoleLevel = $roleHierarchy[$requiredRole] ?? 0;
        
        return $userRoleLevel >= $requiredRoleLevel;
    }
    
    /**
     * Log admin activity
     * @param int $adminId Admin user ID
     * @param string $action Action performed
     * @param string $description Activity description
     * @return bool Success status
     */
    public function logActivity($adminId, $action, $description) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO system_logs (admin_id, action, description, ip_address, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            return $stmt->execute([
                $adminId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR']
            ]);
            
        } catch (PDOException $e) {
            error_log("Error logging admin activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get current admin user data
     * @return array|null Admin data if logged in
     */
    public function getCurrentAdmin() {
        if (!isset($_SESSION['admin_id'])) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'],
            'email' => $_SESSION['admin_email'],
            'name' => $_SESSION['admin_name'],
            'role' => $_SESSION['admin_role']
        ];
    }
    
    /**
     * Check if admin session is active and valid
     * @return bool True if session is valid
     */
    public function validateSession() {
        if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_last_activity'])) {
            return false;
        }
        
        $timeout = 3600; // 1 hour session timeout
        if (time() - $_SESSION['admin_last_activity'] > $timeout) {
            $this->logout();
            return false;
        }
        
        $_SESSION['admin_last_activity'] = time();
        return true;
    }
}

/**
 * Helper function to check if admin is logged in
 * @return bool True if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

/**
 * Helper function to require admin authentication
 * @param string $requiredRole Minimum required role
 * @return void
 */
function requireAdminAuth($requiredRole = null) {
    $auth = new AdminAuth();
    
    if (!$auth->validateSession()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . 'admin/login.php');
        exit;
    }
    
    if ($requiredRole !== null && !$auth->hasAccess($requiredRole)) {
        $_SESSION['error'] = 'You do not have permission to access this page';
        header('Location: ' . BASE_URL . 'admin/index.php');
        exit;
    }
} 