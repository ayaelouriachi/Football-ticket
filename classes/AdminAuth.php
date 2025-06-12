<?php

class AdminAuth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Attempt to log in an admin
     * @param string $email
     * @param string $password
     * @return array ['success' => bool, 'message' => string]
     */
    public function login($email, $password) {
        try {
            // Get admin user
            $sql = "SELECT id, name, email, password FROM admins WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin || !password_verify($password, $admin['password'])) {
                return [
                    'success' => false,
                    'message' => 'Invalid email or password'
                ];
            }

            // Generate admin token
            $adminToken = bin2hex(random_bytes(32));

            // Store in session
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_token'] = $adminToken;

            // Log successful login
            $this->logActivity('login', 'Admin logged in successfully');

            return [
                'success' => true,
                'message' => 'Login successful',
                'token' => $adminToken
            ];

        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'An error occurred during login'
            ];
        }
    }

    /**
     * Log out the current admin
     */
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->logActivity('logout', 'Admin logged out');
            
            // Clear admin session data
            unset($_SESSION['admin_id']);
            unset($_SESSION['admin_name']);
            unset($_SESSION['admin_email']);
            unset($_SESSION['admin_token']);
        }
    }

    /**
     * Log admin activity
     * @param string $action
     * @param string $description
     */
    private function logActivity($action, $description) {
        try {
            $sql = "INSERT INTO admin_activity_log (admin_id, action, description, created_at) 
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $_SESSION['admin_id'] ?? null,
                $action,
                $description
            ]);
        } catch (Exception $e) {
            error_log("Error logging admin activity: " . $e->getMessage());
        }
    }
} 