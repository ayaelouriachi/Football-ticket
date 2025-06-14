<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/EmailService.php';
require_once __DIR__ . '/classes/PDFGenerator.php';

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/test_payment.log');

echo "=== TEST DU FLUX DE PAIEMENT ===\n\n";

try {
    // Get order details from database
    $order = new Order();
    $dbOrder = $order->getOrderById(16); // Use the order ID we created

    if (!$dbOrder || empty($dbOrder['items'])) {
        throw new Exception("Commande non trouvée ou pas d'articles");
    }

    // Format data to match test script structure
    $orderDetails = [
        'order_id' => $dbOrder['id'],
        'match_title' => $dbOrder['items'][0]['home_team_name'] . ' vs ' . $dbOrder['items'][0]['away_team_name'],
        'match_date' => $dbOrder['items'][0]['match_date'],
        'stadium_name' => $dbOrder['items'][0]['stadium_name'],
        'ticket_category' => $dbOrder['items'][0]['category_name'],
        'quantity' => $dbOrder['items'][0]['quantity'],
        'total_amount' => $dbOrder['total_amount'],
        'user_email' => $dbOrder['email']
    ];

    echo "1. Données de la commande formatées :\n";
    print_r($orderDetails);

    // Generate PDF
    echo "\n2. Génération du PDF...\n";
    $pdfGenerator = new PDFGenerator();
    $ticketPdfPath = $pdfGenerator->generateTicket($orderDetails);
    echo "PDF généré : " . $ticketPdfPath . "\n";

    // Send email
    echo "\n3. Envoi de l'email...\n";
    $emailService = new EmailService(true); // Enable debug mode
    $emailResult = $emailService->sendTicketConfirmation(
        $orderDetails['user_email'],
        'TEST - Confirmation de votre commande #' . $orderDetails['order_id'],
        $ticketPdfPath,
        [
            'order_id' => $orderDetails['order_id'],
            'match_title' => $orderDetails['match_title'],
            'match_date' => $orderDetails['match_date'],
            'stadium' => $orderDetails['stadium_name'],
            'ticket_category' => $orderDetails['ticket_category'],
            'quantity' => $orderDetails['quantity'],
            'total' => $orderDetails['total_amount']
        ]
    );

    if ($emailResult) {
        echo "\n✅ TEST RÉUSSI : Email envoyé avec succès!\n";
    } else {
        throw new Exception("Échec de l'envoi de l'email");
    }

} catch (Exception $e) {
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    error_log("Test payment error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
} 