<?php
// Session configuration - must be set before session starts
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.name', 'football_tickets');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'football_tickets');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'Football Tickets');
define('APP_URL', 'http://localhost/football_tickets');
define('APP_VERSION', '1.0.0');

// Session configuration constants
define('SESSION_NAME', 'football_tickets');
define('SESSION_LIFETIME', 7200); // 2 hours

// Image paths
define('UPLOAD_PATH', __DIR__ . '/../uploads');
define('TEAM_LOGOS_PATH', UPLOAD_PATH . '/teams');
define('STADIUM_IMAGES_PATH', UPLOAD_PATH . '/stadiums');
define('MATCH_IMAGES_PATH', UPLOAD_PATH . '/matches');

// Default images
define('DEFAULT_TEAM_LOGO', '/assets/images/default-team.svg');
define('DEFAULT_STADIUM_IMAGE', '/assets/images/default-stadium.svg');
define('DEFAULT_MATCH_IMAGE', '/assets/images/default-match.svg');
define('DEFAULT_PLACEHOLDER', '/assets/images/default-placeholder.svg');

// Cart configuration
define('MAX_CART_ITEMS', 10);
define('MAX_TICKETS_PER_CATEGORY', 10);
define('CART_TIMEOUT', 1800); // 30 minutes

// Payment configuration
define('CURRENCY', 'EUR');
define('CURRENCY_SYMBOL', '€');
define('TAX_RATE', 0.20); // 20% VAT

// Email configuration
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@example.com');
define('SMTP_PASS', 'your-smtp-password');
define('SMTP_FROM', 'noreply@example.com');
define('SMTP_FROM_NAME', APP_NAME);

// Security configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 72);
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes
define('TOKEN_LIFETIME', 3600); // 1 hour

// Debug mode
define('DEBUG', true);
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
} 