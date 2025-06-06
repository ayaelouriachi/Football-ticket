<?php
// PayPal Configuration
define('PAYPAL_CLIENT_ID', 'AV5aJZBd9Td8kh3eRla5My1LjUPZBNfkiu3QOHDKzb2iFQiDfK1UTQ6X2FFntD7LAZHWcK90NaGhA8Kn');
define('PAYPAL_CLIENT_SECRET', 'EJGZQkg-PNHEsLx9l2GkEoplkonkyCtsigb0n_WCsImop7AQGX5xzSr8_0P77u6xIGoscRg2vAcIheyM');
define('PAYPAL_CURRENCY', 'USD');
define('PAYPAL_MODE', 'sandbox'); // sandbox or live

// Exchange rate (1 MAD = X USD)
define('MAD_TO_USD_RATE', 0.1); // Approximate exchange rate

// PayPal API URLs with IP addresses
define('PAYPAL_API_URL', PAYPAL_MODE === 'sandbox' 
    ? 'https://api-m.sandbox.paypal.com'
    : 'https://api-m.paypal.com');

// PayPal SDK URL
define('PAYPAL_SDK_URL', 'https://www.paypal.com/sdk/js');

// DNS and SSL Configuration
define('PAYPAL_VERIFY_SSL', true);
define('PAYPAL_CONNECT_TIMEOUT', 30);
define('PAYPAL_TIMEOUT', 30);

// Logging Configuration
define('PAYPAL_LOG_ENABLED', true);
define('PAYPAL_LOG_FILE', __DIR__ . '/../logs/paypal.log');

class PayPalConfig {
    private static $config = [
        'sandbox' => [
            'client_id' => PAYPAL_CLIENT_ID,
            'client_secret' => PAYPAL_CLIENT_SECRET,
            'mode' => 'sandbox',
            'base_url' => PAYPAL_API_URL,
            'web_url' => 'https://www.sandbox.paypal.com'
        ],
        'live' => [
            'client_id' => PAYPAL_CLIENT_ID,
            'client_secret' => PAYPAL_CLIENT_SECRET,
            'mode' => 'live',
            'base_url' => PAYPAL_API_URL,
            'web_url' => 'https://www.paypal.com'
        ]
    ];
    
    public static function getConfig($environment = 'sandbox') {
        return self::$config[$environment] ?? self::$config['sandbox'];
    }
    
    public static function getSDKConfig($environment = 'sandbox') {
        $config = self::getConfig($environment);
        
        return [
            'mode' => $config['mode'],
            'acct1.UserName' => $config['client_id'],
            'acct1.Password' => $config['client_secret'],
            'acct1.Signature' => '',
            'log.LogEnabled' => PAYPAL_LOG_ENABLED,
            'log.FileName' => PAYPAL_LOG_FILE,
            'log.LogLevel' => 'DEBUG',
            'http.ConnectionTimeOut' => PAYPAL_CONNECT_TIMEOUT,
            'http.Retry' => 1,
            'http.ReadTimeOut' => PAYPAL_TIMEOUT,
            'http.CURLOPT_SSL_VERIFYPEER' => PAYPAL_VERIFY_SSL
        ];
    }
}
?>