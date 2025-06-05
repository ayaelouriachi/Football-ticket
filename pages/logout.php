<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../includes/flash_messages.php');

// Initialiser la session
SessionManager::init();

// Vérifier le token CSRF si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        die('Token de sécurité invalide');
    }
}

// Instancier la classe Auth
$auth = new Auth();

// Déconnecter l'utilisateur
$auth->logout();

// Rediriger vers la page de connexion avec un message
setFlashMessage('success', 'Vous avez été déconnecté avec succès');
header('Location: ' . BASE_URL . 'pages/login.php');
exit;
