<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../includes/security.php');

// Initialize session
SessionManager::init();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    SessionManager::setFlashMessage('error', 'Erreur de sécurité. Veuillez réessayer.');
    header('Location: ' . BASE_URL);
    exit;
}

// Destroy session and redirect
SessionManager::destroy();
SessionManager::setFlashMessage('success', 'Vous avez été déconnecté avec succès.');
header('Location: ' . BASE_URL);
exit; 