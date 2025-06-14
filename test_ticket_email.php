<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/EmailService.php';
require_once __DIR__ . '/classes/PDFGenerator.php';

// Données de test
$orderDetails = [
    'order_id' => 'TEST123',
    'match_title' => 'Match Test vs Test FC',
    'match_date' => '2024-04-01 20:00:00',
    'stadium_name' => 'Stade Test',
    'ticket_category' => 'VIP',
    'quantity' => 2,
    'total_amount' => 500.00,
    'user_email' => 'armyb4810@gmail.com'  // Votre email Gmail
];

try {
    // 1. Générer le PDF
    echo "1. Génération du PDF...\n";
    $pdfGenerator = new PDFGenerator();
    $ticketPdfPath = $pdfGenerator->generateTicket($orderDetails);
    echo "PDF généré : " . $ticketPdfPath . "\n";

    // 2. Envoyer l'email
    echo "2. Envoi de l'email...\n";
    $emailService = new EmailService(true); // true pour activer le debug
    $emailResult = $emailService->sendTicketConfirmation(
        $orderDetails['user_email'],
        'Test - Confirmation de votre commande #' . $orderDetails['order_id'],
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
        echo "Email envoyé avec succès!\n";
    }

} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
} 