<?php
// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialisation de la session
require_once __DIR__ . '/session.php';
SessionManager::init();

// Constantes
require_once __DIR__ . '/constants.php';

// Configuration de la base de données
require_once __DIR__ . '/database.php';
$db = Database::getInstance()->getConnection();

// Classes nécessaires
spl_autoload_register(function ($class) {
    $file = dirname(__DIR__) . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Set timezone
date_default_timezone_set('UTC');

// Définir la constante de timeout du panier si elle n'existe pas
if (!defined('CART_TIMEOUT')) {
    define('CART_TIMEOUT', 7200); // 2 heures
}

if (!defined('MAX_TICKETS_PER_CATEGORY')) {
    define('MAX_TICKETS_PER_CATEGORY', 10);
}

// Fonction pour gérer les erreurs d'images (version JavaScript)
?>
<script>
function handleImageError(element) {
    if (element.dataset.type === 'team') {
        element.src = 'assets/images/default-team.png';
    }
}
</script>
<?php

// Common functions
function redirect($url) {
    header("Location: $url");
    exit;
}

function flash($type, $message) {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][$type] = $message;
}

function get_flash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return null;
}

function display_flash_messages() {
    if (isset($_SESSION['flash']) && !empty($_SESSION['flash'])) {
        foreach ($_SESSION['flash'] as $type => $message) {
            echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($message);
            echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
            echo "</div>";
        }
        unset($_SESSION['flash']);
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        flash('error', 'Please log in to access this page');
        redirect('/login.php');
    }
}

function sanitize_output($value) {
    if (is_array($value)) {
        return array_map('sanitize_output', $value);
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_price($price, $currency = 'MAD') {
    return number_format($price, 2) . ' ' . $currency;
}

function format_date($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Set default headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (isset($_SERVER['HTTPS'])) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// Initialize cart if needed
if (!isset($cart) && class_exists('Cart')) {
    $cart = new Cart($db, $_SESSION);
} 