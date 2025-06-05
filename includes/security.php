<?php

class SecurityHelper {
    // Constante pour la clé de session du token CSRF
    const CSRF_TOKEN_KEY = 'csrf_token';
    
    // Générer un token CSRF
    public static function generateCSRFToken() {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = self::generateToken();
        }
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }
    
    // Vérifier un token CSRF
    public static function verifyCSRFToken($token) {
        if (empty($token) || empty($_SESSION[self::CSRF_TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }
    
    // Nettoyer les données d'entrée
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    // Valider un email
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Valider un numéro de téléphone marocain
    public static function validateMoroccanPhone($phone) {
        return preg_match('/^(\+212|0)[6-7][0-9]{8}$/', $phone);
    }
    
    // Générer un token aléatoire
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Hacher un mot de passe
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Vérifier un mot de passe
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Échapper les données pour l'affichage HTML
    public static function escape($data) {
        if (is_array($data)) {
            return array_map([self::class, 'escape'], $data);
        }
        
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    // Valider les données contre les injections XSS
    public static function preventXSS($data) {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }// Valider la force d'un mot de passe
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une minuscule";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Générer un nom de fichier sécurisé
    public static function generateSecureFilename($originalName) {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = self::generateToken(16);
        return $filename . '.' . strtolower($extension);
    }
    
    // Valider les types de fichiers autorisés
    public static function validateFileType($filename, $allowedTypes = []) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedTypes);
    }
    
    // Nettoyer les URLs
    public static function sanitizeUrl($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
    
    // Générer un hash pour les fichiers
    public static function generateFileHash($filePath) {
        return hash_file('sha256', $filePath);
    }
}

// Fonctions globales pour la compatibilité
function generateCSRFToken() {
    return SecurityHelper::generateCSRFToken();
}

function verifyCSRFToken($token) {
    return SecurityHelper::verifyCSRFToken($token);
}
?>