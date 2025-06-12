<?php
require_once(__DIR__ . '/config/session.php');
require_once(__DIR__ . '/config/constants.php');
require_once(__DIR__ . '/includes/auth_middleware.php');

// Initialize session
SessionManager::init();

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

// Récupérer l'ID de la commande depuis la session
$orderId = $_SESSION['order_id'] ?? null;

if ($orderId) {
    // Mettre à jour le statut de la commande
    require_once(__DIR__ . '/classes/Order.php');
    $order = new Order();
    $order->updateStatus($orderId, ORDER_STATUS_CANCELLED);
    
    // Définir le message d'erreur
    setFlashMessage('error', 'Le paiement a été annulé. Vous pouvez réessayer ultérieurement.');
}

// Rediriger vers le panier
header('Location: ' . BASE_URL . 'cart.php');
exit;
?> 