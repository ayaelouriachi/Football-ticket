<?php
// Démarrer la session et nettoyer la sortie
ob_start();
session_start();

// Inclure les bibliothèques depuis le dossier vendor
require_once __DIR__ . '/vendor/autoload.php';

// Récupérer les données POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    ob_end_clean();
    http_response_code(400);
    echo 'Données manquantes ou invalides.';
    exit;
}

// Variables attendues
$logo = __DIR__ . '/assets/logo.png'; // Mettez le chemin de votre logo
$match = $data['match'] ?? [];
$reservation = $data['reservation'] ?? [];
$paypal = $data['paypal'] ?? [];
$reference = strtoupper(uniqid('TKT-'));

// Générer le QR code
$qrContent = "Réf: $reference\nTransaction: " . ($paypal['id'] ?? '');
$qr = \Endroid\QrCode\QrCode::create($qrContent)->setSize(120);
$writer = new \Endroid\QrCode\Writer\PngWriter();
$qrResult = $writer->write($qr);
$qrTemp = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
file_put_contents($qrTemp, $qrResult->getString());

// Création du PDF
$pdf = new \TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
$pdf->SetCreator('Football Tickets');
$pdf->SetAuthor('Football Tickets');
$pdf->SetTitle('Ticket de Match');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Logo et date
if (file_exists($logo)) {
    $pdf->Image($logo, 10, 10, 30);
}
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(120, 10);
$pdf->Cell(0, 10, 'Date de génération : ' . date('d/m/Y H:i'), 0, 1, 'R');

// Titre
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Ln(10);
$pdf->Cell(0, 12, 'TICKET DE MATCH', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 0, '========================', 0, 1, 'C');
$pdf->Ln(4);

// Détails du match
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 8, 
    "Match : " . ($match['teamA'] ?? '') . " vs " . ($match['teamB'] ?? '') . "\n" .
    "Date  : " . ($match['date'] ?? '') . "\n" .
    "Heure : " . ($match['heure'] ?? '') . "\n" .
    "Stade : " . ($match['stadium'] ?? ''), 0, 'L');
$pdf->Ln(2);

// Détails réservation
$pdf->MultiCell(0, 8,
    "Nombre de places : " . ($reservation['quantite'] ?? '') . "\n" .
    "Section : " . ($reservation['section'] ?? '') . "\n" .
    "Prix total : " . ($reservation['prix'] ?? '') . " €", 0, 'L');
$pdf->Ln(2);

// Transaction et référence
$pdf->MultiCell(0, 8,
    "Transaction PayPal : " . ($paypal['id'] ?? '') . "\n" .
    "Référence : $reference", 0, 'L');
$pdf->Ln(2);

// QR Code
$pdf->Image($qrTemp, 75, $pdf->GetY(), 40, 40, 'PNG');
$pdf->Ln(45);

// Conditions
$pdf->SetFont('helvetica', 'I', 9);
$pdf->MultiCell(0, 8, "Conditions d'entrée et informations pratiques :\n- Présentez ce ticket à l'entrée.\n- Le QR code sera scanné pour validation.\n- Toute reproduction est interdite.\n- Plus d'infos sur notre site.", 0, 'C');

// Nettoyage du QR temporaire
@unlink($qrTemp);

// Nettoyer la sortie et envoyer le PDF
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="ticket.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
echo $pdf->Output('', 'S');
exit;
?> 