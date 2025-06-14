<?php
require_once 'includes/header.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $itemId = $_POST['item_id'] ?? null;
        
        if (!$itemId) {
            throw new Exception("Article invalide");
        }
        
        $cart = new Cart();
        if ($cart->removeFromCart($_SESSION['user_id'], $itemId)) {
            $_SESSION['success'] = "Article supprimé du panier";
        } else {
            throw new Exception("Erreur lors de la suppression de l'article");
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: cart.php');
exit(); 