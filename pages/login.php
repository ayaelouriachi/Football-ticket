<?php
session_start();
require_once 'config/database.php';
require_once 'classes/User.php';

$error = '';
$success = '';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $user = new User();
        $loginResult = $user->login($email, $password);
        
        if ($loginResult['success']) {
            $_SESSION['user_id'] = $loginResult['user']['id'];
            $_SESSION['user_email'] = $loginResult['user']['email'];
            $_SESSION['user_name'] = $loginResult['user']['first_name'] . ' ' . $loginResult['user']['last_name'];
            $_SESSION['user_role'] = $loginResult['user']['role'] ?? 'user';
            
            // Rediriger vers la page demandée ou index
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit();
        } else {
            $error = $loginResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Football Tickets Maroc</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1>Connexion</h1>
                    <p>Connectez-vous pour réserver vos billets</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="icon-error"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="icon-success"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               required>
                        <i class="input-icon icon-email"></i>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                        <i class="input-icon icon-lock"></i>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="icon-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Se souvenir de moi
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Mot de passe oublié ?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        Se connecter
                        <i class="icon-arrow-right"></i>
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Pas encore de compte ? <a href="register.php">Créer un compte</a></p>
                </div>
                
                <div class="social-login">
                    <div class="divider">
                        <span>ou</span>
                    </div>
                    <button class="btn btn-social btn-google">
                        <i class="icon-google"></i>
                        Continuer avec Google
                    </button>
                    <button class="btn btn-social btn-facebook">
                        <i class="icon-facebook"></i>
                        Continuer avec Facebook
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>