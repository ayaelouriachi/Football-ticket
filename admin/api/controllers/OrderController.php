<?php
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../config/auth.php';

class OrderController {
    private $order;
    
    public function __construct() {
        $this->order = new Order();
    }
    
    /**
     * Get all orders
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
                'user_id' => isset($_GET['user_id']) ? (int) $_GET['user_id'] : null,
                'match_id' => isset($_GET['match_id']) ? (int) $_GET['match_id'] : null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            // Get orders
            $result = $this->order->getAll($filters, $page, $limit);
            
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
     * Get order by ID
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
            
            // Get order
            $order = $this->order->getById($id);
            
            if (!$order) {
                throw new Exception('Order not found', 404);
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $order
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
     * Update order status
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
            $success = $this->order->updateStatus($id, $data['status']);
            
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
     * Process refund
     */
    public function processRefund(int $id): void {
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
            
            if (!isset($data['reason'])) {
                throw new Exception('Refund reason is required', 400);
            }
            
            // Process refund
            $success = $this->order->processRefund($id, $data['reason']);
            
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
     * Get order statistics
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
            
            // Get date range from query string
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            // Get statistics
            $stats = $this->order->getStats($startDate, $endDate);
            
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
} 