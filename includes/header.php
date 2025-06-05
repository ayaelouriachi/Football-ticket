<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');

SessionManager::init();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Accueil'; ?> - <?php echo APP_NAME; ?></title>
    <meta name="description" content="<?php echo $page_description ?? 'Réservez vos billets pour les matchs de football au Maroc'; ?>">
    
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
    <meta name="csrf-token" content="<?php echo SessionManager::generateCSRFToken(); ?>">
</head>
<body class="<?php echo $body_class ?? ''; ?>">
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
                <button class="navbar-toggle" id="mobile-menu-toggle"  >
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
                            <button class="cart-toggle" id="cart-toggle">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="cart-count" id="cart-count">0</span>
                            </button>
                            <div class="cart-dropdown-menu" id="cart-dropdown">
                                <div class="cart-header">
                                    <h3>Panier</h3>
                                </div>
                                <div class="cart-items" id="cart-items">
                                    <p class="cart-empty">Votre panier est vide</p>
                                </div>
                                <div class="cart-footer">
                                    <div class="cart-total">
                                        <strong>Total: <span id="cart-total">0.00 MAD</span></strong>
                                    </div>
                                   <a href="http://localhost/football_tickets/cart.php" class="btn btn-primary btn-block">
                                        Voir le panier
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <?php if (SessionManager::has('user_id')): ?>
                            <div class="user-dropdown">
                                <button class="user-toggle">
                                    <i class="fas fa-user-circle"></i>
                                    <span><?php echo SessionManager::get('user_first_name', 'Mon compte'); ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="user-dropdown-menu">
                                    <a href="<?php echo BASE_URL; ?>pages/profile.php" class="dropdown-item">
                                        <i class="fas fa-user"></i> Mon profil
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>pages/orders.php" class="dropdown-item">
                                        <i class="fas fa-receipt"></i> Mes commandes
                                    </a>
                                    <hr class="dropdown-divider">
                                    <a href="<?php echo BASE_URL; ?>pages/logout.php" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="auth-buttons">
                                <a href="<?php echo BASE_URL; ?>pages/login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt"></i> Connexion
                                </a>
                                <a href="<?php echo BASE_URL; ?>pages/register.php" class="btn btn-primary">
                                    <i class="fas fa-user-plus"></i> Inscription
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content"><?php // Content will be inserted here ?>