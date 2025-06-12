<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/auth.php';

class UserController {
    private $user;
    
    public function __construct() {
        $this->user = new User();
    }
    
    /**
     * Get all users
     */
    public function getAll(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get query parameters
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 10;
            
            // Get filters
            $filters = [
                'status' => $_GET['status'] ?? null,
                'search' => $_GET['search'] ?? null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            // Get users
            $result = $this->user->getAll($filters, $page, $limit);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Get user by ID
     */
    public function getById(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get user
            $user = $this->user->getById($id);
            
            if (!$user) {
                throw new Exception('User not found', 404);
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $user
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Create new user
     */
    public function create(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validateUserData($data, true)) {
                throw new Exception('Invalid input data', 400);
            }
            
            // Create user
            $userId = $this->user->create($data);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $userId
                ]
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Update user
     */
    public function update(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validateUserData($data, false)) {
                throw new Exception('Invalid input data', 400);
            }
            
            // Update user
            $success = $this->user->update($id, $data);
            
            // Return success response
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Delete user
     */
    public function delete(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Delete user
            $success = $this->user->delete($id);
            
            // Return success response
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Update user status
     */
    public function updateStatus(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['status'])) {
                throw new Exception('Status is required', 400);
            }
            
            // Update status
            $success = $this->user->updateStatus($id, $data['status']);
            
            // Return success response
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Get user statistics
     */
    public function getStats(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get statistics
            $stats = $this->user->getStats();
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Validate user data
     */
    private function validateUserData(array $data, bool $isCreate): bool {
        // Required fields for create
        if ($isCreate) {
            $required = ['name', 'email', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return false;
                }
            }
        }
        
        // Validate email format if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Validate status if provided
        if (!empty($data['status']) && !in_array($data['status'], ['active', 'inactive', 'banned'])) {
            return false;
        }
        
        // Validate password length if provided
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            return false;
        }
        
        return true;
    }
} 