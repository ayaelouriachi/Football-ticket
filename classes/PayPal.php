<?php
require_once 'config/paypal.php';
require_once 'config/database.php';

class PayPalPayment {
    private $config;
    private $db;
    
    public function __construct($environment = 'sandbox') {
        $this->config = PayPalConfig::getConfig($environment);
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Créer une commande PayPal
    public function createOrder($orderData) {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return ['success' => false, 'message' => 'Erreur d\'authentification PayPal'];
            }
            
            $orderRequest = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $orderData['order_id'],
                        'amount' => [
                            'currency_code' => PAYPAL_CURRENCY,
                            'value' => number_format($orderData['total'], 2, '.', '')
                        ],
                        'description' => $orderData['description'],
                        'items' => $this->formatOrderItems($orderData['items'])
                    ]
                ],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                            'brand_name' => APP_NAME,
                            'locale' => 'fr-MA',
                            'landing_page' => 'LOGIN',
                            'user_action' => 'PAY_NOW',
                            'return_url' => BASE_URL . 'payment/success.php',
                            'cancel_url' => BASE_URL . 'payment/cancel.php'
                        ]
                    ]
                ]
            ];
            
            $response = $this->makeApiCall('/v2/checkout/orders', 'POST', $orderRequest, $accessToken);
            
            if ($response && isset($response['id'])) {
                // Enregistrer la transaction
                $this->logTransaction($orderData['order_id'], $response['id'], 'created');
                
                return [
                    'success' => true,
                    'order_id' => $response['id'],
                    'approval_url' => $this->getApprovalUrl($response['links'] ?? [])
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création de la commande PayPal'];
            
        } catch (Exception $e) {
            error_log("PayPal create order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique PayPal'];
        }
    }
    
    // Capturer le paiement
    public function captureOrder($paypalOrderId) {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return ['success' => false, 'message' => 'Erreur d\'authentification PayPal'];
            }
            
            $response = $this->makeApiCall("/v2/checkout/orders/{$paypalOrderId}/capture", 'POST', [], $accessToken);
            
            if ($response && $response['status'] === 'COMPLETED') {
                $captureId = $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;
                $amount = $response['purchase_units'][0]['payments']['captures'][0]['amount']['value'] ?? 0;
                
                // Mettre à jour le log de transaction
                $this->logTransaction(null, $paypalOrderId, 'captured', $captureId, $amount);
                
                return [
                    'success' => true,
                    'capture_id' => $captureId,
                    'amount' => $amount,
                    'transaction_id' => $paypalOrderId
                ];
            }
            
            return ['success' => false, 'message' => 'Échec de la capture du paiement'];
            
        } catch (Exception $e) {
            error_log("PayPal capture order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique lors de la capture'];
        }
    }
    
    // Obtenir les détails d'une commande
    public function getOrderDetails($paypalOrderId) {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                return null;
            }
            
            return $this->makeApiCall("/v2/checkout/orders/{$paypalOrderId}", 'GET', [], $accessToken);
            
        } catch (Exception $e) {
            error_log("PayPal get order details error: " . $e->getMessage());
            return null;
        }
    }
    
    // Obtenir le token d'accès
    private function getAccessToken() {
        try {
            $credentials = base64_encode($this->config['client_id'] . ':' . $this->config['client_secret']);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->config['base_url'] . '/v1/oauth2/token',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . $credentials,
                    'Accept: application/json',
                    'Accept-Language: en_US',
                    'Content-Type: application/x-www-form-urlencoded'
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data['access_token'] ?? null;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("PayPal get access token error: " . $e->getMessage());
            return null;
        }
    }
    
    // Effectuer un appel API
    private function makeApiCall($endpoint, $method = 'GET', $data = [], $accessToken = null) {
        try {
            $ch = curl_init();
            
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            
            if ($accessToken) {
                $headers[] = 'Authorization: Bearer ' . $accessToken;
            }
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->config['base_url'] . $endpoint,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method
            ]);
            
            if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true);
            }
            
            error_log("PayPal API call failed: HTTP {$httpCode} - {$response}");
            return null;
            
        } catch (Exception $e) {
            error_log("PayPal API call error: " . $e->getMessage());
            return null;
        }
    }
    
    // Formater les articles pour PayPal
    private function formatOrderItems($items) {
        $paypalItems = [];
        
        foreach ($items as $item) {
            $paypalItems[] = [
                'name' => $item['name'],
                'quantity' => (string)$item['quantity'],
                'unit_amount' => [
                    'currency_code' => PAYPAL_CURRENCY,
                    'value' => number_format($item['price'], 2, '.', '')
                ]
            ];
        }
        
        return $paypalItems;
    }
    
    // Obtenir l'URL d'approbation
    private function getApprovalUrl($links) {
        foreach ($links as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }
        return null;
    }
    
    // Logger les transactions
    private function logTransaction($orderId, $paypalOrderId, $status, $captureId = null, $amount = null) {
        try {
            $sql = "INSERT INTO payment_logs (order_id, paypal_order_id, status, capture_id, amount, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    status = VALUES(status), capture_id = VALUES(capture_id), 
                    amount = VALUES(amount), updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId, $paypalOrderId, $status, $captureId, $amount]);
            
        } catch (PDOException $e) {
            error_log("Log PayPal transaction error: " . $e->getMessage());
        }
    }
}
?>