<?php
// Définition des chemins de base
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CONFIG_PATH', ROOT_PATH . 'config' . DIRECTORY_SEPARATOR);
define('INCLUDES_PATH', ROOT_PATH . 'includes' . DIRECTORY_SEPARATOR);
define('ADMIN_PATH', ROOT_PATH . 'admin' . DIRECTORY_SEPARATOR);

// Détection de l'environnement
$isLocalhost = in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

// URLs de base - Forcer HTTPS uniquement en production
$protocol = (!$isLocalhost && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $host . '/football_tickets/');
define('ADMIN_URL', BASE_URL . 'admin/'); 