<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../send_ticket_email.php';
require_once __DIR__ . '/../generate_ticket_pdf.php';
require_once __DIR__ . '/../process_ticket_payment.php';

// Configuration des logs pour le test
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/test_payment_process.log');
error_log("\n=== Début du test de processus de paiement ===\n");

try {
    // 1. Créer une commande de test dans la base de données
    $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->beginTransaction();
    
    try {
        // Insérer un utilisateur de test si nécessaire
        $userStmt = $conn->prepare("
            INSERT IGNORE INTO users (name, email, created_at)
            VALUES ('Test User', 'test@example.com', CURRENT_TIMESTAMP)
        ");
        $userStmt->execute();
        
        // Récupérer l'ID de l'utilisateur
        $userIdStmt = $conn->prepare("SELECT id FROM users WHERE email = 'test@example.com'");
        $userIdStmt->execute();
        $userId = $userIdStmt->fetchColumn();
        
        // Créer une commande de test
        $orderStmt = $conn->prepare("
            INSERT INTO orders (user_id, total_amount, status, created_at)
            VALUES (?, 500.00, 'pending', CURRENT_TIMESTAMP)
        ");
        $orderStmt->execute([$userId]);
        $orderId = $conn->lastInsertId();
        
        // Ajouter des items à la commande
        $itemStmt = $conn->prepare("
            INSERT INTO order_items (order_id, ticket_category_id, quantity, price_per_ticket)
            SELECT ?, id, 2, 250.00
            FROM ticket_categories
            LIMIT 1
        ");
        $itemStmt->execute([$orderId]);
        
        $conn->commit();
        error_log("[INFO] Commande de test créée avec ID: $orderId");
        
        // 2. Tester directement la fonction sendTicketEmail
        echo "Test d'envoi d'email pour la commande $orderId...\n";
        $emailResult = sendTicketEmail($orderId);
        
        if ($emailResult === true) {
            echo "Email envoyé avec succès !\n";
            error_log("[SUCCESS] Email de test envoyé avec succès pour la commande $orderId");
        } else {
            echo "Échec de l'envoi d'email : " . print_r($emailResult, true) . "\n";
            error_log("[ERROR] Échec de l'envoi d'email de test : " . print_r($emailResult, true));
        }
        
        // 3. Tester directement la fonction processPayment
        echo "\nTest du processus de paiement...\n";
        
        $paypalTransactionId = 'TEST_' . time();
        $result = processPayment($paypalTransactionId, $orderId);
        
        if ($result['success']) {
            echo "Processus de paiement réussi !\n";
            echo "Réponse : " . print_r($result, true) . "\n";
            error_log("[SUCCESS] Test complet réussi pour la commande $orderId");
        } else {
            echo "Processus de paiement échoué !\n";
            echo "Erreur : " . print_r($result, true) . "\n";
            error_log("[ERROR] Échec du test complet : " . print_r($result, true));
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("[ERROR] Exception pendant le test : " . $e->getMessage());
        echo "Erreur pendant le test : " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    error_log("[CRITICAL] Erreur fatale : " . $e->getMessage());
    echo "Erreur fatale : " . $e->getMessage() . "\n";
}

error_log("=== Fin du test de processus de paiement ===\n");

// Classe pour simuler php://input
class TestInputStream {
    public static $content;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr(self::$content, 0, $count);
        self::$content = substr(self::$content, $count);
        return $ret;
    }
    
    public function stream_eof() {
        return empty(self::$content);
    }
    
    public function stream_stat() {
        return [];
    }
} 