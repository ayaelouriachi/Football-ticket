<?php
require_once 'generate_ticket_pdf.php';
require_once 'send_ticket_email.php';

// Fonction de test
function testTicketSystem($orderId) {
    try {
        echo "Début du test pour la commande #$orderId\n";
        
        // Test de génération PDF
        echo "Test de génération du PDF...\n";
        $pdfContent = generateFootballTicketPDF($orderId);
        if ($pdfContent) {
            echo "✅ PDF généré avec succès!\n";
            
            // Sauvegarder le PDF pour vérification
            file_put_contents("test_ticket_$orderId.pdf", $pdfContent);
            echo "✅ PDF sauvegardé comme test_ticket_$orderId.pdf\n";
        }
        
        // Test d'envoi d'email
        echo "\nTest d'envoi d'email...\n";
        $emailSent = sendTicketEmail($orderId);
        if ($emailSent) {
            echo "✅ Email envoyé avec succès!\n";
        } else {
            echo "❌ Erreur lors de l'envoi de l'email\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
    }
}

// Vérifier si un ID de commande est fourni
if ($argc != 2) {
    echo "Usage: php test_ticket_system.php [order_id]\n";
    exit(1);
}

$orderId = $argv[1];
testTicketSystem($orderId); 