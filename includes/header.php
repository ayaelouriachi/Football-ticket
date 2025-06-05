<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../includes/auth_middleware.php');
require_once(__DIR__ . '/../includes/flash_messages.php');

// Initialize session
SessionManager::init();

// Get current user if logged in
$currentUser = getCurrentUser();

// Get cart count
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Accueil'; ?> - <?php echo APP_NAME; ?></title>
    <meta name="description" content="<?php echo $pageDescription ?? 'Réservez vos billets pour les matchs de football au Maroc'; ?>">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/responsive.css">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>images/favicon.png">
    
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <!-- Custom JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>js/dropdown.js" defer></script>
    
    <!-- Debug Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Debug script chargé');
        console.log('Session user:', <?php echo json_encode($currentUser); ?>);
        console.log('Cart count:', <?php echo $cartCount; ?>);
        
        // Test elements
        setTimeout(() => {
            const userToggle = document.querySelector('.user-toggle');
            const userDropdownMenu = document.querySelector('.user-dropdown-menu');
            console.log('Elements après chargement:');
            console.log('- userToggle:', userToggle, window.getComputedStyle(userToggle).display);
            console.log('- userDropdownMenu:', userDropdownMenu);
        }, 1000);
    });
    </script>
    
    <style>
    /* Navbar styles */
    .navbar {
        padding: 1rem 0;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: relative;
        z-index: 1000;
    }
    
    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .navbar-brand img {
        height: 40px;
    }
    
    .navbar-menu {
        display: flex;
        align-items: center;
        gap: 2rem;
    }
    
    .navbar-nav {
        display: flex;
        gap: 1.5rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    
    .nav-link {
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
    }
    
    .nav-link:hover {
        color: #007bff;
    }
    
    .nav-link.active {
        color: #007bff;
    }
    
    /* User dropdown styles */
    .user-dropdown {
        position: relative;
        display: inline-block;
    }
    
    .user-toggle {
        display: inline-flex !important;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: none;
        border: none;
        color: #333;
        font-weight: 500;
        cursor: pointer;
        transition: color 0.2s;
        white-space: nowrap;
    }
    
    .user-toggle:hover {
        color: #007bff;
    }
    
    .user-dropdown-menu {
        display: none;
        position: absolute;
        right: 0;
        top: 100%;
        min-width: 200px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1001;
        margin-top: 0.5rem;
        padding: 0.5rem 0;
        border: 1px solid rgba(0,0,0,0.1);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
    }
    
    .user-dropdown-menu.show {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        transform: translateY(0) !important;
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        color: #333;
        text-decoration: none;
        transition: background-color 0.2s;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        font-size: inherit;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    
    .dropdown-item.text-danger {
        color: #dc3545;
    }
    
    .dropdown-item.text-danger:hover {
        background-color: #fff5f5;
    }
    
    .dropdown-divider {
        margin: 0.5rem 0;
        border-top: 1px solid #e9ecef;
    }
    
    /* Cart styles */
    .cart-toggle {
        position: relative;
        padding: 0.5rem;
        background: none;
        border: none;
        color: #333;
        cursor: pointer;
    }
    
    .cart-count {
        position: absolute;
        top: 0;
        right: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 18px;
        height: 18px;
        padding: 0 5px;
        background: #dc3545;
        color: white;
        font-size: 12px;
        font-weight: bold;
        border-radius: 9px;
        transform: translate(50%, -50%);
    }
    
    /* Auth buttons */
    .auth-buttons {
        display: flex;
        gap: 1rem;
    }
    
    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 4px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #007bff;
        color: white;
    }
    
    .btn-primary:hover {
        background: #0056b3;
    }
    
    .btn-outline-primary {
        background: none;
        border: 1px solid #007bff;
        color: #007bff;
    }
    
    .btn-outline-primary:hover {
        background: #007bff;
        color: white;
    }
    
    /* Loading spinner */
    .loading-spinner {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.8);
        z-index: 9999;
        justify-content: center;
        align-items: center;
    }
    
    .spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Mobile menu */
    .navbar-toggle {
        display: none;
        flex-direction: column;
        gap: 4px;
        padding: 0.5rem;
        background: none;
        border: none;
        cursor: pointer;
    }
    
    .navbar-toggle span {
        display: block;
        width: 24px;
        height: 2px;
        background: #333;
        transition: all 0.3s;
    }
    
    @media (max-width: 768px) {
        .navbar-toggle {
            display: flex;
        }
        
        .navbar-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-menu.show {
            display: block;
        }
        
        .navbar-nav {
            flex-direction: column;
            gap: 1rem;
        }
        
        .navbar-actions {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }
    }
    </style>
</head>
<body class="<?php echo $bodyClass ?? ''; ?>">
    <!-- Loading Spinner -->
    <div id="loading-spinner" class="loading-spinner">
        <div class="spinner"></div>
    </div>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <!-- Logo -->
                <div class="navbar-brand">
                    <a href="<?php echo BASE_URL; ?>" class="logo">
                        <img src="<?php echo ASSETS_URL; ?>images/logo.png" alt="<?php echo APP_NAME; ?>">
                        <span class="logo-text">Football Tickets</span>
                    </a>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="navbar-toggle" id="mobile-menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Navigation Menu -->
                <div class="navbar-menu" id="navbar-menu">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                                <i class="fas fa-home"></i> Accueil
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>pages/matches.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'active' : ''; ?>">
                                <i class="fas fa-futbol"></i> Matchs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?php echo BASE_URL; ?>pages/about.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                                <i class="fas fa-info-circle"></i> À propos
                            </a>
                        </li>
                    </ul>

                    <!-- User Actions -->
                    <div class="navbar-actions">
                        <!-- Cart -->
                        <div class="cart-dropdown">
                            <a href="<?php echo BASE_URL; ?>cart.php" class="cart-toggle">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="cart-count"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>

                        <!-- User Menu -->
                        <?php if ($currentUser): ?>
                            <div class="user-dropdown">
                                <button type="button" class="user-toggle" aria-expanded="false" aria-haspopup="true">
                                    <i class="fas fa-user-circle"></i>
                                    <span class="user-name"><?php echo htmlspecialchars($currentUser['name']); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="user-dropdown-menu">
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                        <a href="<?php echo BASE_URL; ?>admin/" class="dropdown-item">
                                            <i class="fas fa-cog"></i>
                                            <span>Administration</span>
                                        </a>
                                        <div class="dropdown-divider"></div>
                                    <?php endif; ?>
                                    
                                    <a href="<?php echo BASE_URL; ?>profile.php" class="dropdown-item">
                                        <i class="fas fa-user"></i>
                                        <span>Mon profil</span>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>orders.php" class="dropdown-item">
                                        <i class="fas fa-ticket-alt"></i>
                                        <span>Mes commandes</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <form id="logout-form" action="<?php echo BASE_URL; ?>scripts/logout.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-sign-out-alt"></i>
                                            <span>Déconnexion</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="auth-buttons">
                                <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    <span>Connexion</span>
                                </a>
                                <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i>
                                    <span>Inscription</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php displayFlashMessages(); ?>

    <!-- Main Content -->
    <main class="main-content">
    
    <!-- Mobile Menu Script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const navbarMenu = document.getElementById('navbar-menu');
        
        if (mobileMenuToggle && navbarMenu) {
            mobileMenuToggle.addEventListener('click', function() {
                navbarMenu.classList.toggle('show');
            });
        }
    });
    </script>
</body>
</html>