<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../vendor/setasign/fpdf/fpdf.php';

try {
    // Créer un PDF simple
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(40, 10, 'Test PDF Generation');
    
    // Sauvegarder dans un fichier
    $pdf->Output('F', __DIR__ . '/test.pdf');
    
    echo "PDF généré avec succès !\n";
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
} 