<?php
// Configuration de base pour les tests
define('TEST_USER_ID', 1);
define('TEST_ORDER_AMOUNT', 100.00);
define('TEST_CURRENCY', 'EUR');

// Configuration PayPal pour les tests
define('TEST_PAYPAL_CLIENT_ID', 'test_client_id');
define('TEST_PAYPAL_SECRET', 'test_secret');
define('TEST_PAYPAL_MODE', 'sandbox');

// Configuration de la base de données de test
define('TEST_DB_HOST', 'localhost');
define('TEST_DB_NAME', 'football_tickets_test');
define('TEST_DB_USER', 'root');
define('TEST_DB_PASS', '');

// Configuration des chemins
define('TEST_ROOT_PATH', dirname(__DIR__));
define('TEST_LOGS_PATH', TEST_ROOT_PATH . '/logs');
define('TEST_UPLOADS_PATH', TEST_ROOT_PATH . '/uploads');

// Configuration des URLs
define('TEST_BASE_URL', 'http://localhost/football_tickets');
define('TEST_API_URL', TEST_BASE_URL . '/api');
define('TEST_AJAX_URL', TEST_BASE_URL . '/ajax');

// Configuration des timeouts
define('TEST_REQUEST_TIMEOUT', 30);
define('TEST_DB_TIMEOUT', 10);

// Configuration des logs
define('TEST_LOG_LEVEL', 'debug');
define('TEST_LOG_FILE', TEST_LOGS_PATH . '/test.log');

// Données de test
$TEST_USER_DATA = [
    'id' => TEST_USER_ID,
    'email' => 'test@example.com',
    'name' => 'Test User',
    'role' => 'user'
];

$TEST_ORDER_DATA = [
    'reference' => 'TEST_ORDER_' . time(),
    'total_amount' => TEST_ORDER_AMOUNT,
    'currency' => TEST_CURRENCY,
    'status' => 'pending'
];

$TEST_PAYMENT_DATA = [
    'transaction_id' => 'TEST_PAYMENT_' . time(),
    'amount' => TEST_ORDER_AMOUNT,
    'currency' => TEST_CURRENCY,
    'status' => 'completed'
];

// Fonctions utilitaires pour les tests
function generateTestReference($prefix = 'TEST') {
    return $prefix . '_' . time() . '_' . rand(1000, 9999);
}

function createTestUser($data = []) {
    global $TEST_USER_DATA;
    return array_merge($TEST_USER_DATA, $data);
}

function createTestOrder($data = []) {
    global $TEST_ORDER_DATA;
    return array_merge($TEST_ORDER_DATA, $data);
}

function createTestPayment($data = []) {
    global $TEST_PAYMENT_DATA;
    return array_merge($TEST_PAYMENT_DATA, $data);
}

function cleanupTestData() {
    // Nettoyer les données de test de la base de données
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Supprimer les commandes de test
        $db->exec("DELETE FROM orders WHERE reference LIKE 'TEST_%'");
        
        // Supprimer les paiements de test
        $db->exec("DELETE FROM payments WHERE transaction_id LIKE 'TEST_%'");
        
        // Supprimer les utilisateurs de test
        $db->exec("DELETE FROM users WHERE email LIKE 'test_%@example.com'");
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Erreur lors du nettoyage des données de test: " . $e->getMessage());
        return false;
    }
}

// Initialisation de la base de données de test si nécessaire
function initTestDatabase() {
    global $db;
    
    try {
        // Vérifier si les tables existent
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            // Charger et exécuter le script SQL de création des tables
            $sql = file_get_contents(TEST_ROOT_PATH . '/setup/database.sql');
            $db->exec($sql);
            
            // Insérer des données de test initiales
            $sql = file_get_contents(TEST_ROOT_PATH . '/setup/test_data.sql');
            $db->exec($sql);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur lors de l'initialisation de la base de données de test: " . $e->getMessage());
        return false;
    }
} 