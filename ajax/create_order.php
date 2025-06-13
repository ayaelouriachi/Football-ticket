<?php
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../includes/auth_middleware.php');
require_once(__DIR__ . '/../includes/cors.php');
require_once(__DIR__ . '/../classes/Order.php');
require_once(__DIR__ . '/../classes/Cart.php');

// Set JSON response header
header('Content-Type: application/json');

// Function to send error response
function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'error_code' => $code
    ]);
    exit;
}

// Function to log detailed error information
function logError($error, $context = []) {
    $errorData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $error instanceof Exception ? $error->getMessage() : $error,
        'trace' => $error instanceof Exception ? $error->getTraceAsString() : debug_backtrace(),
        'context' => $context
    ];
    
    error_log(json_encode($errorData));
}

try {
    // Check if request is AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        sendErrorResponse('Direct access not allowed', 403);
    }

    // Check if user is logged in
    if (!isLoggedIn()) {
        sendErrorResponse('User not authenticated', 401);
    }

    // Get and validate JSON data
    $jsonData = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('Invalid JSON data: ' . json_last_error_msg());
    }
    
    if (!isset($jsonData['amount']) || !is_numeric($jsonData['amount'])) {
        sendErrorResponse('Invalid amount');
    }
    
    // Initialize cart and get contents
    $cart = new Cart($db, $_SESSION);
    
    // Validate cart first
    $validation = $cart->validateCart();
    if (!$validation['success']) {
        logError($validation['message'], ['cart_data' => $cart->getCartContents()]);
        sendErrorResponse($validation['message']);
    }
    
    $cartContents = $cart->getCartContents();
    
    if (empty($cartContents['items'])) {
        sendErrorResponse('Cart is empty');
    }
    
    // Convert cart total from DH to EUR (1 EUR = 10 DH)
    $cartTotalDH = $cartContents['total'];
    $cartTotalEUR = $cartTotalDH / 10;
    
    // Format amounts for comparison
    $cartTotalEUR = number_format($cartTotalEUR, 2, '.', '');
    $requestAmount = number_format($jsonData['amount'], 2, '.', '');
    
    if ($cartTotalEUR != $requestAmount) {
        logError('Amount mismatch', [
            'expected_eur' => $cartTotalEUR,
            'received_eur' => $requestAmount,
            'cart_total_dh' => $cartTotalDH,
            'cart_data' => $cartContents
        ]);
        sendErrorResponse('Montant incorrect. Attendu: ' . $cartTotalEUR . ' EUR, Reçu: ' . $requestAmount . ' EUR');
    }
    
    // Create new order
    $order = new Order();
    $result = $order->createOrder(
        $_SESSION['user_id'],
        $cartContents['items']
    );
    
    if (!$result['success']) {
        logError($result['message'], [
            'user_id' => $_SESSION['user_id'],
            'cart_data' => $cartContents
        ]);
        sendErrorResponse($result['message']);
    }
    
    // Store order ID in session
    $_SESSION['order_id'] = $result['order_id'];
    
    // Return success response with both DH and EUR amounts
    echo json_encode([
        'success' => true,
        'order_id' => $result['order_id'],
        'total_dh' => $cartTotalDH,
        'total_eur' => $cartTotalEUR,
        'currency' => PAYPAL_CURRENCY
    ]);
    
} catch (Exception $e) {
    logError($e, [
        'request_data' => $jsonData ?? null,
        'cart_data' => isset($cart) ? $cart->getCartContents() : null
    ]);
    sendErrorResponse('Erreur technique lors de la création de la commande: ' . $e->getMessage());
} 