<?php
require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../includes/auth.php');

// Set JSON response header
header('Content-Type: application/json');

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['error' => 'Direct access not allowed']);
    exit;
}

// Initialize auth
$auth = new AdminAuth($db, $_SESSION);

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Update last activity time
$_SESSION['admin_last_activity'] = time();

// Log activity
$auth->logActivity($_SESSION['admin_id'], 'session_extended', 'Session prolongée');

// Send success response
echo json_encode([
    'success' => true,
    'message' => 'Session prolongée avec succès',
    'new_expiry' => time() + ADMIN_SETTINGS['session_lifetime']
]); 