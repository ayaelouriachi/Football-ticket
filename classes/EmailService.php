<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class EmailService {
    private $mailer;
    private $config;
    private $debug = false;
    
    public function __construct($debug = false) {
        $this->config = require dirname(__DIR__) . '/config/email.php';
        $this->debug = $debug;
        
        $this->initializeMailer();
    }
    
    private function initializeMailer() {
        try {
            $this->mailer = new PHPMailer(true);
            
            // Enable debug output if debug mode is on
            if ($this->debug) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = function($str, $level) {
                    error_log("SMTP DEBUG [$level]: $str");
                };
            }
            
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Options SSL supplémentaires pour Gmail
            $this->mailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Configuration de l'expéditeur
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Configuration générale
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
            
            // Timeout plus long pour les connexions lentes
            $this->mailer->Timeout = 60;
            
            if ($this->debug) {
                error_log("✅ Mailer initialized with:");
                error_log("Host: " . $this->config['smtp_host']);
                error_log("Port: " . $this->config['smtp_port']);
                error_log("Username: " . $this->config['smtp_username']);
                error_log("From: " . $this->config['from_email']);
            }
            
        } catch (Exception $e) {
            error_log("❌ ERREUR initialisation mailer: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function sendTicketConfirmation($to, $subject, $ticketPdfPath, $orderDetails) {
        try {
            // Log de début
            error_log("🔄 Début de l'envoi d'email à: " . $to);
            error_log("📎 Fichier PDF: " . $ticketPdfPath);
            
            // Vérification du fichier PDF
            if (!file_exists($ticketPdfPath)) {
                error_log("❌ ERREUR: Le fichier PDF n'existe pas: " . $ticketPdfPath);
                throw new Exception("Le fichier PDF du billet n'existe pas: " . $ticketPdfPath);
            }
            
            // Nettoyage des anciennes données
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // Destinataire
            $this->mailer->addAddress($to);
            error_log("✉️ Destinataire ajouté: " . $to);
            
            // Sujet et corps du message
            $this->mailer->Subject = $subject;
            
            // Corps du message en HTML
            $body = $this->getTicketEmailTemplate($orderDetails);
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '</p>'], "\n", $body));
            error_log("📝 Corps du message généré");
            
            // Pièce jointe (PDF du billet)
            $this->mailer->addAttachment($ticketPdfPath, 'ticket.pdf');
            error_log("📎 PDF attaché: " . $ticketPdfPath);
            
            // Envoi de l'email
            error_log("🚀 Tentative d'envoi de l'email...");
            $result = $this->mailer->send();
            
            // Log du résultat
            if ($result) {
                error_log("✅ Email envoyé avec succès à: " . $to);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("❌ ERREUR lors de l'envoi de l'email: " . $e->getMessage());
            error_log("📋 Détails de l'erreur SMTP: " . $this->mailer->ErrorInfo);
            throw new Exception("Erreur lors de l'envoi de l'email: " . $e->getMessage());
        } finally {
            // Nettoyage
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
        }
    }
    
    private function getTicketEmailTemplate($orderDetails) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .details { margin: 20px 0; padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Confirmation de votre commande</h2>
                </div>
                
                <p>Bonjour,</p>
                
                <p>Nous vous remercions pour votre commande. Voici les détails :</p>
                
                <div class="details">
                    <p><strong>Numéro de commande :</strong> <?php echo htmlspecialchars($orderDetails['order_id']); ?></p>
                    <p><strong>Match :</strong> <?php echo htmlspecialchars($orderDetails['match_title']); ?></p>
                    <p><strong>Date :</strong> <?php echo htmlspecialchars($orderDetails['match_date']); ?></p>
                    <p><strong>Stade :</strong> <?php echo htmlspecialchars($orderDetails['stadium']); ?></p>
                    <p><strong>Catégorie :</strong> <?php echo htmlspecialchars($orderDetails['ticket_category']); ?></p>
                    <p><strong>Quantité :</strong> <?php echo htmlspecialchars($orderDetails['quantity']); ?></p>
                    <p><strong>Total :</strong> <?php echo number_format($orderDetails['total'], 2); ?> MAD</p>
                </div>
                
                <p>Vous trouverez ci-joint votre/vos billet(s) au format PDF.</p>
                
                <p>Pour toute question, n'hésitez pas à nous contacter.</p>
                
                <div class="footer">
                    <p>Ceci est un email automatique, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
} 