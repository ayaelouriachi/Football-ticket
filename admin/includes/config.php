<?php
// Load main application config if not already loaded
if (!defined('BASE_URL')) {
    require_once(__DIR__ . '/../../config/init.php');
}

// Admin specific configuration
define('ADMIN_UPLOAD_DIR', __DIR__ . '/../../uploads/admin/');
define('ADMIN_ASSETS_URL', BASE_URL . 'assets/admin/');

// Admin specific settings
$ADMIN_SETTINGS = [
    'items_per_page' => 10,
    'session_lifetime' => 3600,
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'pdf'],
    'max_file_size' => 5 * 1024 * 1024 // 5MB
];

// Use existing database connection
$adminDb = $db;

// Load admin auth class
require_once(__DIR__ . '/auth.php');
$auth = new AdminAuth($adminDb);

// Admin specific helper functions with unique names to avoid conflicts
function adminSetFlashMessage($type, $message) {
    if (!isset($_SESSION['admin_flash'])) {
        $_SESSION['admin_flash'] = [];
    }
    $_SESSION['admin_flash'][$type] = $message;
}

function adminGetFlashMessage($type) {
    if (isset($_SESSION['admin_flash'][$type])) {
        $message = $_SESSION['admin_flash'][$type];
        unset($_SESSION['admin_flash'][$type]);
        return $message;
    }
    return null;
}

function adminDisplayFlashMessages() {
    if (isset($_SESSION['admin_flash']) && !empty($_SESSION['admin_flash'])) {
        foreach ($_SESSION['admin_flash'] as $type => $message) {
            echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
            echo htmlspecialchars($message);
            echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
            echo "</div>";
        }
        unset($_SESSION['admin_flash']);
    }
}

function adminFormatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

function adminFormatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function adminSanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function adminGenerateSlug($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    
    return $text;
}

function adminJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function adminJsonError($message, $status = 400) {
    adminJsonResponse(['error' => $message], $status);
}

function adminJsonSuccess($data = null, $message = 'Success') {
    adminJsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

// Admin Panel Configuration

// Roles and Permissions
define('ADMIN_ROLES', [
    'super_admin' => [
        'name' => 'Super Administrateur',
        'permissions' => [
            'manage_admins',
            'manage_settings',
            'manage_matches',
            'manage_categories',
            'manage_users',
            'manage_orders',
            'view_reports',
            'manage_content',
            'manage_notifications',
            'manage_backups'
        ]
    ],
    'admin' => [
        'name' => 'Administrateur',
        'permissions' => [
            'manage_matches',
            'manage_categories',
            'manage_users',
            'manage_orders',
            'view_reports',
            'manage_content',
            'manage_notifications'
        ]
    ],
    'moderator' => [
        'name' => 'Modérateur',
        'permissions' => [
            'view_matches',
            'view_categories',
            'view_users',
            'view_orders',
            'manage_content'
        ]
    ]
]);

// Admin Menu Structure
define('ADMIN_MENU', [
    'dashboard' => [
        'title' => 'Tableau de bord',
        'icon' => 'bi bi-speedometer2',
        'url' => 'index.php',
        'permission' => null
    ],
    'matches' => [
        'title' => 'Matchs',
        'icon' => 'bi bi-trophy',
        'url' => 'matches.php',
        'permission' => 'view_matches',
        'submenu' => [
            'list' => [
                'title' => 'Liste des matchs',
                'url' => 'matches.php'
            ],
            'add' => [
                'title' => 'Ajouter un match',
                'url' => 'matches/add.php',
                'permission' => 'manage_matches'
            ],
            'categories' => [
                'title' => 'Catégories',
                'url' => 'categories.php',
                'permission' => 'manage_categories'
            ]
        ]
    ],
    'orders' => [
        'title' => 'Commandes',
        'icon' => 'bi bi-cart3',
        'url' => 'orders.php',
        'permission' => 'view_orders',
        'submenu' => [
            'list' => [
                'title' => 'Toutes les commandes',
                'url' => 'orders.php'
            ],
            'pending' => [
                'title' => 'En attente',
                'url' => 'orders.php?status=pending'
            ],
            'completed' => [
                'title' => 'Complétées',
                'url' => 'orders.php?status=completed'
            ]
        ]
    ],
    'users' => [
        'title' => 'Utilisateurs',
        'icon' => 'bi bi-people',
        'url' => 'users.php',
        'permission' => 'view_users',
        'submenu' => [
            'list' => [
                'title' => 'Liste des utilisateurs',
                'url' => 'users.php'
            ],
            'add' => [
                'title' => 'Ajouter un utilisateur',
                'url' => 'users/add.php',
                'permission' => 'manage_users'
            ]
        ]
    ],
    'reports' => [
        'title' => 'Rapports',
        'icon' => 'bi bi-graph-up',
        'url' => 'reports.php',
        'permission' => 'view_reports',
        'submenu' => [
            'sales' => [
                'title' => 'Ventes',
                'url' => 'reports/sales.php'
            ],
            'users' => [
                'title' => 'Utilisateurs',
                'url' => 'reports/users.php'
            ],
            'matches' => [
                'title' => 'Matchs',
                'url' => 'reports/matches.php'
            ]
        ]
    ],
    'settings' => [
        'title' => 'Paramètres',
        'icon' => 'bi bi-gear',
        'url' => 'settings.php',
        'permission' => 'manage_settings',
        'submenu' => [
            'general' => [
                'title' => 'Général',
                'url' => 'settings/general.php'
            ],
            'payment' => [
                'title' => 'Paiement',
                'url' => 'settings/payment.php'
            ],
            'email' => [
                'title' => 'Email',
                'url' => 'settings/email.php'
            ],
            'backup' => [
                'title' => 'Sauvegarde',
                'url' => 'settings/backup.php',
                'permission' => 'manage_backups'
            ]
        ]
    ]
]);

// Admin API Settings
define('ADMIN_API', [
    'enable_api' => true,
    'api_key_expiry' => 30, // days
    'rate_limit' => [
        'requests' => 100,
        'window' => 60 // 1 minute
    ]
]);

// Admin Notification Settings
define('ADMIN_NOTIFICATIONS', [
    'email' => [
        'from_name' => 'TicketFoot Admin',
        'from_email' => 'admin@ticketfoot.com',
        'reply_to' => 'support@ticketfoot.com'
    ],
    'sms' => [
        'provider' => 'twilio',
        'sender_id' => 'TicketFoot'
    ]
]);

// Helper functions
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function generateSlug($text) {
    // Replace non letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);
    
    return $text;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

// Response helpers for API/AJAX
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonError($message, $status = 400) {
    jsonResponse(['error' => $message], $status);
}

function jsonSuccess($data = null, $message = 'Success') {
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}
?> 