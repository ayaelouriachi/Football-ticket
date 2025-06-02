<?php
// Application paths
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . '/assets');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Image paths and URLs
if (!defined('IMAGES_URL')) define('IMAGES_URL', '/assets/images');
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', '/uploads');
if (!defined('TEAM_LOGOS_URL')) define('TEAM_LOGOS_URL', UPLOADS_URL . '/teams');
if (!defined('STADIUM_IMAGES_URL')) define('STADIUM_IMAGES_URL', UPLOADS_URL . '/stadiums');
if (!defined('MATCH_IMAGES_URL')) define('MATCH_IMAGES_URL', UPLOADS_URL . '/matches');

// Default images
if (!defined('DEFAULT_TEAM_LOGO')) define('DEFAULT_TEAM_LOGO', IMAGES_URL . '/default-team.svg');
if (!defined('DEFAULT_STADIUM_IMAGE')) define('DEFAULT_STADIUM_IMAGE', IMAGES_URL . '/default-stadium.svg');
if (!defined('DEFAULT_MATCH_IMAGE')) define('DEFAULT_MATCH_IMAGE', IMAGES_URL . '/default-match.svg');
if (!defined('DEFAULT_PLACEHOLDER')) define('DEFAULT_PLACEHOLDER', IMAGES_URL . '/default-placeholder.svg');

// Cart settings
if (!defined('CART_TIMEOUT')) define('CART_TIMEOUT', 1800); // 30 minutes
if (!defined('MAX_TICKETS_PER_CATEGORY')) define('MAX_TICKETS_PER_CATEGORY', 10);

// Error reporting
if (defined('DEBUG') && DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Time zone
date_default_timezone_set('UTC');

// Session settings
ini_set('session.gc_maxlifetime', 7200);
ini_set('session.cookie_lifetime', 7200);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

// Security headers
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    if (isset($_SERVER['HTTPS'])) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
} 