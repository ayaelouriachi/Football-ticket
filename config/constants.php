<?php
// Protection contre les inclusions multiples
if (!defined('APP_NAME')) {
    // Application
    define('APP_NAME', 'Football Tickets');
    define('APP_VERSION', '1.0.0');
    define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');

    // URLs
    define('BASE_URL', 'http://localhost/football_tickets/');
    define('ADMIN_URL', BASE_URL . 'admin/');
    define('ASSETS_URL', BASE_URL . 'assets/');
    define('UPLOADS_URL', BASE_URL . 'uploads/');

    // Paths
    define('ROOT_PATH', dirname(__DIR__) . '/');
    define('UPLOADS_PATH', ROOT_PATH . 'uploads/');
    define('LOGS_PATH', ROOT_PATH . 'logs/');

    // Database
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'football_tickets');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    // Session
    define('SESSION_NAME', 'football_tickets_session');
    define('SESSION_LIFETIME', 7200); // 2 hours

    // Security
    define('HASH_ALGO', PASSWORD_BCRYPT);
    define('HASH_COST', 12);

    // Pagination
    define('ITEMS_PER_PAGE', 10);

    // File Upload
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
    define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

    // Cart
    define('CART_EXPIRY', 7200); // 2 hours
    define('MAX_TICKETS_PER_ORDER', 10);

    // Configuration PayPal
    define('PAYPAL_ENVIRONMENT', APP_ENV === 'production' ? 'live' : 'sandbox');
    define('PAYPAL_CURRENCY', 'MAD');

    // Configuration email
    define('MAIL_FROM', 'noreply@footballtickets.ma');
    define('MAIL_FROM_NAME', APP_NAME);

    // Statuts des commandes
    define('ORDER_STATUS_PENDING', 'pending');
    define('ORDER_STATUS_PAID', 'paid');
    define('ORDER_STATUS_CANCELLED', 'cancelled');
    define('ORDER_STATUS_REFUNDED', 'refunded');

    // Rôles utilisateurs
    define('USER_ROLE_USER', 'user');
    define('USER_ROLE_ADMIN', 'admin');

    // Messages de succès et d'erreur
    define('MSG_SUCCESS_LOGIN', 'Connexion réussie');
    define('MSG_ERROR_LOGIN', 'Email ou mot de passe incorrect');
    define('MSG_SUCCESS_REGISTER', 'Inscription réussie');
    define('MSG_ERROR_REGISTER', 'Erreur lors de l\'inscription');
    define('MSG_SUCCESS_CART_ADD', 'Article ajouté au panier');
    define('MSG_ERROR_CART_ADD', 'Impossible d\'ajouter l\'article au panier');
}