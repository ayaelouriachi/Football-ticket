<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/MatchController.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/UserController.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse the request URI
$request = parse_url($_SERVER['REQUEST_URI']);
$path = $request['path'];

// Remove base path if exists
$basePath = '/admin/api';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Initialize controllers
$authController = new AuthController();
$dashboardController = new DashboardController();
$matchController = new MatchController();
$orderController = new OrderController();
$userController = new UserController();

// Route the request
try {
    switch ($path) {
        // Authentication routes
        case '/login':
            $authController->login();
            break;
            
        case '/logout':
            $authController->logout();
            break;
            
        case '/profile':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $authController->getProfile();
                    break;
                case 'PUT':
                    $authController->updateProfile();
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Dashboard routes
        case '/dashboard/stats':
            $dashboardController->getStats();
            break;
            
        case '/dashboard/recent-orders':
            $dashboardController->getRecentOrders();
            break;
            
        case '/dashboard/recent-activities':
            $dashboardController->getRecentActivities();
            break;
            
        case '/dashboard/revenue':
            $dashboardController->getRevenue();
            break;
            
        // Match management routes
        case '/matches':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $matchController->getAll();
                    break;
                case 'POST':
                    $matchController->create();
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Match detail routes
        case (preg_match('/^\/matches\/(\d+)$/', $path, $matches) ? true : false):
            $matchId = (int) $matches[1];
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $matchController->getById($matchId);
                    break;
                case 'PUT':
                    $matchController->update($matchId);
                    break;
                case 'DELETE':
                    $matchController->delete($matchId);
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Match status update route
        case (preg_match('/^\/matches\/(\d+)\/status$/', $path, $matches) ? true : false):
            $matchId = (int) $matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $matchController->updateStatus($matchId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Order management routes
        case '/orders':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $orderController->getAll();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Order detail route
        case (preg_match('/^\/orders\/(\d+)$/', $path, $matches) ? true : false):
            $orderId = (int) $matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $orderController->getById($orderId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Order status update route
        case (preg_match('/^\/orders\/(\d+)\/status$/', $path, $matches) ? true : false):
            $orderId = (int) $matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $orderController->updateStatus($orderId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Order refund route
        case (preg_match('/^\/orders\/(\d+)\/refund$/', $path, $matches) ? true : false):
            $orderId = (int) $matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $orderController->processRefund($orderId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Order statistics route
        case '/orders/stats':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $orderController->getStats();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // User management routes
        case '/users':
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $userController->getAll();
                    break;
                case 'POST':
                    $userController->create();
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;
            
        // User detail routes
        case (preg_match('/^\/users\/(\d+)$/', $path, $matches) ? true : false):
            $userId = (int) $matches[1];
            switch ($_SERVER['REQUEST_METHOD']) {
                case 'GET':
                    $userController->getById($userId);
                    break;
                case 'PUT':
                    $userController->update($userId);
                    break;
                case 'DELETE':
                    $userController->delete($userId);
                    break;
                default:
                    throw new Exception('Method not allowed', 405);
            }
            break;
            
        // User status update route
        case (preg_match('/^\/users\/(\d+)\/status$/', $path, $matches) ? true : false):
            $userId = (int) $matches[1];
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $userController->updateStatus($userId);
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // User statistics route
        case '/users/stats':
            if ($_SERVER['REQUEST_METHOD'] === 'GET') {
                $userController->getStats();
            } else {
                throw new Exception('Method not allowed', 405);
            }
            break;
            
        // Add other routes here as we implement them
            
        default:
            throw new Exception('Not found', 404);
    }
} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'error' => $e->getMessage(),
        'code' => $code
    ]);
} 