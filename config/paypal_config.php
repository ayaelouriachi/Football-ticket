<?php
// Prevent multiple definition of constants
if (!defined('PAYPAL_CLIENT_ID')) {
    // PayPal API Configuration
    define('PAYPAL_CLIENT_ID', 'AV5aJZBd9Td8kh3eRla5My1LjUPZBNfkiu3QOHDKzb2iFQiDfK1UTQ6X2FFntD7LAZHWcK90NaGhA8Kn');
    define('PAYPAL_CLIENT_SECRET','EJGZQkg-PNHEsLx9l2GkEoplkonkyCtsigb0n_WCsImop7AQGX5xzSr8_0P77u6xIGoscRg2vAcIheyM');
    
    // Environment Settings
    define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'
    define('PAYPAL_CURRENCY', 'USD');
    
    // API URLs
    define('PAYPAL_API_URL', PAYPAL_MODE === 'sandbox' 
        ? 'https://api-m.sandbox.paypal.com'
        : 'https://api-m.paypal.com');
        
    // Currency conversion (MAD to USD)
    define('MAD_TO_USD_RATE', 0.099); // Update this rate periodically
    
    // Logging settings
    define('PAYPAL_LOG_ENABLED', true);
    define('PAYPAL_LOG_FILE', __DIR__ . '/../logs/paypal.log');
} 