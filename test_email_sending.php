<?php
require 'vendor/autoload.php';
require_once 'config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Activer l'affichage des erreurs PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Créer un dossier pour les logs s'il n'existe pas
if (!file_exists('logs')) {
    mkdir('logs');
}

// Configuration des logs
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/email_test.log');
error_log("\n=== Nouveau test d'envoi d'email (" . date('Y-m-d H:i:s') . ") ===");

try {
    $mail = new PHPMailer(true);

    // Configuration SMTP avec debug maximum
    $mail->SMTPDebug = 3;
    $mail->Debugoutput = function($str, $level) {
        error_log("DEBUG [$level]: $str");
        echo "DEBUG [$level]: $str\n";
    };

    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Utilisation explicite de SSL
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';

    // Vérification de la connexion SMTP
    error_log("Tentative de connexion SMTP...");
    if (!$mail->smtpConnect()) {
        throw new Exception("Échec de la connexion SMTP");
    }
    error_log("Connexion SMTP réussie!");

    // Expéditeur et destinataire
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME);
    error_log("Destinataire configuré: " . SMTP_USERNAME);

    // Contenu du mail de test
    $mail->isHTML(true);
    $mail->Subject = 'Test Email SSL - Football Tickets - ' . date('Y-m-d H:i:s');
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <h2>Test de configuration email (SSL)</h2>
            <p>Ceci est un email de test pour vérifier la configuration SMTP avec SSL.</p>
            <p>Date et heure du test: ' . date('Y-m-d H:i:s') . '</p>
            <p>Configuration utilisée:</p>
            <ul>
                <li>Host: ' . SMTP_HOST . '</li>
                <li>Port: ' . SMTP_PORT . '</li>
                <li>Sécurité: SSL</li>
                <li>Username: ' . SMTP_USERNAME . '</li>
            </ul>
        </div>';
    $mail->AltBody = 'Ceci est un email de test pour vérifier la configuration SMTP avec SSL.';

    // Envoi
    error_log("Tentative d'envoi de l'email...");
    $mail->send();
    error_log("Email de test envoyé avec succès!");
    echo "Email de test envoyé avec succès! Vérifiez les logs pour plus de détails.\n";
    echo "Email envoyé à: " . SMTP_USERNAME . "\n";
    echo "Vérifiez votre boîte de réception ET le dossier spam.\n";

} catch (Exception $e) {
    error_log("ERREUR: L'email n'a pas pu être envoyé.");
    error_log("Message d'erreur: " . $e->getMessage());
    error_log("Trace complète: " . $e->getTraceAsString());
    if (isset($mail)) {
        error_log("Détails PHPMailer: " . $mail->ErrorInfo);
    }
    echo "ERREUR: L'email n'a pas pu être envoyé.\n";
    echo "Message d'erreur: " . $e->getMessage() . "\n";
} 