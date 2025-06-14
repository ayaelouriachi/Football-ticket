<?php
// Test du formatage des prix
require_once 'includes/functions.php';

$prices = [
    199.99,
    1000.50,
    0.99,
    1234567.89
];

echo "=== Test du formatage des prix ===\n\n";

foreach ($prices as $price) {
    echo "Prix original : $price\n";
    echo "Prix formaté : " . formatPrice($price) . "\n\n";
}

echo "Test terminé avec succès!\n"; 