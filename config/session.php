<?php
class SessionManager {
    private static $initialized = false;
    
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Prevent headers already sent errors
        if (!headers_sent()) {
            // Configuration des cookies de session
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            // Configuration de la session
            ini_set('session.use_strict_mode', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', 1);
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.gc_maxlifetime', 3600);
            
            // Démarrer la session si elle n'est pas déjà active
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            self::$initialized = true;
        }
    }
    
    public static function destroy() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public static function regenerate() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    public static function setFlashMessage($type, $message) {
        $_SESSION['flash_messages'][$type][] = $message;
    }
    
    public static function getFlashMessages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    public static function isActive() {
        return session_status() === PHP_SESSION_ACTIVE;
    }
    
    public static function set($key, $value) {
        self::init();
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        self::init();
        return $_SESSION[$key] ?? $default;
    }
    
    public static function remove($key) {
        self::init();
        unset($_SESSION[$key]);
    }
    
    public static function has($key) {
        self::init();
        return isset($_SESSION[$key]);
    }
    
    public static function clear() {
        self::init();
        $_SESSION = array();
    }
}
?>