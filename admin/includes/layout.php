<?php
// Vérifier si les fichiers nécessaires sont déjà inclus
if (!defined('BASE_URL')) {
    require_once(__DIR__ . '/../../config/init.php');
}

if (!class_exists('AdminAuth')) {
    require_once(__DIR__ . '/auth.php');
}

// S'assurer que config.php est inclus
require_once(__DIR__ . '/config.php');

// Initialize auth if not already done
if (!isset($auth)) {
    $auth = new AdminAuth();
}

// Check authentication
if (!$auth->isLoggedIn()) {
    header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login.php');
    exit;
}

// Get current user
$currentUser = $auth->getCurrentAdmin();

// Get current page for menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);

// Vérifier si ADMIN_MENU est défini
if (!defined('ADMIN_MENU')) {
    die('Erreur : ADMIN_MENU n\'est pas défini. Vérifiez le fichier config.php');
}

/**
 * Check if a menu item should be displayed based on user permissions
 */
function canViewMenuItem($item) {
    global $auth;
    return !isset($item['permission']) || 
           $auth->hasPermission($item['permission']);
}

/**
 * Generate menu item HTML
 */
function renderMenuItem($key, $item, $currentPage) {
    $isActive = $currentPage === $item['url'];
    $hasSubmenu = isset($item['submenu']);
    
    if (!canViewMenuItem($item)) {
        return '';
    }
    
    $html = '<li class="nav-item' . ($hasSubmenu ? ' has-submenu' : '') . '">';
    
    if ($hasSubmenu) {
        $html .= '<a class="nav-link' . ($isActive ? ' active' : '') . '" href="#' . $key . '" data-bs-toggle="collapse" role="button" aria-expanded="false">';
    } else {
        $html .= '<a class="nav-link' . ($isActive ? ' active' : '') . '" href="' . $item['url'] . '">';
    }
    
    $html .= '<i class="' . $item['icon'] . ' me-2"></i>';
    $html .= '<span>' . $item['title'] . '</span>';
    
    if ($hasSubmenu) {
        $html .= '<i class="bi bi-chevron-down ms-auto"></i>';
    }
    
    $html .= '</a>';
    
    if ($hasSubmenu) {
        $html .= '<div class="collapse' . ($isActive ? ' show' : '') . '" id="' . $key . '">';
        $html .= '<ul class="nav flex-column submenu">';
        
        foreach ($item['submenu'] as $subKey => $subItem) {
            if (canViewMenuItem($subItem)) {
                $isSubActive = $currentPage === $subItem['url'];
                $html .= '<li class="nav-item">';
                $html .= '<a class="nav-link' . ($isSubActive ? ' active' : '') . '" href="' . $subItem['url'] . '">';
                $html .= '<span>' . $subItem['title'] . '</span>';
                $html .= '</a>';
                $html .= '</li>';
            }
        }
        
        $html .= '</ul>';
        $html .= '</div>';
    }
    
    $html .= '</li>';
    
    return $html;
}

?>
<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Administration - TicketFoot'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/admin.css" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 280px;
            --header-height: 60px;
            --sidebar-bg: #1a1c23;
            --content-bg: #121317;
        }
        
        body {
            min-height: 100vh;
            background: var(--content-bg);
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid rgba(255,255,255,0.1);
            z-index: 1000;
            transition: transform 0.3s ease;
        }
        
        .sidebar-header {
            height: var(--header-height);
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
        }
        
        .sidebar-brand img {
            width: 32px;
            height: 32px;
            margin-right: 0.5rem;
        }
        
        .sidebar-content {
            height: calc(100vh - var(--header-height));
            overflow-y: auto;
        }
        
        .nav-item {
            margin: 0.25rem 1rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255,255,255,0.7);
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .nav-link:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
        
        .nav-link.active {
            color: #fff;
            background: #3B82F6;
        }
        
        .submenu {
            padding-left: 3rem;
            margin-top: 0.25rem;
        }
        
        .submenu .nav-link {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            background: var(--content-bg);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding: 0 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 900;
        }
        
        .header-title {
            font-size: 1.25rem;
            font-weight: 500;
            color: #fff;
            margin: 0;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header-actions .btn {
            padding: 0.5rem;
            color: rgba(255,255,255,0.7);
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .header-actions .btn:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--header-height);
            min-height: 100vh;
        }
        
        .content-wrapper {
            padding: 2rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .header {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block !important;
            }
        }
        
        /* User Menu */
        .user-menu {
            position: relative;
        }
        
        .user-menu .dropdown-menu {
            min-width: 200px;
            padding: 0.5rem;
            margin-top: 0.5rem;
            background: var(--sidebar-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 0.5rem;
        }
        
        .user-menu .dropdown-item {
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,0.7);
            border-radius: 0.25rem;
        }
        
        .user-menu .dropdown-item:hover {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
        
        .user-menu .dropdown-divider {
            border-color: rgba(255,255,255,0.1);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <img src="../assets/images/logo.png" alt="TicketFoot">
                <span>TicketFoot</span>
            </a>
            <button class="btn btn-link d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <div class="sidebar-content">
            <ul class="nav flex-column">
                <?php
                // Debug: Afficher le contenu de ADMIN_MENU
                if (empty(ADMIN_MENU)) {
                    echo '<li class="nav-item"><div class="alert alert-danger">ADMIN_MENU est vide</div></li>';
                }
                
                foreach (ADMIN_MENU as $key => $item): 
                    $renderedItem = renderMenuItem($key, $item, $currentPage);
                    if (empty($renderedItem)) {
                        echo "<!-- Menu item '$key' non affiché - Permissions insuffisantes -->\n";
                    } else {
                        echo $renderedItem;
                    }
                endforeach; 
                ?>
            </ul>
        </div>
    </nav>
    
    <!-- Header -->
    <header class="header">
        <button class="btn btn-link toggle-sidebar d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar">
            <i class="bi bi-list"></i>
        </button>
        
        <h1 class="header-title"><?php echo $pageTitle ?? 'Administration'; ?></h1>
        
        <div class="header-actions">
            <button class="btn" type="button" id="darkModeToggle">
                <i class="bi bi-moon"></i>
            </button>
            
            <div class="dropdown user-menu">
                <button class="btn" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2"></i>
                    <span><?php echo htmlspecialchars($currentUser['name'] ?? $currentUser['username'] ?? 'Administrateur'); ?></span>
                    <i class="bi bi-chevron-down ms-2"></i>
                </button>
                
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <span class="dropdown-item-text">
                            <small class="d-block text-muted">Connecté en tant que</small>
                            <strong><?php echo htmlspecialchars($currentUser['role']); ?></strong>
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Mon profil</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['flash_message'];
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Content goes here -->
            <?php if (isset($content)) echo $content; ?>
        </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const htmlElement = document.documentElement;
        
        // Check saved theme preference
        const savedTheme = localStorage.getItem('admin-theme');
        if (savedTheme) {
            htmlElement.setAttribute('data-bs-theme', savedTheme);
            updateDarkModeIcon(savedTheme === 'dark');
        }
        
        darkModeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('admin-theme', newTheme);
            updateDarkModeIcon(newTheme === 'dark');
        });
        
        function updateDarkModeIcon(isDark) {
            const icon = darkModeToggle.querySelector('i');
            icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon';
        }
        
        // Mobile sidebar toggle
        const sidebar = document.querySelector('.sidebar');
        const toggleButtons = document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target="#sidebar"]');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', () => {
                sidebar.classList.toggle('show');
            });
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                sidebar.classList.contains('show') && 
                !sidebar.contains(e.target) && 
                !e.target.hasAttribute('data-bs-toggle')) {
                sidebar.classList.remove('show');
            }
        });
        
        // Submenu toggle
        const submenuToggles = document.querySelectorAll('.has-submenu > .nav-link');
        
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                const menuItem = toggle.parentElement;
                const submenu = menuItem.querySelector('.collapse');
                const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
                
                // Close other submenus
                document.querySelectorAll('.has-submenu > .nav-link[aria-expanded="true"]').forEach(item => {
                    if (item !== toggle) {
                        item.setAttribute('aria-expanded', 'false');
                        item.classList.remove('active');
                        bootstrap.Collapse.getInstance(item.nextElementSibling).hide();
                    }
                });
                
                // Toggle current submenu
                toggle.setAttribute('aria-expanded', !isExpanded);
                toggle.classList.toggle('active', !isExpanded);
            });
        });
        
        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
        
        // Initialize popovers
        const popovers = document.querySelectorAll('[data-bs-toggle="popover"]');
        popovers.forEach(popover => {
            new bootstrap.Popover(popover);
        });
        
        // Auto-hide alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
        
        // Session timeout warning
        const sessionTimeout = <?php echo ADMIN_SETTINGS['session_lifetime']; ?>;
        const warningTime = 5 * 60; // Show warning 5 minutes before timeout
        
        let timeoutWarning;
        let timeoutRedirect;
        
        function resetSessionTimers() {
            clearTimeout(timeoutWarning);
            clearTimeout(timeoutRedirect);
            
            timeoutWarning = setTimeout(showTimeoutWarning, (sessionTimeout - warningTime) * 1000);
            timeoutRedirect = setTimeout(redirectToLogin, sessionTimeout * 1000);
        }
        
        function showTimeoutWarning() {
            const modal = new bootstrap.Modal(document.createElement('div'));
            modal.element.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Session Expiration</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Votre session va expirer dans 5 minutes. Voulez-vous la prolonger ?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <button type="button" class="btn btn-primary" onclick="extendSession()">Prolonger la session</button>
                        </div>
                    </div>
                </div>
            `;
            modal.show();
        }
        
        function extendSession() {
            fetch('ajax/extend_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resetSessionTimers();
                        bootstrap.Modal.getInstance(document.querySelector('.modal')).hide();
                    }
                });
        }
        
        function redirectToLogin() {
            window.location.href = 'logout.php';
        }
        
        // Initialize session timers
        resetSessionTimers();
        
        // Reset timers on user activity
        ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
            document.addEventListener(event, resetSessionTimers);
        });
    </script>
    
    <!-- Custom page scripts -->
    <?php if (isset($pageScripts)) echo $pageScripts; ?>
</body>
</html> 