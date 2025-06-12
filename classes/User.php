<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';

class User {
    private $db;
    private $table = 'users';
    private $id;
    private $email;
    private $name;
    private $role;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register($data) {
        try {
            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Check if email exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                throw new Exception("Email already registered");
            }

            // Validate password
            if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters");
            }

            // Hash password
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (name, email, password_hash, role, status, created_at)
                VALUES (?, ?, ?, 'user', 'active', NOW())
            ");
            $stmt->execute([
                $data['name'],
                $data['email'],
                $password_hash
            ]);

            return [
                'success' => true,
                'message' => 'Registration successful'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Connexion utilisateur
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, name, password_hash, role 
                FROM users 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $this->id = $user['id'];
                $this->email = $user['email'];
                $this->name = $user['name'];
                $this->role = $user['role'];

                $_SESSION['user_id'] = $this->id;
                $_SESSION['user_email'] = $this->email;
                $_SESSION['user_name'] = $this->name;
                $_SESSION['user_role'] = $this->role;

                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Déconnexion utilisateur
     */
    public function logout() {
        session_destroy();
        $this->id = null;
        $this->email = null;
        $this->name = null;
        $this->role = null;
    }
    
    /**
     * Récupérer un utilisateur par ID
     */
    public function getUserById($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            $user = $stmt->fetch();
            return $user ? $this->sanitizeUserData($user) : null;
            
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer un utilisateur par email
     */
    private function getUserByEmail($email) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE email = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si un email existe
     */
    private function emailExists($email) {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ? AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Email exists check error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Vérifier si l'utilisateur est admin
     */
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Récupérer l'utilisateur connecté
     */
    public function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role']
        ];
    }
    
    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateProfile($data) {
        try {
            $updates = [];
            $params = [];

            if (isset($data['name'])) {
                $updates[] = "name = ?";
                $params[] = $data['name'];
            }

            if (isset($data['email'])) {
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Invalid email format");
                }
                $updates[] = "email = ?";
                $params[] = $data['email'];
            }

            if (isset($data['password'])) {
                if (strlen($data['password']) < PASSWORD_MIN_LENGTH) {
                    throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters");
                }
                $updates[] = "password_hash = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($updates)) {
                throw new Exception("No fields to update");
            }

            $params[] = $this->getId();
            $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            if (isset($data['name'])) {
                $_SESSION['user_name'] = $data['name'];
                $this->name = $data['name'];
            }

            if (isset($data['email'])) {
                $_SESSION['user_email'] = $data['email'];
                $this->email = $data['email'];
            }

            return [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Changer le mot de passe
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Vérifier le mot de passe actuel
            $user = $this->getUserById($userId);
            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Mot de passe actuel incorrect'];
            }
            
            // Validation du nouveau mot de passe
            if (!$this->isValidPassword($newPassword)) {
                return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $sql = "UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$hashedPassword, $userId]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Mot de passe modifié avec succès'];
            }
            
            return ['success' => false, 'error' => 'Erreur lors de la modification'];
            
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Erreur système'];
        }
    }
    
    /**
     * Validation des données d'inscription
     */
    private function validateRegistrationData($data) {
        $errors = [];
        
        // Email
        if (empty($data['email'])) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        }
        
        // Mot de passe
        if (empty($data['password'])) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (!$this->isValidPassword($data['password'])) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }
        
        // Confirmation mot de passe
        if (empty($data['password_confirm'])) {
            $errors['password_confirm'] = 'La confirmation du mot de passe est requise';
        } elseif ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas';
        }
        
        // Prénom
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'Le prénom est requis';
        } elseif (strlen($data['first_name']) < 2) {
            $errors['first_name'] = 'Le prénom doit contenir au moins 2 caractères';
        }
        
        // Nom
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Le nom est requis';
        } elseif (strlen($data['last_name']) < 2) {
            $errors['last_name'] = 'Le nom doit contenir au moins 2 caractères';
        }
        
        // Téléphone (optionnel mais validation si présent)
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'Format de téléphone invalide';
        }
        
        return $errors;
    }
    
    /**
     * Validation des données de profil
     */
    private function validateProfileData($data) {
        $errors = [];
        
        // Prénom
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'Le prénom est requis';
        } elseif (strlen($data['first_name']) < 2) {
            $errors['first_name'] = 'Le prénom doit contenir au moins 2 caractères';
        }
        
        // Nom
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Le nom est requis';
        } elseif (strlen($data['last_name']) < 2) {
            $errors['last_name'] = 'Le nom doit contenir au moins 2 caractères';
        }
        
        // Téléphone (optionnel)
        if (!empty($data['phone']) && !preg_match('/^[0-9+\-\s\(\)]+$/', $data['phone'])) {
            $errors['phone'] = 'Format de téléphone invalide';
        }
        
        return $errors;
    }
    
    /**
     * Validation du mot de passe
     */
    private function isValidPassword($password) {
        return strlen($password) >= 8;
    }
    
    /**
     * Nettoyer les données utilisateur (supprimer les infos sensibles)
     */
    private function sanitizeUserData($user) {
        unset($user['password_hash']);
        unset($user['verification_token']);
        unset($user['reset_token']);
        unset($user['reset_token_expires']);
        return $user;
    }
    
    /**
     * Mettre à jour la dernière connexion
     */
    private function updateLastLogin($userId) {
        try {
            $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Créer un token de mémorisation
     */
    private function createRememberToken($userId) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
            
            // Supprimer les anciens tokens
            $sql = "DELETE FROM user_remember_tokens WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            // Créer le nouveau token
            $sql = "INSERT INTO user_remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, hash('sha256', $token), $expires]);
            
            // Définir le cookie
            setcookie('remember_token', $token, strtotime('+30 days'), '/', '', false, true);
            
        } catch (PDOException $e) {
            error_log("Create remember token error: " . $e->getMessage());
        }
    }
    
    /**
     * Supprimer un token de mémorisation
     */
    private function removeRememberToken($token) {
        try {
            $sql = "DELETE FROM user_remember_tokens WHERE token = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([hash('sha256', $token)]);
        } catch (PDOException $e) {
            error_log("Remove remember token error: " . $e->getMessage());
        }
    }

    public function getId() {
        return $this->id ?? $_SESSION['user_id'] ?? null;
    }

    public function getName() {
        return $this->name ?? $_SESSION['user_name'] ?? null;
    }

    public function getEmail() {
        return $this->email ?? $_SESSION['user_email'] ?? null;
    }

    public function getRole() {
        return $this->role ?? $_SESSION['user_role'] ?? null;
    }

    public function getOrders() {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, 
                       m.title as match_title, m.match_date,
                       t1.name as team1_name, t2.name as team2_name,
                       tc.name as ticket_category,
                       s.name as stadium_name
                FROM orders o
                JOIN tickets t ON o.id = t.order_id
                JOIN ticket_categories tc ON t.ticket_category_id = tc.id
                JOIN matches m ON tc.match_id = m.id
                JOIN teams t1 ON m.team1_id = t1.id
                JOIN teams t2 ON m.team2_id = t2.id
                JOIN stadiums s ON m.stadium_id = s.id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$this->getId()]);
            return $stmt->fetchAll();

        } catch (PDOException $e) {
            error_log("Get orders error: " . $e->getMessage());
            return [];
        }
    }

    public function getOrderHistory() {
        $sql = "SELECT 
            o.*,
            m.match_date,
            t1.name as home_team,
            t2.name as away_team,
            s.name as stadium,
            tc.name as ticket_category,
            oi.quantity,
            oi.price as unit_price
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN matches m ON oi.match_id = m.id
        JOIN teams t1 ON m.home_team_id = t1.id
        JOIN teams t2 ON m.away_team_id = t2.id
        JOIN stadiums s ON m.stadium_id = s.id
        JOIN ticket_categories tc ON oi.category_id = tc.id
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
}
?>