<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/email_config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Configuration des logs
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/email_test.log');

echo "=== Test du système d'envoi d'emails ===\n\n";

// Test 1: Configuration SMTP
echo "1. Vérification de la configuration SMTP...\n";
try {
    echo "Host: " . SMTP_HOST . "\n";
    echo "Port: " . SMTP_PORT . "\n";
    echo "Username: " . SMTP_USERNAME . "\n";
    echo "Password: " . (empty(SMTP_PASSWORD) ? "Non défini" : "Défini") . "\n";
    echo "From Address: " . MAIL_FROM_ADDRESS . "\n";
    echo "From Name: " . MAIL_FROM_NAME . "\n";
    echo "✓ Configuration SMTP chargée\n\n";
} catch (Exception $e) {
    echo "✗ Erreur de configuration SMTP: " . $e->getMessage() . "\n\n";
}

// Test 2: Connexion SMTP
echo "2. Test de connexion SMTP...\n";
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    
    // Test de connexion uniquement
    $mail->smtpConnect();
    echo "✓ Connexion SMTP réussie\n\n";
} catch (Exception $e) {
    echo "✗ Erreur de connexion SMTP: " . $e->getMessage() . "\n\n";
}

// Test 3: Envoi d'un email simple
echo "3. Test d'envoi d'un email simple...\n";
try {
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME); // Envoi à soi-même pour le test
    
    $mail->isHTML(true);
    $mail->Subject = 'Test - Email simple';
    $mail->Body = '<h1>Test d\'envoi d\'email</h1><p>Ceci est un test du système d\'envoi d\'emails.</p>';
    $mail->AltBody = 'Test d\'envoi d\'email - Ceci est un test du système d\'envoi d\'emails.';
    
    $mail->send();
    echo "✓ Email simple envoyé avec succès\n\n";
} catch (Exception $e) {
    echo "✗ Erreur d'envoi d'email simple: " . $e->getMessage() . "\n\n";
}

// Test 4: Génération et envoi d'un PDF
echo "4. Test de génération et envoi d'un PDF...\n";
try {
    require_once __DIR__ . '/../generate_ticket_pdf.php';
    
    // Créer un PDF de test
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Test PDF', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Ceci est un PDF de test.', 0, 1, 'C');
    $pdfContent = $pdf->Output('S');
    
    echo "✓ PDF généré avec succès\n";
    
    // Envoyer le PDF par email
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->CharSet = 'UTF-8';
    
    $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
    $mail->addAddress(SMTP_USERNAME);
    
    $mail->isHTML(true);
    $mail->Subject = 'Test - Email avec PDF';
    $mail->Body = '<h1>Test d\'envoi d\'email avec PDF</h1><p>Un PDF de test est attaché à cet email.</p>';
    $mail->AltBody = 'Test d\'envoi d\'email avec PDF - Un PDF de test est attaché à cet email.';
    
    $mail->addStringAttachment($pdfContent, 'test.pdf', 'base64', 'application/pdf');
    
    $mail->send();
    echo "✓ Email avec PDF envoyé avec succès\n\n";
} catch (Exception $e) {
    echo "✗ Erreur lors du test PDF: " . $e->getMessage() . "\n\n";
}

// Test 5: Vérification des permissions
echo "5. Vérification des permissions...\n";
$logsDir = __DIR__ . '/../logs';
$tempDir = sys_get_temp_dir();

echo "Dossier logs: $logsDir\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($logsDir)), -4) . "\n";
echo "Accessible en écriture: " . (is_writable($logsDir) ? "Oui" : "Non") . "\n\n";

echo "Dossier temp: $tempDir\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($tempDir)), -4) . "\n";
echo "Accessible en écriture: " . (is_writable($tempDir) ? "Oui" : "Non") . "\n\n";

// Test 6: Test du workflow complet
echo "6. Test du workflow complet...\n";
try {
    require_once __DIR__ . '/../send_ticket_email.php';
    
    // Créer une commande de test dans la base de données
    $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insérer une commande de test
    $conn->beginTransaction();
    
    try {
        // Créer un utilisateur de test si nécessaire
        $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email) VALUES (?, ?)");
        $stmt->execute(['Test User', SMTP_USERNAME]);
        $userId = $conn->lastInsertId() ?: $conn->query("SELECT id FROM users WHERE email = '" . SMTP_USERNAME . "'")->fetchColumn();
        
        // Créer une commande de test
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, total_amount, status, created_at) 
            VALUES (?, 100.00, 'pending', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$userId]);
        $orderId = $conn->lastInsertId();
        
        $conn->commit();
        
        echo "Commande de test créée (ID: $orderId)\n";
        
        // Tester l'envoi du ticket
        $result = sendTicketEmail($orderId);
        
        if ($result) {
            echo "✓ Workflow complet testé avec succès\n\n";
        } else {
            echo "✗ Échec du workflow complet\n\n";
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "✗ Erreur lors du test du workflow: " . $e->getMessage() . "\n\n";
}

echo "\n=== Fin des tests ===\n";
?> 