<?php
require_once(__DIR__ . '/includes/config.php');
require_once(__DIR__ . '/includes/auth.php');

// Initialize auth
$auth = new AdminAuth($adminDb);

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        if (empty($email) || empty($password)) {
            throw new Exception('Veuillez remplir tous les champs.');
        }
        
        if ($auth->login($email, $password)) {
            // Redirect to dashboard or requested page
            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = "Connexion Admin - TicketFoot";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../css/admin.css" rel="stylesheet">
    
    <style>
        body {
            background: #1a1c23;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
        
        .login-header h1 {
            color: #fff;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
        
        .form-floating {
            margin-bottom: 1rem;
        }
        
        .form-floating input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .form-floating input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            box-shadow: none;
        }
        
        .form-floating label {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .btn-primary {
            background: #3B82F6;
            border: none;
            padding: 0.8rem;
            font-weight: 500;
            width: 100%;
        }
        
        .btn-primary:hover {
            background: #2563EB;
        }
        
        .forgot-password {
            text-align: center;
            margin-top: 1rem;
        }
        
        .forgot-password a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password a:hover {
            color: #fff;
        }
        
        .alert {
            background: rgba(255, 255, 255, 0.05);
            border: none;
            color: #fff;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: #FCA5A5;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #6EE7B7;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../assets/images/logo.png" alt="TicketFoot Admin" class="mb-4">
            <h1>Administration TicketFoot</h1>
            <p>Connectez-vous pour gérer votre site</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                <label for="email">Adresse email</label>
            </div>
            
            <div class="form-floating mb-4">
                <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                <label for="password">Mot de passe</label>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
            </button>
        </form>
        
        <div class="forgot-password">
            <a href="reset-password.php">Mot de passe oublié ?</a>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            
            const forms = document.querySelectorAll('.needs-validation');
            
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
