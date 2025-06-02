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
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères';
    } elseif (!$terms) {
        $error = 'Vous devez accepter les conditions d\'utilisation';
    } else {
        $user = new User();
        $registerResult = $user->register($first_name, $last_name, $email, $phone, $password);
        
        if ($registerResult['success']) {
            $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            // Optionnel : connexion automatique
            $_SESSION['user_id'] = $registerResult['user_id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user_role'] = 'user';
            header('Location: index.php');
            exit();
        } else {
            $error = $registerResult['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Football Tickets Maroc</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-card register-card">
                <div class="auth-header">
                    <h1>Créer un compte</h1>
                    <p>Rejoignez-nous pour réserver vos places de football</p>
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
                
                <form method="POST" class="auth-form" id="registerForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Prénom *</label>
                            <input type="text" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($first_name ?? ''); ?>" 
                                   required>
                            <i class="input-icon icon-user"></i>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Nom *</label>
                            <input type="text" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($last_name ?? ''); ?>" 
                                   required>
                            <i class="input-icon icon-user"></i>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               required>
                        <i class="input-icon icon-email"></i>
                        <div class="field-validation" id="email-validation"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="tel" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                               placeholder="+212 6 00 00 00 00">
                        <i class="input-icon icon-phone"></i>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Mot de passe *</label>
                            <input type="password" id="password" name="password" required>
                            <i class="input-icon icon-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="icon-eye"></i>
                            </button>
                            <div class="password-strength" id="password-strength"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmer le mot de passe *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="input-icon icon-lock"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="icon-eye"></i>
                            </button>
                            <div class="field-validation" id="confirm-password-validation"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            <span class="checkmark"></span>
                            J'accepte les <a href="terms.php" target="_blank">conditions d'utilisation</a> 
                            et la <a href="privacy.php" target="_blank">politique de confidentialité</a>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter">
                            <span class="checkmark"></span>
                            Je souhaite recevoir les actualités et offres spéciales par email
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">
                        Créer mon compte
                        <i class="icon-arrow-right"></i>
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
                </div>
                
                <div class="social-login">
                    <div class="divider">
                        <span>ou</span>
                    </div>
                    <button class="btn btn-social btn-google">
                        <i class="icon-google"></i>
                        S'inscrire avec Google
                    </button>
                    <button class="btn btn-social btn-facebook">
                        <i class="icon-facebook"></i>
                        S'inscrire avec Facebook
                    </button>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/auth.js"></script>
    <script src="assets/js/register-validation.js"></script>
</body>
</html>