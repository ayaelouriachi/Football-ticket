<?php
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/includes/auth.php');

// Initialize auth
$auth = new AdminAuth($db, $_SESSION);

// Log out the user
$auth->logout();

// Set success message
setFlashMessage('success', 'Vous avez été déconnecté avec succès.');

// Redirect to login page
header('Location: login.php');
exit;
