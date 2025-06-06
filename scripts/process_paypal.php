<?php
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/paypal.php';

header('Content-Type: application/json');

// Enable error logging
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Set error log file
$logFile = dirname(__DIR__) . '/logs/paypal_error.log';
ini_set('error_log', $logFile);

// Create logs directory if it doesn't exist
$logsDir = dirname(__DIR__) . '/logs';
if (!is_dir($logsDir)) {
    mkdir($logsDir, 0777, true);
}

// Ensure log file exists and is writable
if (!file_exists($logFile)) {
    touch($logFile);
    chmod($logFile, 0666);
}

error_log("\n\n=== New PayPal Request ===");
error_log("Date: " . date('Y-m-d H:i:s'));
error_log("POST Data: " . print_r($_POST, true));

function sendJsonResponse($success, $data = null, $message = null) {
    $response = ['success' => $success];
    if ($data !== null) $response['data'] = $data;
    if ($message !== null) $response['message'] = $message;
    echo json_encode($response);
    exit;
}

try {
    if (!isset($_POST['action'])) {
        throw new Exception('Action not specified');
    }

    $cart = new Cart($db, $_SESSION);
    $cartContents = $cart->getCartContents();
    error_log("Cart Contents: " . print_r($cartContents, true));

    if (empty($cartContents['items'])) {
        throw new Exception('Cart is empty');
    }

    // Convert MAD to USD
    $madAmount = $cartContents['total'];
    $usdAmount = number_format($madAmount * MAD_TO_USD_RATE, 2, '.', '');

    switch ($_POST['action']) {
        case 'create_order':
            $items = [];
            foreach ($cartContents['items'] as $item) {
                $unitPrice = number_format($item['price'] * MAD_TO_USD_RATE, 2, '.', '');
                $items[] = [
                    'name' => $item['match_title'] . ' - ' . $item['category_name'],
                    'description' => 'Match le ' . date('d/m/Y H:i', strtotime($item['match_date'])) . ' à ' . $item['stadium_name'],
                    'quantity' => $item['quantity'],
                    'unit_amount' => [
                        'currency_code' => PAYPAL_CURRENCY,
                        'value' => $unitPrice
                    ]
                ];
            }

            $data = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => PAYPAL_CURRENCY,
                        'value' => $usdAmount,
                        'breakdown' => [
                            'item_total' => [
                                'currency_code' => PAYPAL_CURRENCY,
                                'value' => $usdAmount
                            ]
                        ]
                    ],
                    'items' => $items
                ]],
                'application_context' => [
                    'return_url' => 'http://localhost/football_tickets/payment_success.php',
                    'cancel_url' => 'http://localhost/football_tickets/cart.php'
                ]
            ];

            error_log("Creating PayPal order...");
            error_log("Order data: " . print_r($data, true));

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => PAYPAL_API_URL . '/v2/checkout/orders',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . getPayPalAccessToken(),
                    'PayPal-Request-Id: ' . uniqid('order_', true)
                ],
                CURLOPT_SSL_VERIFYPEER => PAYPAL_VERIFY_SSL,
                CURLOPT_CONNECTTIMEOUT => PAYPAL_CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT => PAYPAL_TIMEOUT,
                CURLOPT_DNS_USE_GLOBAL_CACHE => false,
                CURLOPT_DNS_CACHE_TIMEOUT => 2
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            error_log("PayPal API Response Code: " . $httpCode);
            error_log("PayPal API Response: " . $response);

            if ($httpCode !== 201) {
                $error = json_decode($response, true);
                throw new Exception('PayPal API error: ' . ($error['message'] ?? 'Unknown error'));
            }

            $orderData = json_decode($response, true);
            if (!isset($orderData['id'])) {
                throw new Exception('Invalid response from PayPal');
            }

            sendJsonResponse(true, ['orderID' => $orderData['id']]);
            break;

        case 'capture_order':
            if (!isset($_POST['orderID'])) {
                throw new Exception('Order ID not provided');
            }

            $orderId = $_POST['orderID'];
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => PAYPAL_API_URL . "/v2/checkout/orders/{$orderId}/capture",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . getPayPalAccessToken(),
                    'PayPal-Request-Id: ' . uniqid('capture_', true)
                ],
                CURLOPT_SSL_VERIFYPEER => PAYPAL_VERIFY_SSL,
                CURLOPT_CONNECTTIMEOUT => PAYPAL_CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT => PAYPAL_TIMEOUT,
                CURLOPT_DNS_USE_GLOBAL_CACHE => false,
                CURLOPT_DNS_CACHE_TIMEOUT => 2
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('Curl error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            error_log("PayPal Capture Response Code: " . $httpCode);
            error_log("PayPal Capture Response: " . $response);

            if ($httpCode !== 201) {
                $error = json_decode($response, true);
                throw new Exception('PayPal capture error: ' . ($error['message'] ?? 'Unknown error'));
            }

            $captureData = json_decode($response, true);
            if ($captureData['status'] !== 'COMPLETED') {
                throw new Exception('Payment not completed');
            }

            // Clear the cart after successful payment
            $cart->clearCart();
            
            sendJsonResponse(true, ['status' => 'COMPLETED']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    error_log('PayPal Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    sendJsonResponse(false, null, $e->getMessage());
}

function getPayPalAccessToken() {
    error_log("Getting PayPal access token...");
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_URL . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Accept-Language: en_US'
        ],
        CURLOPT_SSL_VERIFYPEER => PAYPAL_VERIFY_SSL,
        CURLOPT_CONNECTTIMEOUT => PAYPAL_CONNECT_TIMEOUT,
        CURLOPT_TIMEOUT => PAYPAL_TIMEOUT,
        CURLOPT_DNS_USE_GLOBAL_CACHE => false,
        CURLOPT_DNS_CACHE_TIMEOUT => 2
    ]);

    error_log("Making cURL request to: " . PAYPAL_API_URL . '/v1/oauth2/token');
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Curl error when getting access token: ' . $error);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("PayPal Token Response Code: " . $httpCode);
    error_log("PayPal Token Response: " . $response);

    if ($httpCode !== 200) {
        throw new Exception('Failed to get PayPal access token');
    }

    $data = json_decode($response, true);
    if (!isset($data['access_token'])) {
        throw new Exception('Invalid response when getting access token');
    }

    return $data['access_token'];
}
?> 