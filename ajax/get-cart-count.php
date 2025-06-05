<?php
// Prevent any output before headers
ob_start();

// Include initialization file
require_once dirname(__DIR__) . '/config/init.php';

// Set JSON headers first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Debug logging
    error_log("get-cart-count.php - Session ID: " . session_id());
    error_log("get-cart-count.php - Session state: " . print_r($_SESSION, true));
    
    // Initialize cart
    if (!isset($cart)) {
        $cart = new Cart($db, $_SESSION);
    }
    
    // Get cart count
    $count = $cart->getCartCount();
    
    error_log("get-cart-count.php - Cart count: $count");
    
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    error_log("Error in get-cart-count.php: " . $e->getMessage());
    error_log("get-cart-count.php - Stack trace: " . $e->getTraceAsString());
    
    // Clean any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set error status code
    http_response_code(500);
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue',
        'count' => 0
    ]);
}

// Ensure script ends here
exit();
?>