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

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFToken($token)) {
            throw new Exception('Token de sécurité invalide');
        }
        
        // Get and validate form data
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate email
        if (!$email) {
            throw new Exception('Email invalide');
        }
        
        // Validate name
        if (empty($name)) {
            throw new Exception('Le nom est requis');
        }
        
        // Validate password
        if (strlen($password) < 8) {
            throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Les mots de passe ne correspondent pas');
        }
        
        // Register user
        $auth = new Auth();
        $userData = [
            'email' => $email,
            'password' => $password,
            'name' => $name
        ];
        
        $user = $auth->register($userData);
        
        if ($user) {
            // Set success message
            setFlashMessage('success', 'Inscription réussie ! Vous êtes maintenant connecté.');
            
            // Redirect to home page
            header('Location: ' . BASE_URL);
            exit;
        }
        
    } catch (Exception $e) {
        setFlashMessage('error', $e->getMessage());
    }
}

// Page title
$pageTitle = 'Inscription';
$pageDescription = 'Créez votre compte Football Tickets';

// Include header
require_once(__DIR__ . '/../includes/header.php');
?>

<div class="container">
    <div class="auth-form">
        <h1>Inscription</h1>
        
        <form method="POST" action="" class="form">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Name -->
            <div class="form-group">
                <label for="name">Nom complet</label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       required 
                       class="form-control">
            </div>
            
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
                       minlength="8"
                       class="form-control">
                <small class="form-text text-muted">
                    Le mot de passe doit contenir au moins 8 caractères
                </small>
            </div>
            
            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       required 
                       minlength="8"
                       class="form-control">
            </div>
            
            <!-- Submit -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </div>
            
            <!-- Links -->
            <div class="auth-links">
                <span>Déjà inscrit ?</span>
                <a href="<?php echo BASE_URL; ?>pages/login.php">
                    Se connecter
                </a>
            </div>
        </form>
    </div>
</div>

<?php require_once(__DIR__ . '/../includes/footer.php'); ?>