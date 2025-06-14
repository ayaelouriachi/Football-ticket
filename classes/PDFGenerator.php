<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use TCPDF;

class PDFGenerator {
    private $pdf;
    
    public function __construct() {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuration du document
        $this->pdf->SetCreator('Football Tickets');
        $this->pdf->SetAuthor('Football Tickets');
        $this->pdf->SetTitle('Billet de match');
        
        // Configuration de la page
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetAutoPageBreak(true, 15);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        
        // Police par défaut
        $this->pdf->SetFont('dejavusans', '', 10);
    }
    
    public function generateTicket($orderDetails) {
        // Ajouter une page
        $this->pdf->AddPage();
        
        // En-tête
        $this->pdf->SetFont('dejavusans', 'B', 20);
        $this->pdf->Cell(0, 10, 'BILLET DE MATCH', 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Informations du match
        $this->pdf->SetFont('dejavusans', 'B', 14);
        $this->pdf->Cell(0, 10, $orderDetails['match_title'], 0, 1, 'C');
        
        $this->pdf->SetFont('dejavusans', '', 12);
        $this->pdf->Cell(0, 10, 'Date: ' . date('d/m/Y H:i', strtotime($orderDetails['match_date'])), 0, 1, 'C');
        $this->pdf->Cell(0, 10, 'Stade: ' . $orderDetails['stadium_name'], 0, 1, 'C');
        $this->pdf->Ln(10);
        
        // Détails du billet
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'DÉTAILS DU BILLET', 0, 1, 'L');
        
        $this->pdf->SetFont('dejavusans', '', 12);
        $this->pdf->Cell(0, 10, 'Catégorie: ' . $orderDetails['ticket_category'], 0, 1, 'L');
        $this->pdf->Cell(0, 10, 'Quantité: ' . $orderDetails['quantity'], 0, 1, 'L');
        $this->pdf->Cell(0, 10, 'Prix total: ' . number_format($orderDetails['total_amount'], 2) . ' MAD', 0, 1, 'L');
        $this->pdf->Ln(10);
        
        // Numéro de commande et QR Code
        $this->pdf->SetFont('dejavusans', 'B', 12);
        $this->pdf->Cell(0, 10, 'N° de commande: ' . $orderDetails['order_id'], 0, 1, 'L');
        
        // Générer et ajouter le QR Code
        $style = [
            'border' => true,
            'padding' => 2,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => [255, 255, 255]
        ];
        
        $qrData = json_encode([
            'order_id' => $orderDetails['order_id'],
            'match' => $orderDetails['match_title'],
            'date' => $orderDetails['match_date'],
            'quantity' => $orderDetails['quantity']
        ]);
        
        $this->pdf->write2DBarcode($qrData, 'QRCODE,H', 15, $this->pdf->GetY() + 10, 50, 50, $style);
        
        // Sauvegarder le PDF
        $pdfPath = dirname(__DIR__) . '/tickets/ticket_' . $orderDetails['order_id'] . '.pdf';
        
        // Créer le dossier tickets s'il n'existe pas
        if (!file_exists(dirname($pdfPath))) {
            mkdir(dirname($pdfPath), 0777, true);
        }
        
        $this->pdf->Output($pdfPath, 'F');
        
        return $pdfPath;
    }
} 