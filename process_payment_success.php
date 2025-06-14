<?php
session_start();
header('Content-Type: application/json'); // Important: Set header to JSON

require_once 'config/database.php';
require_once 'classes/Cart.php';

$response = ['success' => false];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'Utilisateur non connecté.';
    echo json_encode($response);
    exit();
}

// Get data from the POST request (sent via fetch)
$input = json_decode(file_get_contents('php://input'), true);
$transaction_id = $input['transaction_id'] ?? null;
$order_id = $input['order_id'] ?? null;


if (!$transaction_id || !$order_id) {
    $response['error'] = 'Transaction invalide ou informations manquantes.';
    echo json_encode($response);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    $cart = new Cart($db);

    // 1. Update the order status
    $stmt = $db->prepare("UPDATE orders SET status = 'completed', transaction_id = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$transaction_id, $order_id, $_SESSION['user_id']]);

    // 2. Clear the user's cart
    $cart->clearCart($_SESSION['user_id']);
    
    // 3. Set the last order ID in session for potential future use
    $_SESSION['last_order_id'] = $order_id;

    // 4. Send a success response back to the JavaScript
    $response['success'] = true;
    $response['order_id'] = $order_id;
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    // Handle errors and send a JSON error response
    error_log("Payment processing error for order $order_id: " . $e->getMessage());
    $response['error'] = "Une erreur est survenue lors de la finalisation de votre commande.";
    echo json_encode($response);
    exit();
}
?>