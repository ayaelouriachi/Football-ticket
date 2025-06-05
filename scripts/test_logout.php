<?php
require_once(__DIR__ . '/../config/session.php');
require_once(__DIR__ . '/../config/constants.php');
require_once(__DIR__ . '/../classes/Auth.php');
require_once(__DIR__ . '/../includes/flash_messages.php');

echo "\n=== TEST DU SYSTÈME DE DÉCONNEXION ===\n\n";

// 1. Test de l'état initial de la session
echo "1. VÉRIFICATION DE L'ÉTAT INITIAL\n";
SessionManager::init();
echo "Session ID: " . session_id() . "\n";
echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? "Oui" : "Non") . "\n";

// 2. Test de la classe Auth
echo "\n2. TEST DE LA CLASSE AUTH\n";
try {
    $auth = new Auth();
    echo "✅ Classe Auth instanciée avec succès\n";
} catch (Exception $e) {
    echo "❌ Erreur lors de l'instanciation de Auth: " . $e->getMessage() . "\n";
}

// 3. Test des messages flash
echo "\n3. TEST DES MESSAGES FLASH\n";
try {
    setFlashMessage('success', 'Test du message flash');
    echo "✅ Message flash défini\n";
    $messages = getFlashMessages();
    echo "Messages récupérés: " . print_r($messages, true) . "\n";
} catch (Exception $e) {
    echo "❌ Erreur avec les messages flash: " . $e->getMessage() . "\n";
}

// 4. Test de la déconnexion
echo "\n4. TEST DE LA DÉCONNEXION\n";
try {
    // Simuler une session utilisateur
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test User';
    $_SESSION['user_role'] = 'user';
    
    echo "Session avant déconnexion:\n";
    echo print_r($_SESSION, true) . "\n";
    
    // Tester la déconnexion
    $result = $auth->logout();
    echo "Résultat déconnexion: " . print_r($result, true) . "\n";
    
    echo "Session après déconnexion:\n";
    echo print_r($_SESSION, true) . "\n";
    
    echo "Cookie de session supprimé: " . (!isset($_COOKIE[session_name()]) ? "Oui" : "Non") . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la déconnexion: " . $e->getMessage() . "\n";
}

// 5. Vérification finale
echo "\n5. VÉRIFICATION FINALE\n";
echo "Session ID après déconnexion: " . session_id() . "\n";
echo "Session vide: " . (empty($_SESSION) ? "Oui" : "Non") . "\n";

echo "\n=== FIN DES TESTS ===\n"; 