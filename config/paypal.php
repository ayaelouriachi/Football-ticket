<?php
class PayPalConfig {
    private static $config = [
        'sandbox' => [
            'client_id' => 'YOUR_SANDBOX_CLIENT_ID',
            'client_secret' => 'YOUR_SANDBOX_CLIENT_SECRET',
            'mode' => 'sandbox',
            'base_url' => 'https://api.sandbox.paypal.com',
            'web_url' => 'https://www.sandbox.paypal.com'
        ],
        'live' => [
            'client_id' => 'YOUR_LIVE_CLIENT_ID',
            'client_secret' => 'YOUR_LIVE_CLIENT_SECRET',
            'mode' => 'live',
            'base_url' => 'https://api.paypal.com',
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
            'log.LogEnabled' => $environment === 'sandbox',
            'log.FileName' => '../logs/PayPal.log',
            'log.LogLevel' => 'INFO'
        ];
    }
}
?>