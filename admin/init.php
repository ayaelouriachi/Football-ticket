<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger les fichiers requis
require_once dirname(__DIR__) . '/config/constants.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/auth.php';
require_once __DIR__ . '/includes/admin_functions.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isLoggedIn() || !isAdmin()) {
    // Sauvegarder l'URL demandée pour la redirection après connexion
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Récupérer les informations de l'utilisateur connecté
$user = getCurrentUser();

// Définir les éléments du menu
$menu_items = [
    [
        'title' => 'Dashboard',
        'icon' => 'bi-speedometer2',
        'url' => ADMIN_URL,
        'active' => false
    ],
    [
        'title' => 'Matchs',
        'icon' => 'bi-calendar-event',
        'url' => ADMIN_URL . 'matches',
        'active' => false
    ],
    [
        'title' => 'Commandes',
        'icon' => 'bi-cart',
        'url' => ADMIN_URL . 'orders',
        'active' => false
    ],
    [
        'title' => 'Utilisateurs',
        'icon' => 'bi-people',
        'url' => ADMIN_URL . 'users',
        'active' => false
    ]
];

// Déterminer la page active
$current_page = str_replace(ADMIN_URL, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$current_page = $current_page ?: '/';

foreach ($menu_items as &$item) {
    $item['active'] = str_replace(ADMIN_URL, '', $item['url']) === $current_page;
}

// Fonction utilitaire pour le fil d'Ariane
function getAdminBreadcrumb() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = array_filter(explode('/', $path));
    
    $breadcrumb = [
        ['title' => 'Dashboard', 'url' => ADMIN_URL]
    ];
    
    $current = '';
    foreach ($parts as $part) {
        if ($part === 'admin') continue;
        
        $current .= '/' . $part;
        $title = ucfirst($part);
        
        if (end($parts) === $part) {
            $breadcrumb[] = ['title' => $title, 'url' => null];
        } else {
            $breadcrumb[] = ['title' => $title, 'url' => ADMIN_URL . ltrim($current, '/')];
        }
    }
    
    return $breadcrumb;
}

// Initialiser les variables de messages
$error_message = $_SESSION['error_message'] ?? null;
$success_message = $_SESSION['success_message'] ?? null;

// Nettoyer les messages de la session
unset($_SESSION['error_message'], $_SESSION['success_message']);

// Obtenir l'instance de la base de données
$db = Database::getInstance()->getConnection(); 