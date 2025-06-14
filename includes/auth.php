<?php
/**
 * Fonctions principales d'authentification
 */

// Inclure les autres fichiers d'authentification
require_once __DIR__ . '/auth_functions.php';
require_once __DIR__ . '/auth_helper.php';

/**
 * Vérifie si un utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est un admin
 * @return bool
 */
function isAdmin() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Récupère l'utilisateur actuellement connecté
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting current user: " . $e->getMessage());
        return null;
    }
}

/**
 * Déconnecte l'utilisateur
 */
function logout() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Détruire toutes les variables de session
    $_SESSION = array();
    
    // Détruire le cookie de session si il existe
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-42000, '/');
    }
    
    // Détruire la session
    session_destroy();
}

/**
 * Redirige vers la page de connexion si l'utilisateur n'est pas connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

/**
 * Redirige vers la page d'accueil si l'utilisateur n'est pas admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL);
        exit;
    }
} 