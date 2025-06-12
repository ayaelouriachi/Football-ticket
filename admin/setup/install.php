<?php
require_once(__DIR__ . '/../includes/config.php');

// Set page title
$pageTitle = "Installation de l'administration - TicketFoot";

// Check if already installed
try {
    $stmt = $adminDb->prepare("SELECT COUNT(*) FROM admin_users");
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        die("L'administration est déjà installée !");
    }
} catch (PDOException $e) {
    // Tables don't exist yet, continue with installation
}

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/../sql/admin_tables.sql');
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map(
            function($statement) {
                return trim($statement);
            },
            explode(';', $sql)
        )
    );
    
    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $adminDb->exec($statement);
            } catch (PDOException $e) {
                // Skip if table already exists
                if ($e->getCode() != '42S01') { // 42S01 = Table already exists
                    throw $e;
                }
            }
        }
    }
    
    // Create default admin user if not exists
    $stmt = $adminDb->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
    $stmt->execute(['admin@ticketfoot.com']);
    
    if ($stmt->fetchColumn() == 0) {
        $stmt = $adminDb->prepare("
            INSERT INTO admin_users (email, password_hash, first_name, last_name, role) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            'admin@ticketfoot.com',
            password_hash('admin123', PASSWORD_DEFAULT),
            'Super',
            'Admin',
            'super_admin'
        ]);
    }
    
    // Insert default settings if not exists
    $defaultSettings = [
        ['site_name', 'Football Tickets Admin', 'string'],
        ['items_per_page', '10', 'integer'],
        ['session_lifetime', '3600', 'integer'],
        ['payment_mode', 'sandbox', 'string'],
        ['paypal_client_id', 'YOUR_SANDBOX_CLIENT_ID', 'string'],
        ['currency', 'EUR', 'string']
    ];
    
    $stmt = $adminDb->prepare("
        INSERT IGNORE INTO admin_settings 
        (setting_key, setting_value, setting_type) 
        VALUES (?, ?, ?)
    ");
    
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    
    // Success message
    $success = true;
    $message = "Installation réussie ! Vous pouvez maintenant vous connecter avec les identifiants suivants :<br>
                Email : admin@ticketfoot.com<br>
                Mot de passe : admin123";
    
} catch (Exception $e) {
    $success = false;
    $message = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../../css/admin.css" rel="stylesheet">
    
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--content-bg);
        }
        
        .install-container {
            width: 100%;
            max-width: 600px;
            padding: 2rem;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .install-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <img src="../../assets/images/logo.png" alt="TicketFoot">
            <h1 class="text-white">Installation de l'administration</h1>
        </div>
        
        <div class="card">
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="text-center">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 mb-4">Installation réussie !</h4>
                        <div class="alert alert-info">
                            <?php echo $message; ?>
                        </div>
                        <a href="../login.php" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <i class="bi bi-x-circle text-danger" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 mb-4">Erreur d'installation</h4>
                        <div class="alert alert-danger">
                            <?php echo $message; ?>
                        </div>
                        <button class="btn btn-primary" onclick="window.location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Réessayer
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 