<?php
// Configuration PayPal
define('PAYPAL_CLIENT_ID', 'AZLGhKHPVf-kWwE_qKjYUphMv1-iWtDXGx_k-ZuMgPL3XzQqQEwK94-higRDW6go-gkNZA7kFGOHNWTx');
define('PAYPAL_CLIENT_SECRET', 'EHuO9WbXHGhqy_JDVbKBqWiVDxHoJWVxZGtxwjfAxsUJpGaRz8AZD1jWsMQa1ZOsqZQQQHQQcQZwGkPv');
define('PAYPAL_CURRENCY', 'MAD');
define('PAYPAL_MODE', 'sandbox'); // sandbox ou live

// URLs PayPal
define('PAYPAL_SANDBOX_API_URL', 'https://api-m.sandbox.paypal.com');
define('PAYPAL_LIVE_API_URL', 'https://api-m.paypal.com');

// URLs de retour
define('PAYPAL_SUCCESS_URL', BASE_URL . 'payment-success.php');
define('PAYPAL_CANCEL_URL', BASE_URL . 'payment-cancel.php');
?> 