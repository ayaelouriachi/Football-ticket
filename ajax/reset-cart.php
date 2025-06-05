<?php
// Prevent any output before headers
ob_start();

// Start session and include required files
session_start();
require_once '../config/database.php';
require_once '../classes/Cart.php';

// Debug
error_log("reset-cart.php - Session ID: " . session_id());
error_log("reset-cart.php - Session state before reset: " . print_r($_SESSION, true));

try {
    // Set JSON headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache');
    
    // Initialize cart
    $cart = new Cart($db, $_SESSION);
    
    // Reset cart
    $result = $cart->resetCart();
    
    error_log("reset-cart.php - Cart reset result: " . print_r($result, true));
    error_log("reset-cart.php - Session state after reset: " . print_r($_SESSION, true));
    
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Cart has been reset successfully',
        'cart_count' => 0
    ]);
    
} catch (Exception $e) {
    error_log("reset-cart.php - Error: " . $e->getMessage());
    error_log("reset-cart.php - Stack trace: " . $e->getTraceAsString());
    
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set error status code
    http_response_code(500);
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error resetting cart: ' . $e->getMessage()
    ]);
}

// Ensure no more output
exit(); 