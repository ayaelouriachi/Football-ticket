<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../includes/auth_middleware.php');
require_once(__DIR__ . '/../classes/Auth.php');

// Initialize session
SessionManager::init();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . BASE_URL);
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            throw new Exception('Token de sécurité invalide');
        }
        
        // Get and validate credentials
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        // Debug log
        error_log("Login attempt - Email: " . ($email ?: 'invalid'));
        
        // Validate email
        if (!$email) {
            throw new Exception('Format d\'email invalide');
        }
        
        // Validate password
        if (empty($password)) {
            throw new Exception('Le mot de passe est requis');
        }
        
        // Attempt login
        $auth = new Auth();
        $user = $auth->login($email, $password);
        
        if ($user) {
            // Set success message
            setFlashMessage('success', 'Connexion réussie');
            
            // Redirect to intended page or default
            $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL;
            unset($_SESSION['redirect_after_login']);
            
            header('Location: ' . $redirect);
            exit;
        } else {
            // Debug log
            error_log("Login failed for email: $email");
            throw new Exception('Email ou mot de passe incorrect');
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        setFlashMessage('error', $e->getMessage());
    }
}

// Page title
$pageTitle = 'Connexion';
$pageDescription = 'Connectez-vous à votre compte Football Tickets';

// Include header
require_once(__DIR__ . '/../includes/header.php');
?>

<div class="container">
    <div class="auth-form">
        <h1>Connexion</h1>
        
        <form method="POST" action="" class="form">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       required 
                       class="form-control">
            </div>
            
            <!-- Password -->
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       required 
                       class="form-control">
            </div>
            
            <!-- Submit -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </div>
            
            <!-- Links -->
            <div class="auth-links">
                <a href="<?php echo BASE_URL; ?>pages/forgot-password.php">
                    Mot de passe oublié ?
                </a>
                <span class="separator">|</span>
                <a href="<?php echo BASE_URL; ?>pages/register.php">
                    Créer un compte
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/footer.php'); ?>