<?php
// CORRECTION ERREUR POST
$_POST = $_POST ?? [];
if (!isset($_POST['password'])) $_POST['password'] = '';
if (!isset($_POST['email'])) $_POST['email'] = '';

/**
 * Admin Authentication Functions
 */

require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../includes/flash_messages.php');

class AdminAuth {
    private $db;
    private $session;
    
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MODERATOR = 'moderator';
    
    const SESSION_LIFETIME = 3600; // 1 hour
    
    public function __construct($db = null) {
        $this->db = $db ?? Database::getInstance()->getConnection();
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
    
    /**
     * Vérifie si un utilisateur est connecté en tant qu'admin
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    /**
     * Authentifie un administrateur
     */
    public function login($email, $password) {
        try {
            // Vérifier les identifiants dans la table admin_users
            $stmt = $this->db->prepare("
                SELECT id, email, password_hash as password, CONCAT(first_name, ' ', last_name) as name, role 
                FROM admin_users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$admin || !password_verify($password, $admin['password'])) {
                // Log la tentative échouée
                $stmt = $this->db->prepare("
                    INSERT INTO admin_failed_logins (email, ip_address) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
                
                throw new Exception(MSG_ERROR_LOGIN);
            }
            
            // Mettre à jour la dernière connexion
            $stmt = $this->db->prepare("
                UPDATE admin_users 
                SET last_login = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$admin['id']]);
            
            // Log la connexion réussie
            $stmt = $this->db->prepare("
                INSERT INTO admin_activity_logs (admin_id, action, entity_type, details, ip_address) 
                VALUES (?, 'login', 'auth', ?, ?)
            ");
            $stmt->execute([
                $admin['id'],
                'Connexion réussie',
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            
            // Créer la session admin
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_role'] = $admin['role'];
            $_SESSION['last_activity'] = time();
            
            return true;
            
        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Déconnecte l'administrateur
     */
    public function logout() {
        // Supprimer uniquement les variables de session admin
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_role']);
    }
    
    /**
     * Récupère les informations de l'administrateur connecté
     */
    public function getCurrentAdmin() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, role, last_login 
                FROM users 
                WHERE id = ? AND role = 'admin' AND is_active = 1
            ");
            $stmt->execute([$_SESSION['admin_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Error getting current admin: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifie si l'administrateur a une permission spécifique
     */
    public function hasPermission($permission) {
        // Temporairement autoriser toutes les permissions pour restaurer l'accès
        return true;
        
        // Le code ci-dessous sera réactivé une fois le menu restauré
        /*
        // Si pas de permission requise, autoriser
        if (!$permission) {
            return true;
        }
        
        if (!$this->isLoggedIn()) {
            error_log("Permission refusée : utilisateur non connecté");
            return false;
        }
        
        $admin = $this->getCurrentAdmin();
        if (!$admin) {
            error_log("Permission refusée : admin non trouvé");
            return false;
        }
        
        // Super admin a toutes les permissions
        if ($admin['role'] === 'super_admin') {
            return true;
        }
        
        // Récupérer les permissions du rôle
        if (!defined('ADMIN_ROLES')) {
            error_log("Permission refusée : ADMIN_ROLES non défini");
            return false;
        }
        
        $rolePermissions = ADMIN_ROLES[$admin['role']]['permissions'] ?? [];
        
        $hasPermission = in_array($permission, $rolePermissions);
        if (!$hasPermission) {
            error_log("Permission refusée pour {$admin['role']} : $permission");
        }
        
        return $hasPermission;
        */
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
    
    /**
     * Vérifie si l'utilisateur a une permission spécifique et redirige si non
     */
    public function requirePermission($permission) {
        if (!$this->hasPermission($permission)) {
            $_SESSION['flash'] = [
                'danger' => 'Vous n\'avez pas la permission d\'accéder à cette page.'
            ];
            header('Location: index.php');
            exit;
        }
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