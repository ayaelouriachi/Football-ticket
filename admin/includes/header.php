<?php
// Inclure les fichiers de configuration principaux
require_once(__DIR__ . '/../../config/init.php');
require_once(__DIR__ . '/../../config/database.php');
require_once(__DIR__ . '/../../config/constants.php');

// Initialiser la session via le gestionnaire de session
SessionManager::init();

// Vérifier l'authentification admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . 'admin/login.php');
    exit;
}

// Définir les variables globales pour l'interface admin
$adminUser = [
    'id' => $_SESSION['admin_id'],
    'name' => $_SESSION['admin_name'] ?? 'Admin',
    'role' => $_SESSION['admin_role'] ?? 'admin'
];

// En-tête HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : ''; ?>Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>admin/">
                <i class="bi bi-shield-lock me-2"></i>Admin Panel
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>admin/matches.php">
                            <i class="bi bi-calendar-event me-2"></i>Matches
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>admin/users.php">
                            <i class="bi bi-people me-2"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center" href="<?php echo BASE_URL; ?>admin/orders.php">
                            <i class="bi bi-cart me-2"></i>Orders
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar bg-white text-primary me-2">
                                <?php echo strtoupper(substr($adminUser['name'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($adminUser['name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li>
                                <a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>admin/profile.php">
                                    <i class="bi bi-person me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item py-2 text-danger" href="<?php echo BASE_URL; ?>admin/logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        <?php
        // Display flash messages
        if (isset($_SESSION['flash'])) {
            echo '<div class="container-fluid mb-4">';
            foreach ($_SESSION['flash'] as $type => $message) {
                $icon = match($type) {
                    'success' => 'bi-check-circle',
                    'danger' => 'bi-exclamation-triangle',
                    'warning' => 'bi-exclamation-circle',
                    'info' => 'bi-info-circle',
                    default => 'bi-bell'
                };
                echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>";
                echo "<i class='bi {$icon} me-2'></i>";
                echo htmlspecialchars($message);
                echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
                echo "</div>";
            }
            echo '</div>';
            unset($_SESSION['flash']);
        }
        ?>
    </main>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
