<?php
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/../includes/logger.php');

// Configuration du test
$config = [
    'user_id' => 1,
    'order_reference' => 'TEST_' . time(),
    'total_amount' => 100.00,
    'currency' => 'EUR'
];

echo "=== Test du processus de paiement ===\n\n";

try {
    // 1. Simuler une session utilisateur
    session_start();
    $_SESSION['user_id'] = $config['user_id'];
    echo "✓ Session utilisateur initialisée\n";

    // 2. Créer une commande de test
    $order = new Order();
    $orderId = $order->createOrder([
        'user_id' => $config['user_id'],
        'reference' => $config['order_reference'],
        'total_amount' => $config['total_amount'],
        'status' => ORDER_STATUS_PENDING
    ]);

    if (!$orderId) {
        throw new Exception("Échec de la création de la commande");
    }

    $_SESSION['order_id'] = $orderId;
    echo "✓ Commande créée (ID: $orderId)\n";

    // 3. Simuler une réponse PayPal
    $paypalResponse = [
        'id' => 'TEST_PAYMENT_' . time(),
        'status' => 'COMPLETED',
        'payer' => [
            'payer_id' => 'TEST_PAYER_' . time(),
            'email_address' => 'test@example.com',
            'name' => [
                'given_name' => 'John',
                'surname' => 'Doe'
            ]
        ],
        'purchase_units' => [
            [
                'amount' => [
                    'value' => (string)$config['total_amount'],
                    'currency_code' => $config['currency']
                ],
                'payments' => [
                    'captures' => [
                        [
                            'id' => 'TEST_CAPTURE_' . time(),
                            'status' => 'COMPLETED'
                        ]
                    ]
                ]
            ]
        ]
    ];

    echo "✓ Données PayPal simulées créées\n";

    // 4. Préparer la requête de mise à jour
    $updateData = [
        'order_id' => $orderId,
        'payment_data' => $paypalResponse
    ];

    echo "\nEnvoi de la requête de mise à jour...\n";
    echo "Données: " . json_encode($updateData, JSON_PRETTY_PRINT) . "\n\n";

    // 5. Envoyer la requête à update_order.php
    $ch = curl_init('http://localhost/football_tickets/ajax/update_order.php');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($updateData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest'
        ],
        CURLOPT_COOKIE => session_name() . '=' . session_id()
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "Résultats:\n";
    echo "- Code HTTP: $httpCode\n";
    echo "- Type de contenu: $contentType\n";

    if ($error) {
        echo "- Erreur cURL: $error\n";
    } else {
        echo "- Réponse brute:\n$response\n\n";
        
        $jsonResponse = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "- Réponse décodée:\n";
            echo json_encode($jsonResponse, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "- Erreur de décodage JSON: " . json_last_error_msg() . "\n";
            echo "- Réponse non-JSON:\n$response\n";
        }
    }

    // 6. Vérifier l'état final de la commande
    $finalOrderData = $order->getOrderById($orderId);
    echo "\nÉtat final de la commande:\n";
    echo json_encode($finalOrderData, JSON_PRETTY_PRINT) . "\n";

    // 7. Nettoyer
    session_destroy();
    echo "\n✓ Test terminé\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} 