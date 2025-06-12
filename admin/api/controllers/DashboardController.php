<?php
require_once __DIR__ . '/../models/Dashboard.php';
require_once __DIR__ . '/../config/auth.php';

class DashboardController {
    private $dashboard;
    
    public function __construct() {
        $this->dashboard = new Dashboard();
    }
    
    /**
     * Get dashboard statistics
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
            $stats = $this->dashboard->getStats();
            
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
     * Get recent orders
     */
    public function getRecentOrders(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get limit from query string
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            $limit = min(max($limit, 1), 50); // Ensure limit is between 1 and 50
            
            // Get recent orders
            $orders = $this->dashboard->getRecentOrders($limit);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $orders
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
     * Get recent activities
     */
    public function getRecentActivities(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get limit from query string
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            $limit = min(max($limit, 1), 50); // Ensure limit is between 1 and 50
            
            // Get recent activities
            $activities = $this->dashboard->getRecentActivities($limit);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $activities
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
     * Get revenue data
     */
    public function getRevenue(): void {
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
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            // Validate dates
            if (!strtotime($startDate) || !strtotime($endDate)) {
                throw new Exception('Invalid date format', 400);
            }
            
            // Get revenue data
            $revenue = $this->dashboard->getRevenueData($startDate, $endDate);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $revenue
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