<?php
class SessionManager {
    private static $started = false;
    
    public static function init() {
        if (self::$started) {
            return;
        }
        
        if (session_status() === PHP_SESSION_NONE) {
            // Configuration sécurisée des sessions avant le démarrage
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            // En production, activer cookie_secure
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }
            
            // Nom de session personnalisé
            session_name('FOOTBALL_TICKETS_SESSION');
            
            // Durée de vie de la session (2 heures)
            ini_set('session.gc_maxlifetime', 7200);
            ini_set('session.cookie_lifetime', 7200);
            
            // Démarrage de la session
            session_start();
        }
        
        // Régénération de l'ID de session pour éviter la fixation
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
        
        // Vérification de l'expiration de la session
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > 7200)) {
            self::destroy();
            return;
        }
        
        $_SESSION['last_activity'] = time();
        self::$started = true;
    }
    
    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }
    
    public static function remove($key) {
        self::init();
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        self::init();
        session_unset();
        session_destroy();
        self::$started = false;
    }
    
    public static function regenerateId() {
        self::init();
        session_regenerate_id(true);
    }
    
    // Génération de token CSRF
    public static function generateCSRFToken() {
        self::init();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Vérification du token CSRF
    public static function verifyCSRFToken($token) {
        self::init();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>