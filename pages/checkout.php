<?php
require_once 'includes/header.php';
require_once 'classes/Cart.php';
require_once 'classes/User.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout');
    exit;
}

// Récupérer le contenu du panier
$cart = new Cart();
$cartItems = $cart->getItems($_SESSION['user_id']);

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$total = $cart->getTotal($_SESSION['user_id']);
?>