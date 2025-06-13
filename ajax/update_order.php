<?php
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../includes/auth_middleware.php');
require_once(__DIR__ . '/../includes/json_response.php');
require_once(__DIR__ . '/../includes/logger.php');
require_once(__DIR__ . '/../classes/Order.php');
require_once(__DIR__ . '/../classes/Cart.php');
require_once(__DIR__ . '/../classes/Payment.php');

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Nettoyer tout output précédent
while (ob_get_level()) {
    ob_end_clean();
}

// Headers CORS et JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Fonction de validation des données
function validateOrderData($data) {
    $errors = [];
    
    // Validation des champs obligatoires
    if (!isset($data['order_id']) || empty($data['order_id'])) {
        $errors[] = 'order_id est requis';
    }
    
    if (!isset($data['payment_data']) || empty($data['payment_data'])) {
        $errors[] = 'payment_data est requis';
    } else {
        $paymentData = $data['payment_data'];
        if (!isset($paymentData['id'])) {
            $errors[] = 'payment_data.id est requis';
        }
        if (!isset($paymentData['status'])) {
            $errors[] = 'payment_data.status est requis';
        }
        if (!isset($paymentData['purchase_units'][0]['amount']['value'])) {
            $errors[] = 'payment_data.purchase_units[0].amount.value est requis';
        }
    }
    
    return $errors;
}

try {
    // Logger toutes les données reçues
    Logger::debug("=== NOUVELLE REQUÊTE UPDATE ORDER ===", [
        'method' => $_SERVER['REQUEST_METHOD'],
        'headers' => getallheaders(),
        'post' => $_POST,
        'get' => $_GET
    ]);
    
    // Vérifier la méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée', 405);
    }

    // Vérifier si la requête est AJAX
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        throw new Exception('Requête non autorisée', 403);
    }

    // Vérifier l'authentification
    if (!isLoggedIn()) {
        throw new Exception('Utilisateur non authentifié', 401);
    }

    // Récupérer et parser les données JSON
    $rawInput = file_get_contents('php://input');
    Logger::debug("Données brutes reçues", ['raw_input' => $rawInput]);

    $jsonData = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Données JSON invalides: ' . json_last_error_msg(), 400);
    }

    Logger::debug("Données JSON décodées", ['json_data' => $jsonData]);

    // Valider les données
    $validationErrors = validateOrderData($jsonData);
    if (!empty($validationErrors)) {
        Logger::error("Erreurs de validation", ['errors' => $validationErrors]);
        throw new Exception('Données manquantes ou invalides: ' . implode(', ', $validationErrors), 400);
    }

    // Récupérer et valider l'ID de commande
    $orderId = $_SESSION['order_id'] ?? null;
    if (!$orderId || $orderId != $jsonData['order_id']) {
        Logger::error("ID de commande invalide", [
            'session_order_id' => $orderId,
            'received_order_id' => $jsonData['order_id']
        ]);
        throw new Exception('ID de commande invalide', 400);
    }

    // Valider que la commande appartient à l'utilisateur
    $order = new Order();
    $orderData = $order->getOrderById($orderId);
    if (!$orderData) {
        Logger::error("Commande non trouvée", ['order_id' => $orderId]);
        throw new Exception('Commande non trouvée', 404);
    }
    if ($orderData['user_id'] != $_SESSION['user_id']) {
        Logger::error("Accès non autorisé à la commande", [
            'order_user_id' => $orderData['user_id'],
            'session_user_id' => $_SESSION['user_id']
        ]);
        throw new Exception('Accès non autorisé à la commande', 403);
    }

    // Extraire les données PayPal
    $paypalData = $jsonData['payment_data'];
    Logger::payment("Données PayPal reçues", ['paypal_data' => $paypalData]);

    // Validation du statut PayPal
    $validStatuses = ['COMPLETED', 'APPROVED'];
    if (!in_array($paypalData['status'], $validStatuses)) {
        Logger::error("Statut PayPal invalide", ['status' => $paypalData['status']]);
        throw new Exception('Statut de paiement invalide', 400);
    }

    // Validation du montant du paiement
    $expectedAmount = $orderData['total_amount'];
    $paidAmount = $paypalData['purchase_units'][0]['amount']['value'] ?? 0;
    $paidCurrency = $paypalData['purchase_units'][0]['amount']['currency_code'] ?? 'EUR';

    if (floatval($paidAmount) !== floatval($expectedAmount)) {
        Logger::error("Montant du paiement incorrect", [
            'expected' => $expectedAmount,
            'received' => $paidAmount
        ]);
        throw new Exception('Montant du paiement incorrect', 400);
    }

    // Créer l'enregistrement de paiement
    $payment = new Payment();
    $paymentResult = $payment->createPayment(
        $orderId,
        $paypalData['id'],
        $paidAmount,
        $paidCurrency
    );

    if (!$paymentResult) {
        Logger::error("Erreur lors de l'enregistrement du paiement", [
            'order_id' => $orderId,
            'transaction_id' => $paypalData['id']
        ]);
        throw new Exception('Erreur lors de l\'enregistrement du paiement', 500);
    }

    Logger::payment("Paiement enregistré avec succès", [
        'payment_id' => $paymentResult,
        'order_id' => $orderId
    ]);

    // Vider le panier
    $cart = new Cart($db, $_SESSION);
    $cart->clearCart();
    Logger::debug("Panier vidé avec succès");

    // Nettoyer la session
    unset($_SESSION['order_id']);

    // Envoyer la réponse de succès
    sendJsonResponse(true, [
        'order_id' => $orderId,
        'payment_id' => $paypalData['id'],
        'redirect_url' => BASE_URL . 'payment-success.php?order_id=' . $orderId
    ], 'Paiement enregistré avec succès');

} catch (Exception $e) {
    $httpCode = $e->getCode() ?: 500;
    
    Logger::error("Erreur lors de la mise à jour de la commande", [
        'message' => $e->getMessage(),
        'code' => $httpCode,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    sendJsonResponse(false, [
        'error_code' => $httpCode,
        'error_details' => $e->getMessage(),
        'received_data' => $jsonData ?? null
    ], 'Erreur: ' . $e->getMessage(), $httpCode);
} 