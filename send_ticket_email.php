<?php
require 'vendor/autoload.php';
require_once 'config/email_config.php';
require_once 'generate_ticket_pdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendTicketEmail($orderId) {
    try {
        // Générer PDF
        try {
            $pdfContent = generateFootballTicketPDF($orderId);
            if (empty($pdfContent)) {
                error_log("[ERROR] PDF généré vide pour la commande $orderId");
                return "Erreur: PDF généré vide";
            }
            error_log("[INFO] PDF généré avec succès pour la commande $orderId");
        } catch (Exception $e) {
            error_log("[ERROR] Erreur génération PDF pour commande $orderId: " . $e->getMessage());
            return "Erreur génération PDF: " . $e->getMessage();
        }
        
        // Récupérer infos commande
        try {
            $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $conn->prepare("
                SELECT 
                    o.id as order_id,
                    o.total_amount,
                    o.created_at,
                    u.name as user_name,
                    u.email as user_email,
                    m.title as match_title,
                    m.match_date,
                    m.competition,
                    s.name as stadium_name,
                    s.city as stadium_city,
                    t1.name as team1_name,
                    t2.name as team2_name,
                    SUM(oi.quantity) as total_tickets
                FROM orders o
                JOIN users u ON o.user_id = u.id
                JOIN order_items oi ON o.id = oi.order_id
                JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
                JOIN matches m ON tc.match_id = m.id
                JOIN stadiums s ON m.stadium_id = s.id
                JOIN teams t1 ON m.team1_id = t1.id
                JOIN teams t2 ON m.team2_id = t2.id
                WHERE o.id = ?
                GROUP BY o.id
            ");
            $stmt->execute([$orderId]);
            $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$orderData) {
                error_log("[ERROR] Données commande non trouvées pour commande $orderId");
                return "Erreur: Commande non trouvée";
            }
            error_log("[INFO] Données commande récupérées avec succès pour commande $orderId");
        } catch (Exception $e) {
            error_log("[ERROR] Erreur récupération données pour commande $orderId: " . $e->getMessage());
            return "Erreur récupération données: " . $e->getMessage();
        }
        
        $mail = new PHPMailer(true);
        
        try {
            // Configuration SMTP avec vérification
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Activer le debug SMTP temporairement
            $mail->SMTPDebug = 3;
            $debugOutput = '';
            $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
                $debugOutput .= "$level: $str\n";
            };
            
            // Expéditeur et destinataire
            $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            $mail->addAddress($orderData['user_email'], $orderData['user_name']);
            
            // Contenu email
            $mail->isHTML(true);
            $mail->Subject = 'Vos tickets pour ' . $orderData['team1_name'] . ' vs ' . $orderData['team2_name'];
            
            $mailBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #006400; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;'>
                    <h1 style='margin: 0;'>Confirmation de votre commande</h1>
                    <p style='margin: 10px 0 0;'>Commande N°: {$orderData['order_id']}</p>
                </div>
                
                <div style='padding: 30px; background-color: #f9f9f9;'>
                    <h2 style='color: #333; margin-top: 0;'>Bonjour {$orderData['user_name']},</h2>
                    <p style='color: #666;'>Votre paiement a été confirmé avec succès ! Voici les détails de votre commande :</p>
                    
                    <div style='background-color: white; padding: 20px; border-radius: 5px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
                        <h3 style='color: #006400; margin-top: 0; border-bottom: 2px solid #006400; padding-bottom: 10px;'>Détails du match</h3>
                        <table style='width: 100%; border-collapse: collapse;'>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Match :</strong></td>
                                <td style='padding: 8px 0; color: #333;'>{$orderData['team1_name']} vs {$orderData['team2_name']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Compétition :</strong></td>
                                <td style='padding: 8px 0; color: #333;'>{$orderData['competition']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Date :</strong></td>
                                <td style='padding: 8px 0; color: #333;'>" . date('d/m/Y à H:i', strtotime($orderData['match_date'])) . "</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Stade :</strong></td>
                                <td style='padding: 8px 0; color: #333;'>{$orderData['stadium_name']} - {$orderData['stadium_city']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Nombre de tickets :</strong></td>
                                <td style='padding: 8px 0; color: #333;'>{$orderData['total_tickets']}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #666;'><strong>Total payé :</strong></td>
                                <td style='padding: 8px 0; color: #333;'>" . number_format($orderData['total_amount'], 2) . " MAD</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div style='background-color: #fff3cd; padding: 20px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;'>
                        <h4 style='margin-top: 0; color: #856404;'>Instructions importantes :</h4>
                        <ul style='color: #856404; margin: 10px 0; padding-left: 20px;'>
                            <li style='margin-bottom: 8px;'>Vos tickets sont en pièce jointe au format PDF</li>
                            <li style='margin-bottom: 8px;'>Imprimez-les ou présentez-les sur votre téléphone à l'entrée</li>
                            <li style='margin-bottom: 8px;'>Arrivez au stade 1 heure avant le début du match</li>
                            <li style='margin-bottom: 8px;'>Conservez vos tickets jusqu'à la fin du match</li>
                        </ul>
                    </div>
                    
                    <p style='margin-top: 30px; color: #666;'>Merci pour votre confiance et bon match !</p>
                    <p style='color: #666;'><em>L'équipe Football Tickets</em></p>
                </div>
                
                <div style='background-color: #006400; color: white; padding: 15px; text-align: center; font-size: 12px; border-radius: 0 0 5px 5px;'>
                    <p style='margin: 0;'>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
            ";
            
            $mail->Body = $mailBody;
            
            // Version texte simple
            $mail->AltBody = "
            Confirmation de votre commande N°{$orderData['order_id']}
            
            Bonjour {$orderData['user_name']},
            
            Votre paiement a été confirmé avec succès !
            
            DÉTAILS DU MATCH :
            - Match : {$orderData['team1_name']} vs {$orderData['team2_name']}
            - Compétition : {$orderData['competition']}
            - Date : " . date('d/m/Y à H:i', strtotime($orderData['match_date'])) . "
            - Stade : {$orderData['stadium_name']} - {$orderData['stadium_city']}
            - Nombre de tickets : {$orderData['total_tickets']}
            - Total payé : " . number_format($orderData['total_amount'], 2) . " MAD
            
            INSTRUCTIONS IMPORTANTES :
            * Vos tickets sont en pièce jointe au format PDF
            * Imprimez-les ou présentez-les sur votre téléphone à l'entrée
            * Arrivez au stade 1 heure avant le début du match
            * Conservez vos tickets jusqu'à la fin du match
            
            Merci pour votre confiance et bon match !
            L'équipe Football Tickets
            ";
            
            // Attacher PDF avec vérification
            if (!empty($pdfContent)) {
                $mail->addStringAttachment(
                    $pdfContent, 
                    'tickets_football_commande_' . $orderId . '.pdf',
                    'base64',
                    'application/pdf'
                );
                error_log("[INFO] PDF attaché avec succès pour commande $orderId");
            } else {
                error_log("[ERROR] Impossible d'attacher le PDF vide pour commande $orderId");
                return "Erreur: PDF vide lors de l'attachement";
            }
            
            $sent = $mail->send();
            error_log("[DEBUG] Debug SMTP pour commande $orderId:\n$debugOutput");
            
            if ($sent) {
                error_log("[SUCCESS] Email envoyé avec succès pour commande $orderId");
                return true;
            } else {
                error_log("[ERROR] Échec envoi email pour commande $orderId: " . $mail->ErrorInfo);
                return "Erreur envoi email: " . $mail->ErrorInfo;
            }
            
        } catch (Exception $e) {
            error_log("[ERROR] Exception lors de l'envoi email pour commande $orderId: " . $e->getMessage() . "\nDebug SMTP:\n$debugOutput");
            return "Exception lors de l'envoi: " . $e->getMessage();
        }
        
    } catch (Exception $e) {
        error_log("[CRITICAL] Erreur globale envoi email pour commande $orderId: " . $e->getMessage());
        return "Erreur globale: " . $e->getMessage();
    }
} 