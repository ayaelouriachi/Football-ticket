<?php
require_once '../config/init.php';

header('Content-Type: application/json');

try {
    // Validate request
    if (!isset($_POST['category_id']) || !isset($_POST['quantity'])) {
        throw new Exception('Missing required parameters');
    }

    $category_id = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$category_id || !$quantity || $quantity < 1) {
        throw new Exception('Invalid parameters');
    }

    // Initialize cart
    $cart = new Cart($db, $_SESSION);
    
    // Update cart item
    $result = $cart->updateItem($category_id, $quantity);
    
    if (!$result['success']) {
        throw new Exception($result['message']);
    }
    
    // Get updated cart contents
    $cart_contents = $cart->getCartContents();
    $result['cart'] = $cart_contents;
    $result['cart_count'] = $cart->getCartCount();
    
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 