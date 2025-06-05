<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/flash_messages.php');

/**
 * Get the currently logged in user
 * @return array|null User data if logged in, null otherwise
 */
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user ?: null;
    } catch (PDOException $e) {
        error_log("Error fetching current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if a user is logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && getCurrentUser() !== null;
}

/**
 * Require authentication for a page
 * @param string $redirect_url URL to redirect to if not authenticated
 * @return void
 */
function requireAuth($redirect_url = null) {
    if (!isLoggedIn()) {
        $redirect = $redirect_url ?? BASE_URL . 'pages/login.php';
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: $redirect");
        exit;
    }
}

/**
 * Require admin role for a page
 * @param string $redirect_url URL to redirect to if not admin
 * @return void
 */
function requireAdmin($redirect_url = null) {
    requireAuth($redirect_url);
    $user = getCurrentUser();
    
    if ($user['role'] !== 'admin') {
        $redirect = $redirect_url ?? BASE_URL;
        header("Location: $redirect");
        exit;
    }
}

/**
 * Generate a new CSRF token
 * @return string The generated token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}

class AuthMiddleware {
    
    // Vérifier si l'utilisateur est connecté
    public static function requireLogin($redirectTo = 'login.php') {
        if (!User::isLoggedIn()) {
            header("Location: " . BASE_URL . $redirectTo);
            exit();
        }
    }
    
    // Vérifier les droits administrateur
    public static function requireAdmin($redirectTo = 'index.php') {
        self::requireLogin();
        
        if (!User::isAdmin()) {
            header("Location: " . BASE_URL . $redirectTo);
            exit();
        }
    }
    
    // Vérifier le token CSRF
    public static function verifyCsrfToken($token = null) {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        
        if (!SessionManager::verifyCSRFToken($token)) {
            http_response_code(403);
            die('Token CSRF invalide');
        }
    }
    
    // Vérifier la méthode HTTP
    public static function requireMethod($method) {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            http_response_code(405);
            die('Méthode non autorisée');
        }
    }
    
    // Limiter le taux de requêtes (simple)
    public static function rateLimit($key, $maxRequests = 10, $timeWindow = 60) {
        $cacheKey = 'rate_limit_' . $key;
        $requests = SessionManager::get($cacheKey, []);
        $now = time();
        
        // Nettoyer les anciennes requêtes
        $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });
        
        if (count($requests) >= $maxRequests) {
            http_response_code(429);
            die('Trop de requêtes, veuillez réessayer plus tard');
        }
        
        $requests[] = $now;
        SessionManager::set($cacheKey, $requests);
    }
}
?>