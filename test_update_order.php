<?php
session_start();

// Simuler une session utilisateur
$_SESSION['user_id'] = 1;
$_SESSION['order_id'] = 123;

// Données de test PayPal
$testData = [
    'order_id' => 123,
    'payment_data' => [
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
                    'value' => '100.00',
                    'currency_code' => 'EUR'
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
    ]
];

echo "=== Test de mise à jour de commande ===\n\n";
echo "Données envoyées:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Initialiser cURL
$ch = curl_init('http://localhost/football_tickets/ajax/update_order.php');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest'
    ]
]);

// Exécuter la requête
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
curl_close($ch);

echo "=== Résultats ===\n";
echo "Code HTTP: " . $httpCode . "\n";
echo "Type de contenu: " . $contentType . "\n";

if ($error) {
    echo "Erreur cURL: " . $error . "\n";
} else {
    echo "Réponse brute:\n" . $response . "\n\n";
    
    // Tenter de décoder la réponse JSON
    $jsonResponse = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "Réponse décodée:\n";
        echo json_encode($jsonResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Erreur de décodage JSON: " . json_last_error_msg() . "\n";
        echo "Réponse non-JSON:\n" . $response . "\n";
    }
} 