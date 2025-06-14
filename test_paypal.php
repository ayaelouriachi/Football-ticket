<?php
header('Content-Type: application/json');

$paypalUrl = 'https://www.paypal.com/sdk/js?client-id=AV5aJZBd9Td8kh3eRla5My1LjUPZBNfkiu3QOHDKzb2iFQiDfK1UTQ6X2FFntD7LAZHWcK90NaGhA8Kn';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paypalUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo json_encode([
    'status' => $httpCode,
    'accessible' => ($httpCode == 200),
    'error' => $error,
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 