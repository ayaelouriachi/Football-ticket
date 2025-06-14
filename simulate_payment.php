<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/EmailService.php';
require_once __DIR__ . '/classes/PDFGenerator.php';

// Configure error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/payment_simulation.log');

error_log("🧪 SIMULATION PAIEMENT DÉMARRÉE");

// Simulated PayPal payment data
$simulated_payment_data = [
    'transaction_id' => 'TEST_' . time(),
    'order_id' => '1', // Use a real order ID from your database
    'amount' => '500.00',
    'currency' => 'MAD',
    'status' => 'COMPLETED'
];

error_log("💳 DONNÉES PAIEMENT SIMULÉES: " . json_encode($simulated_payment_data));

try {
    // Get order details (using real database data)
    error_log("🔄 RÉCUPÉRATION COMMANDE - ID: " . $simulated_payment_data['order_id']);
    $order = new Order();
    $orderDetails = $order->getOrderById($simulated_payment_data['order_id']);
    
    if (!$orderDetails) {
        error_log("❌ ERREUR: Commande non trouvée");
        throw new Exception("Commande non trouvée");
    }
    
    error_log("📊 DONNÉES COMMANDE: " . json_encode($orderDetails));
    error_log("📧 EMAIL CLIENT: " . ($orderDetails['email'] ?? 'NON TROUVÉ'));
    
    // Generate PDF
    $ticketsDir = __DIR__ . '/tickets';
    if (!file_exists($ticketsDir)) {
        if (!mkdir($ticketsDir, 0777, true)) {
            error_log("❌ ERREUR: Impossible de créer le dossier tickets");
            throw new Exception("Impossible de créer le dossier tickets");
        }
    }
    
    error_log("📄 GÉNÉRATION PDF - Dossier: " . $ticketsDir);
    $pdfGenerator = new PDFGenerator();
    $ticketPdfPath = $pdfGenerator->generateTicket($orderDetails);
    
    if (!file_exists($ticketPdfPath)) {
        error_log("❌ PDF NON CRÉÉ: " . $ticketPdfPath);
        throw new Exception("Échec de la génération du PDF");
    }
    
    error_log("✅ PDF CRÉÉ: " . $ticketPdfPath . " (Taille: " . filesize($ticketPdfPath) . " bytes)");
    
    // Send email
    error_log("📨 ENVOI EMAIL - À: " . $orderDetails['email']);
    $emailData = [
        'order_id' => $simulated_payment_data['order_id'],
        'match_title' => $orderDetails['items'][0]['home_team_name'] . ' vs ' . $orderDetails['items'][0]['away_team_name'],
        'match_date' => $orderDetails['items'][0]['match_date'],
        'stadium' => $orderDetails['items'][0]['stadium_name'],
        'ticket_category' => $orderDetails['items'][0]['category_name'],
        'quantity' => $orderDetails['items'][0]['quantity'],
        'total' => $orderDetails['total_amount']
    ];
    error_log("📨 DONNÉES EMAIL: " . json_encode($emailData));
    
    $emailService = new EmailService(true); // Enable debug mode
    $emailResult = $emailService->sendTicketConfirmation(
        $orderDetails['email'],
        'TEST - Confirmation de votre commande #' . $simulated_payment_data['order_id'],
        $ticketPdfPath,
        $emailData
    );
    
    error_log("📧 RÉSULTAT ENVOI: " . ($emailResult ? "SUCCÈS" : "ÉCHEC"));
    
    if (!$emailResult) {
        error_log("❌ ERREUR: Échec de l'envoi de l'email");
        throw new Exception("Échec de l'envoi de l'email");
    }
    
    echo "✅ SIMULATION RÉUSSIE!\n";
    echo "📋 Vérifiez les logs dans: " . ini_get('error_log') . "\n";
    
} catch (Exception $e) {
    error_log("❌ ERREUR SIMULATION: " . $e->getMessage());
    error_log("📋 TRACE: " . $e->getTraceAsString());
    
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
} 