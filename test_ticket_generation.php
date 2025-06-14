<?php
// Données de test pour le ticket
$testData = [
    'match' => [
        'teamA' => 'Raja Casablanca',
        'teamB' => 'Wydad Casablanca',
        'date' => '01/01/2024',
        'heure' => '20:00',
        'stadium' => 'Stade Mohammed V'
    ],
    'reservation' => [
        'quantite' => 2,
        'section' => 'Tribune Nord',
        'prix' => 150
    ],
    'paypal' => [
        'id' => 'PAY-' . uniqid()
    ]
];

// Initialiser cURL
$ch = curl_init('http://localhost/football_tickets/generate_ticket_pdf.php');

// Configurer les options cURL
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen(json_encode($testData))
]);

// Exécuter la requête
$response = curl_exec($ch);

// Vérifier s'il y a des erreurs
if(curl_errno($ch)) {
    echo 'Erreur cURL : ' . curl_error($ch);
} else {
    // Vérifier le type de contenu de la réponse
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    
    if(strpos($contentType, 'application/pdf') !== false) {
        // Envoyer les en-têtes pour afficher le PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="ticket.pdf"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Afficher le PDF
        echo $response;
    } else {
        echo "Réponse reçue : " . $response;
    }
}

// Fermer cURL
curl_close($ch);
?> 