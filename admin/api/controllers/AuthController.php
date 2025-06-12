<?php
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../config/auth.php';

class AuthController {
    private $admin;
    
    public function __construct() {
        $this->admin = new Admin();
    }
    
    /**
     * Handle admin login
     */
    public function login(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['email']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Email and password are required']);
                return;
            }
            
            // Authenticate admin
            $admin = $this->admin->authenticate($data['email'], $data['password']);
            if (!$admin) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid credentials']);
                return;
            }
            
            // Generate JWT token
            $token = Auth::generateToken([
                'admin_id' => $admin['id'],
                'role' => $admin['role']
            ]);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'token' => $token,
                'admin' => $admin
            ]);
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Authentication failed']);
        }
    }
    
    /**
     * Handle admin logout
     */
    public function logout(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            // Log the logout activity
            $adminId = Auth::getAdminId();
            if ($adminId) {
                $this->admin->logActivity($adminId, 'logout', 'Successfully logged out');
            }
            
            // Return success response
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Logout failed']);
        }
    }
    
    /**
     * Get admin profile
     */
    public function getProfile(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            // Get admin profile
            $adminId = Auth::getAdminId();
            $profile = $this->admin->getById($adminId);
            
            if (!$profile) {
                http_response_code(404);
                echo json_encode(['error' => 'Profile not found']);
                return;
            }
            
            // Return profile data
            echo json_encode([
                'success' => true,
                'profile' => $profile
            ]);
            
        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch profile']);
        }
    }
    
    /**
     * Update admin profile
     */
    public function updateProfile(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                return;
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                http_response_code(400);
                echo json_encode(['error' => 'No data provided']);
                return;
            }
            
            // Update profile
            $adminId = Auth::getAdminId();
            $success = $this->admin->updateProfile($adminId, $data);
            
            if (!$success) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                return;
            }
            
            // Return success response
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile']);
        }
    }
} 