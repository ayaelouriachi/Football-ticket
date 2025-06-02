<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../classes/Cart.php';

echo "Testing cart functionality...\n\n";

try {
    // Initialize cart
    $cart = new Cart($db, $_SESSION);
    
    // Clear cart first
    $result = $cart->clearCart();
    echo $result['success'] ? "✅" : "❌", " Cart cleared\n";
    
    // Get a ticket category for testing
    $stmt = $db->query("SELECT id, price FROM ticket_categories WHERE remaining_tickets > 0 LIMIT 1");
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        die("❌ No available tickets found for testing\n");
    }
    
    // Test adding item
    $result = $cart->addItem($category['id'], 1);
    echo $result['success'] ? "✅" : "❌", " Add item to cart: {$result['message']}\n";
    
    // Test getting cart contents
    $contents = $cart->getCartContents();
    echo !empty($contents['items']) ? "✅" : "❌", " Cart has items\n";
    echo "Cart total: {$contents['total']}\n";
    
    // Test updating quantity
    $result = $cart->updateItem($category['id'], 2);
    echo $result['success'] ? "✅" : "❌", " Update quantity: {$result['message']}\n";
    
    // Test cart validation
    $result = $cart->validateCart();
    echo $result['success'] ? "✅" : "❌", " Cart validation: {$result['message']}\n";
    
    // Test removing item
    $result = $cart->removeItem($category['id']);
    echo $result['success'] ? "✅" : "❌", " Remove item: {$result['message']}\n";
    
    // Verify cart is empty
    $contents = $cart->getCartContents();
    echo empty($contents['items']) ? "✅" : "❌", " Cart is empty after removal\n";
    
    echo "\nAll cart tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error testing cart: " . $e->getMessage() . "\n";
    exit(1);
} 