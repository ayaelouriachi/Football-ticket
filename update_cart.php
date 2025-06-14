<?php
require_once 'includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $itemId = $_POST['item_id'] ?? null;
        $quantity = $_POST['quantity'] ?? null;
        
        if (!$itemId || !$quantity || !is_numeric($quantity) || $quantity < 1 || $quantity > 10) {
            throw new Exception("Quantité invalide");
        }
        
        $cart = new Cart();
        if ($cart->updateCartItem($_SESSION['user_id'], $itemId, $quantity)) {
            header('Location: cart.php');
            exit();
        } else {
            throw new Exception("Erreur lors de la mise à jour du panier");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: cart.php');
exit(); 