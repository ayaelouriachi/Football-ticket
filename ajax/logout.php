<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../includes/auth_middleware.php');

// Initialize session
SessionManager::init();

// Verify CSRF token
$headers = getallheaders();
$csrfToken = isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : '';

if (!validateCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

try {
    // Get current user before destroying session
    $currentUser = getCurrentUser();
    
    if (!$currentUser) {
        throw new Exception('No user is currently logged in');
    }
    
    // Clear all session data
    $_SESSION = array();
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear any other auth-related cookies
    setcookie('remember_me', '', time() - 3600, '/');
    setcookie('user_token', '', time() - 3600, '/');
    
    // Log the successful logout
    error_log("User ID {$currentUser['id']} logged out successfully");
    
    // Return success response with redirect URL
    echo json_encode([
        'success' => true,
        'message' => 'Déconnexion réussie',
        'redirect' => BASE_URL . 'pages/login.php'
    ]);
    
} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors de la déconnexion'
    ]);
} 