<?php
/**
 * Admin Authentication Functions
 */

require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/../../includes/flash_messages.php');

class AdminAuth {
    private $db;
    private $session;
    
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    
    const SESSION_LIFETIME = 3600; // 1 hour
    
    public function __construct($db) {
        $this->db = $db;
        $this->session = $_SESSION;
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Extend session lifetime on activity
        if (isset($_SESSION['admin_id'])) {
            $_SESSION['last_activity'] = time();
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, first_name, last_name, role, status, last_login
                FROM admin_users 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['admin_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $this->logout();
                return null;
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur : " . $e->getMessage());
            return null;
        }
    }
    
    public function login($email, $password) {
        try {
            // Check for too many failed attempts
            if ($this->isIpBlocked() || $this->isAccountBlocked($email)) {
                throw new Exception("Trop de tentatives de connexion. Veuillez réessayer plus tard.");
            }
            
            // Get user
            $stmt = $this->db->prepare("
                SELECT id, email, password_hash, first_name, last_name, role, status 
                FROM admin_users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Debug: Afficher les informations de l'utilisateur
            error_log("Tentative de connexion pour l'email: " . $email);
            error_log("Utilisateur trouvé: " . ($user ? "Oui" : "Non"));
            if ($user) {
                error_log("Status: " . $user['status']);
                error_log("Role: " . $user['role']);
                error_log("Password verification: " . (password_verify($password, $user['password_hash']) ? "Succès" : "Échec"));
            }
            
            // Verify user exists and password is correct
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $this->logFailedAttempt($email);
                if (!$user) {
                    throw new Exception("Email ou mot de passe incorrect. (Utilisateur non trouvé)");
                } else {
                    throw new Exception("Email ou mot de passe incorrect. (Mot de passe invalide)");
                }
            }
            
            // Check if user is active
            if ($user['status'] !== 'active') {
                throw new Exception("Ce compte est " . $user['status'] . ".");
            }
            
            // Clear failed attempts
            $this->clearFailedAttempts($email);
            
            // Update last login
            $stmt = $this->db->prepare("
                UPDATE admin_users 
                SET last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // Set session
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Log activity
            $this->logActivity($user['id'], 'login', 'auth', null, 'Connexion réussie');
            
            return true;
            
        } catch (Exception $e) {
            error_log("Erreur de connexion: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            $this->logActivity($_SESSION['admin_id'], 'logout', 'auth', null, 'Déconnexion');
        }
        
        // Destroy session
        session_unset();
        session_destroy();
        
        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }
    
    public function isLoggedIn() {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }
        
        // Check session timeout
        $timeout = $this->getSessionTimeout();
        if (time() - $_SESSION['last_activity'] > $timeout) {
            $this->logout();
            return false;
        }
        
        return true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /football_tickets/admin/login.php');
            exit;
        }
    }
    
    public function requireRole($roles) {
        $this->requireLogin();
        
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        if (!in_array($_SESSION['admin_role'], $roles)) {
            $this->logActivity($_SESSION['admin_id'], 'access_denied', 'auth', null, 
                             'Tentative d\'accès non autorisé à ' . $_SERVER['REQUEST_URI']);
            die("Accès non autorisé.");
        }
    }
    
    public function hasPermission($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Super admin has all permissions
        if ($_SESSION['admin_role'] === self::ROLE_SUPER_ADMIN) {
            return true;
        }
        
        // Define role permissions
        $permissions = [
            self::ROLE_ADMIN => [
                'manage_matches',
                'manage_teams',
                'manage_stadiums',
                'manage_tickets',
                'view_reports',
                'manage_users'
            ],
            self::ROLE_MODERATOR => [
                'view_matches',
                'view_teams',
                'view_stadiums',
                'view_tickets'
            ]
        ];
        
        return isset($permissions[$_SESSION['admin_role']]) && 
               in_array($permission, $permissions[$_SESSION['admin_role']]);
    }
    
    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            $this->logActivity($_SESSION['admin_id'], 'permission_denied', 'auth', null,
                             'Tentative d\'accès sans permission : ' . $permission);
            die("Permission refusée.");
        }
    }
    
    private function isIpBlocked() {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM admin_failed_logins 
            WHERE ip_address = ? 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$_SERVER['REMOTE_ADDR']]);
        return $stmt->fetchColumn() >= 5;
    }
    
    private function isAccountBlocked($email) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) 
            FROM admin_failed_logins 
            WHERE email = ? 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() >= 10;
    }
    
    private function logFailedAttempt($email) {
        $stmt = $this->db->prepare("
            INSERT INTO admin_failed_logins (email, ip_address) 
            VALUES (?, ?)
        ");
        $stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);
    }
    
    private function clearFailedAttempts($email) {
        $stmt = $this->db->prepare("
            DELETE FROM admin_failed_logins 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
    }
    
    private function logActivity($adminId, $action, $entityType = null, $entityId = null, $details = null) {
        $stmt = $this->db->prepare("
            INSERT INTO admin_activity_logs 
            (admin_id, action, entity_type, entity_id, details, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminId,
            $action,
            $entityType,
            $entityId,
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
    }
    
    private function getSessionTimeout() {
        $stmt = $this->db->prepare("
            SELECT setting_value 
            FROM admin_settings 
            WHERE setting_key = 'session_lifetime'
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn() ?: self::SESSION_LIFETIME;
    }
}
?> 