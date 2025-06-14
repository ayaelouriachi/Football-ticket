<?php
ob_start();
session_start();

require_once __DIR__ . '/vendor/autoload.php';
require_once 'config/database.php';

// 1. Récupérer l'ID de la commande
$order_id = $_GET['order_id'] ?? null;
if (!$order_id && isset($_SESSION['last_order_id'])) {
    $order_id = $_SESSION['last_order_id'];
}

if (!$order_id) {
    die("Erreur : Numéro de commande non fourni.");
}

// 2. Récupérer les données de la commande depuis la base de données
try {
    $db = Database::getInstance()->getConnection();

    // Requête pour obtenir les détails principaux de la commande et du match
    $stmt = $db->prepare("
        SELECT
            o.id AS order_id,
            o.transaction_id,
            o.created_at AS order_date,
            o.total_amount,
            u.name AS user_name,
            m.match_date,
            t1.name AS team1_name,
            t2.name AS team2_name,
            s.name AS stadium_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
        JOIN matches m ON tc.match_id = m.id
        JOIN teams t1 ON m.team1_id = t1.id
        JOIN teams t2 ON m.team2_id = t2.id
        JOIN stadiums s ON m.stadium_id = s.id
        WHERE o.id = ?
        LIMIT 1
    ");
    $stmt->execute([$order_id]);
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order_details) {
        throw new Exception("Commande introuvable.");
    }

    // Requête pour obtenir tous les articles de la commande
    $items_stmt = $db->prepare("
        SELECT
            oi.quantity,
            oi.price_per_ticket,
            tc.name AS category_name
        FROM order_items oi
        JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
        WHERE oi.order_id = ?
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    ob_end_clean();
    error_log("Erreur lors de la récupération de la commande $order_id: " . $e->getMessage());
    die("Une erreur est survenue lors de la génération de votre ticket. Veuillez contacter le support.");
}

// 3. Générer le PDF
$logo = __DIR__ . '/assets/logo.png';
$reference = $order_details['order_id'];

// Générer le QR code
$qrContent = "Commande: {$order_details['order_id']}\nTransaction: {$order_details['transaction_id']}\nUtilisateur: {$order_details['user_name']}";
$qr = \Endroid\QrCode\QrCode::create($qrContent)->setSize(120);
$writer = new \Endroid\QrCode\Writer\PngWriter();
$qrResult = $writer->write($qr);
$qrTemp = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
file_put_contents($qrTemp, $qrResult->getString());

$pdf = new \TCPDF('P', 'mm', 'A5', true, 'UTF-8', false);
$pdf->SetCreator('Football Tickets');
$pdf->SetAuthor('Football Tickets');
$pdf->SetTitle('Ticket de Match - Commande ' . $reference);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

// Logo et date
if (file_exists($logo)) {
    $pdf->Image($logo, 10, 10, 30);
}
$pdf->SetFont('helvetica', '', 10);
$pdf->SetXY(120, 10);
$pdf->Cell(0, 10, 'Commandé le : ' . date('d/m/Y H:i', strtotime($order_details['order_date'])), 0, 1, 'R');

// Titre
$pdf->SetFont('helvetica', 'B', 18);
$pdf->Ln(10);
$pdf->Cell(0, 12, 'VOS BILLETS DE MATCH', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 0, '========================', 0, 1, 'C');
$pdf->Ln(4);

// Détails du match
$pdf->SetFont('helvetica', '', 12);
$pdf->MultiCell(0, 8,
    "Match : " . htmlspecialchars($order_details['team1_name']) . " vs " . htmlspecialchars($order_details['team2_name']) . "\n" .
    "Date  : " . date('d/m/Y', strtotime($order_details['match_date'])) . "\n" .
    "Heure : " . date('H:i', strtotime($order_details['match_date'])) . "\n" .
    "Stade : " . htmlspecialchars($order_details['stadium_name']), 0, 'L');
$pdf->Ln(2);

// Détails réservation
$items_details = "";
foreach($order_items as $item) {
    $items_details .= " - " . $item['quantity'] . " x " . htmlspecialchars($item['category_name']) . " (" . number_format($item['price_per_ticket'], 2) . " MAD)\n";
}
$pdf->MultiCell(0, 8,
    "Détails de la réservation :\n" . $items_details .
    "Prix total : " . number_format($order_details['total_amount'], 2) . " MAD", 0, 'L');
$pdf->Ln(2);

// Transaction et référence
$pdf->MultiCell(0, 8,
    "Transaction : " . htmlspecialchars($order_details['transaction_id']) . "\n" .
    "Référence Commande : " . $reference, 0, 'L');
$pdf->Ln(2);

// QR Code
$pdf->Image($qrTemp, 75, $pdf->GetY(), 40, 40, 'PNG');
$pdf->Ln(45);

// Conditions
$pdf->SetFont('helvetica', 'I', 9);
$pdf->MultiCell(0, 8, "Conditions: Présentez ce billet à l'entrée. Le QR code sera scanné. Toute reproduction est interdite.", 0, 'C');

@unlink($qrTemp);

// Nettoyer la sortie et envoyer le PDF
ob_end_clean();
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="ticket_' . $reference . '.pdf"');
echo $pdf->Output('', 'S');
exit;
?> 