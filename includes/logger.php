<?php
class Logger {
    private static $logFile = __DIR__ . '/../logs/payment.log';
    
    public static function init() {
        $logDir = dirname(self::$logFile);
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public static function log($message, $type = 'INFO', $context = []) {
        self::init();
        
        $logEntry = sprintf(
            "[%s] [%s] %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($type),
            $message,
            !empty($context) ? '- Context: ' . json_encode($context) : ''
        );
        
        error_log($logEntry, 3, self::$logFile);
    }
    
    public static function payment($message, $context = []) {
        self::log($message, 'PAYMENT', $context);
    }
    
    public static function error($message, $context = []) {
        self::log($message, 'ERROR', $context);
    }
    
    public static function debug($message, $context = []) {
        self::log($message, 'DEBUG', $context);
    }
} 