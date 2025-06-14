<?php
// Test du formatage des prix dans l'admin
require_once 'includes/config.php';
require_once '../includes/functions.php';

$prices = [
    199.99,
    1000.50,
    0.99,
    1234567.89
];

echo "=== Test du formatage des prix (Admin) ===\n\n";

foreach ($prices as $price) {
    echo "Prix original : $price\n";
    echo "Prix formaté (formatPrice) : " . formatPrice($price) . "\n";
    echo "Prix formaté (adminFormatPrice) : " . adminFormatPrice($price) . "\n\n";
}

echo "Test terminé avec succès!\n"; 