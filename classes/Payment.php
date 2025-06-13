<?php
require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../includes/logger.php');

class Payment {
    private $db;
    private $table = 'payments';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function createPayment($orderId, $transactionId, $amount, $currency = 'EUR') {
        try {
            // 1. Insérer dans la table payments
            $sql = "INSERT INTO {$this->table} 
                    (order_id, transaction_id, amount, currency, status, payment_method) 
                    VALUES (?, ?, ?, ?, 'completed', 'paypal')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $orderId,
                $transactionId,
                $amount,
                $currency
            ]);
            
            if ($result) {
                // 2. Mettre à jour la table orders
                $orderSql = "UPDATE orders SET 
                            status = 'paid',
                            payment_status = 'completed',
                            payment_method = 'paypal',
                            payment_id = ?
                            WHERE id = ?";
                
                $orderStmt = $this->db->prepare($orderSql);
                $orderResult = $orderStmt->execute([$transactionId, $orderId]);
                
                if ($orderResult) {
                    return true;
                }
            }
            
            return false;
            
        } catch (PDOException $e) {
            error_log("Erreur de paiement: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPaymentByOrderId($orderId) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE order_id = ? ORDER BY created_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur de récupération du paiement: " . $e->getMessage());
            return false;
        }
    }
    
    public function getPaymentByTransactionId($transactionId) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE transaction_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$transactionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erreur de récupération du paiement: " . $e->getMessage());
            return false;
        }
    }
    
    public function updatePaymentStatus($paymentId, $status, $details = null) {
        try {
            $sql = "UPDATE {$this->table} SET status = ?, payment_details = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $status,
                $details ? json_encode($details) : null,
                $paymentId
            ]);
            
            if ($result) {
                Logger::payment("Statut du paiement mis à jour", [
                    'payment_id' => $paymentId,
                    'new_status' => $status
                ]);
                return true;
            }
            
            Logger::error("Échec de la mise à jour du statut", [
                'payment_id' => $paymentId,
                'status' => $status,
                'error' => $stmt->errorInfo()
            ]);
            return false;
            
        } catch (PDOException $e) {
            Logger::error("Erreur lors de la mise à jour du statut", [
                'error' => $e->getMessage(),
                'payment_id' => $paymentId
            ]);
            throw new Exception("Erreur lors de la mise à jour du statut: " . $e->getMessage());
        }
    }
    
    public function validatePaymentData($paymentData) {
        $errors = [];
        
        // Validation des champs requis
        $requiredFields = ['transaction_id', 'amount', 'currency'];
        foreach ($requiredFields as $field) {
            if (!isset($paymentData[$field]) || empty($paymentData[$field])) {
                $errors[] = "Le champ '$field' est requis";
            }
        }
        
        // Validation du montant
        if (isset($paymentData['amount']) && (!is_numeric($paymentData['amount']) || $paymentData['amount'] <= 0)) {
            $errors[] = "Le montant doit être un nombre positif";
        }
        
        // Validation de la devise
        if (isset($paymentData['currency']) && !in_array($paymentData['currency'], ['EUR', 'USD', 'MAD'])) {
            $errors[] = "Devise non supportée";
        }
        
        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
} 