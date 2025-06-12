<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../includes/auth_middleware.php');

// Initialize session
SessionManager::init();

// Set JSON response header
header('Content-Type: application/json');

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

// Verify authentication status
$response = [
    'isAuthenticated' => isLoggedIn(),
    'timestamp' => time()
];

if (!$response['isAuthenticated']) {
    $response['redirectUrl'] = BASE_URL . 'pages/login.php';
    if (isset($_SERVER['HTTP_REFERER'])) {
        $response['redirectUrl'] .= '?redirect=' . urlencode($_SERVER['HTTP_REFERER']);
    }
}

// Send response
echo json_encode($response);
exit; 