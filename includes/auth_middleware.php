<?php
require_once 'classes/User.php';

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