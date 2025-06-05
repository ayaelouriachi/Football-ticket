<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/session.php');
require_once __DIR__ . '/Cart.php';

class Auth {
    private $db;
    private $session;
    private $table = 'users';
    private $cart;
    
    public function __construct($db = null) {
        if ($db === null) {
            $db = Database::getInstance()->getConnection();
        }
        $this->db = $db;
        $this->session = new SessionManager();
        $this->cart = new Cart($db, $_SESSION);
    }
    
    /**
     * Authenticate user and create session
     * @param string $email User email
     * @param string $password User password
     * @return array|false User data if successful, false otherwise
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !password_verify($password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ];
            }
            
            // Transférer le panier de la session à l'utilisateur
            if ($this->cart) {
                $oldSessionId = session_id();
                $this->cart->transferCart($oldSessionId, $user['id']);
            }
            
            // Créer la session utilisateur
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            return [
                'success' => true,
                'message' => 'Connexion réussie',
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Erreur lors de la connexion"
            ];
        }
    }
    
    /**
     * Register new user
     * @param array $userData User registration data
     * @return array|false User data if successful, false otherwise
     * @throws Exception if validation fails or registration fails
     */
    public function register($userData) {
        try {
            // Validate required fields
            if (!isset($userData['email']) || !isset($userData['password']) || !isset($userData['name'])) {
                throw new Exception('Données d\'inscription incomplètes');
            }
            
            // Validate email
            if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Format d\'email invalide');
            }
            
            // Validate name
            if (empty($userData['name'])) {
                throw new Exception('Le nom est requis');
            }
            
            // Validate password
            if (strlen($userData['password']) < 8) {
                throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
            }
            
            // Check if email exists
            $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$userData['email']]);
            if ($stmt->fetch()) {
                throw new Exception('Cet email est déjà utilisé');
            }
            
            // Hash password with bcrypt
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT, ['cost' => 12]);
            
            // Debug log (REMOVE IN PRODUCTION)
            error_log("Registering new user: " . $userData['email']);
            error_log("Generated password hash: " . $hashedPassword);
            
            // Insert new user
            $stmt = $this->db->prepare('
                INSERT INTO users (name, email, password, role, created_at) 
                VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)
            ');
            
            $stmt->execute([
                $userData['name'],
                $userData['email'],
                $hashedPassword,
                'user' // Default role
            ]);
            
            // Get inserted user
            $userId = $this->db->lastInsertId();
            $stmt = $this->db->prepare('
                SELECT id, name, email, role, created_at 
                FROM users 
                WHERE id = ?
            ');
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Start session for new user
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            error_log("Registration successful for user: " . $userData['email']);
            return $user;
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update user profile
     * @param int $userId User ID
     * @param array $userData Updated user data
     * @return bool Success status
     */
    public function updateProfile($userId, $userData) {
        try {
            $updates = [];
            $params = [];
            
            // Build update query dynamically
            if (isset($userData['name'])) {
                $updates[] = 'name = ?';
                $params[] = $userData['name'];
            }
            
            if (isset($userData['email'])) {
                // Check if new email is already used
                $stmt = $this->db->prepare('
                    SELECT id FROM users 
                    WHERE email = ? AND id != ?
                ');
                $stmt->execute([$userData['email'], $userId]);
                if ($stmt->fetch()) {
                    throw new Exception('Cet email est déjà utilisé');
                }
                
                $updates[] = 'email = ?';
                $params[] = $userData['email'];
            }
            
            if (isset($userData['password'])) {
                $updates[] = 'password = ?';
                $params[] = password_hash($userData['password'], PASSWORD_DEFAULT);
            }
            
            if (empty($updates)) {
                return true; // Nothing to update
            }
            
            // Add user ID to params
            $params[] = $userId;
            
            // Update user
            $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Reset user password
     * @param string $email User email
     * @param string $token Reset token
     * @param string $password New password
     * @return bool Success status
     */
    public function resetPassword($email, $token, $password) {
        try {
            // Verify token
            $stmt = $this->db->prepare('
                SELECT id 
                FROM password_resets 
                WHERE email = ? AND token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                AND used = 0
            ');
            $stmt->execute([$email, $token]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Token invalide ou expiré');
            }
            
            // Update password
            $stmt = $this->db->prepare('
                UPDATE users 
                SET password = ? 
                WHERE email = ?
            ');
            $stmt->execute([
                password_hash($password, PASSWORD_DEFAULT),
                $email
            ]);
            
            // Mark token as used
            $stmt = $this->db->prepare('
                UPDATE password_resets 
                SET used = 1 
                WHERE email = ? AND token = ?
            ');
            $stmt->execute([$email, $token]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function logout() {
        try {
            // Supprimer toutes les données de session liées à l'utilisateur
            unset(
                $_SESSION['user_id'],
                $_SESSION['user_name'],
                $_SESSION['user_email'],
                $_SESSION['user_role']
            );
            
            // Démarrer une nouvelle session pour les messages flash
            SessionManager::init();
            
            return [
                'success' => true,
                'message' => 'Déconnexion réussie'
            ];
            
        } catch (Exception $e) {
            error_log("Logout Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "Erreur lors de la déconnexion"
            ];
        }
    }
    
    public function isLoggedIn() {
        return $this->session->has('user_id');
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $this->session->get('user_id'),
            'email' => $this->session->get('user_email'),
            'name' => $this->session->get('user_name'),
            'role' => $this->session->get('user_role')
        ];
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            $currentUrl = $_SERVER['REQUEST_URI'];
            header("Location: /pages/login.php?redirect=" . urlencode($currentUrl));
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if ($this->session->get('user_role') !== 'admin') {
            header("Location: /");
            exit;
        }
    }
    
    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get user
            $stmt = $this->db->prepare("SELECT password FROM {$this->table} WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception("Mot de passe actuel incorrect");
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception("Le nouveau mot de passe doit contenir au moins 8 caractères");
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => 12]);
            
            // Update password
            $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            return [
                'success' => true,
                'message' => 'Mot de passe mis à jour avec succès'
            ];
            
        } catch (Exception $e) {
            error_log("Password Update Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 