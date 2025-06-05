<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth_middleware.php';
require_once '../includes/security.php';
require_once '../classes/Cart.php';
require_once '../classes/User.php';

// Initialiser la session
SessionManager::init();

// Headers pour les réponses JSON
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'action demandée
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Action non définie'];

try {
    // Validate CSRF token
    if (!validateCSRFToken(getRequestHeader('X-CSRF-Token'))) {
        throw new Exception('Invalid CSRF token');
    }

    switch ($action) {
        case 'add_to_cart':
            $response = handleAddToCart();
            break;
            
        case 'update_cart':
            $response = handleUpdateCart();
            break;
            
        case 'remove_from_cart':
            $response = handleRemoveFromCart();
            break;
            
        case 'get_cart_count':
            $response = handleGetCartCount();
            break;
            
        case 'check_login_status':
            $response = handleCheckLoginStatus();
            break;
            
        case 'validate_email':
            $response = handleValidateEmail();
            break;
            
        default:
            $response = ['success' => false, 'message' => 'Action non reconnue'];
    }
    
} catch (Exception $e) {
    ErrorHandler::logCustomError("AJAX Error: " . $e->getMessage(), [
        'action' => $action,
        'user_id' => SessionManager::get('user_id'),
        'post_data' => $_POST
    ]);
    
    $response = [
        'success' => false,
        'message' => APP_ENV === 'development' ? $e->getMessage() : 'Erreur technique'
    ];
}

echo json_encode($response);

// Fonctions de gestion des actions AJAX

function handleAddToCart() {
    AuthMiddleware::requireLogin();
    AuthMiddleware::verifyCsrfToken();
    
    $userId = SessionManager::get('user_id');
    $ticketCategoryId = (int)($_POST['ticket_category_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    
    if ($ticketCategoryId <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Données invalides'];
    }
    
    $cart = new Cart();
    $result = $cart->addItem($userId, $ticketCategoryId, $quantity);
    
    if ($result['success']) {
        $result['cart_count'] = $cart->getCartItemCount($userId);
    }
    
    return $result;
}

function handleUpdateCart() {
    AuthMiddleware::requireLogin();
    AuthMiddleware::verifyCsrfToken();
    
    $userId = SessionManager::get('user_id');
    $ticketCategoryId = (int)($_POST['ticket_category_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    if ($ticketCategoryId <= 0) {
        return ['success' => false, 'message' => 'Données invalides'];
    }
    
    $cart = new Cart();
    $result = $cart->updateQuantity($userId, $ticketCategoryId, $quantity);
    
    if ($result['success']) {
        $result['cart_count'] = $cart->getCartItemCount($userId);
        $result['cart_total'] = $cart->getCartTotal($userId);
    }
    
    return $result;
}

function handleRemoveFromCart() {
    AuthMiddleware::requireLogin();
    AuthMiddleware::verifyCsrfToken();
    
    $userId = SessionManager::get('user_id');
    $ticketCategoryId = (int)($_POST['ticket_category_id'] ?? 0);
    
    if ($ticketCategoryId <= 0) {
        return ['success' => false, 'message' => 'Données invalides'];
    }
    
    $cart = new Cart();
    $result = $cart->removeItem($userId, $ticketCategoryId);
    
    if ($result['success']) {
        $result['cart_count'] = $cart->getCartItemCount($userId);
        $result['cart_total'] = $cart->getCartTotal($userId);
    }
    
    return $result;
}

function handleGetCartCount() {
    if (!User::isLoggedIn()) {
        return ['success' => true, 'count' => 0];
    }
    
    $userId = SessionManager::get('user_id');
    $cart = new Cart();
    
    return [
        'success' => true,
        'count' => $cart->getCartItemCount($userId),
        'total' => $cart->getCartTotal($userId)
    ];
}

function handleCheckLoginStatus() {
    return [
        'success' => true,
        'logged_in' => User::isLoggedIn(),
        'is_admin' => User::isAdmin(),
        'user_name' => SessionManager::get('user_name', '')
    ];
}

function handleValidateEmail() {
    $email = SecurityHelper::sanitizeInput($_POST['email'] ?? '');
    
    if (!SecurityHelper::validateEmail($email)) {
        return ['success' => false, 'message' => 'Format d\'email invalide'];
    }
    
    $user = new User();
    if ($user->emailExists($email)) {
        return ['success' => false, 'message' => 'Cet email est déjà utilisé'];
    }
    
    return ['success' => true, 'message' => 'Email disponible'];
}
?>