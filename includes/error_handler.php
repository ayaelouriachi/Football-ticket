<?php

class ErrorHandler {
    private static $logFile = LOGS_PATH . 'app_errors.log';
    
    public static function init() {
        // Configuration des erreurs en fonction de l'environnement
        if (APP_ENV === 'development') {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }
        
        // Définir les gestionnaires d'erreur personnalisés
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleFatalError']);
    }
    
    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        $errorMsg = sprintf(
            "[%s] Error: %s in %s on line %d",
            date('Y-m-d H:i:s'),
            $message,
            $file,
            $line
        );
        
        self::logError($errorMsg);
        
        if (APP_ENV === 'development') {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
            echo "<strong>Error:</strong> $message<br>";
            echo "<strong>File:</strong> $file<br>";
            echo "<strong>Line:</strong> $line";
            echo "</div>";
        }
    }
    
    public static function handleException($exception) {
        $errorMsg = sprintf(
            "[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s",
            date('Y-m-d H:i:s'),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        self::logError($errorMsg);
        
        if (APP_ENV === 'development') {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px;'>";
            echo "<strong>Uncaught Exception:</strong> " . $exception->getMessage() . "<br>";
            echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
            echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
            echo "</div>";
        } else {
            include 'error_pages/500.php';
        }
    }
    
    public static function handleFatalError() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $errorMsg = sprintf(
                "[%s] Fatal Error: %s in %s on line %d",
                date('Y-m-d H:i:s'),
                $error['message'],
                $error['file'],
                $error['line']
            );
            
            self::logError($errorMsg);
            
            if (APP_ENV === 'production') {
                include 'error_pages/500.php';
            }
        }
    }
    
    private static function logError($message) {
        if (!file_exists(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }
        
        error_log($message . PHP_EOL, 3, self::$logFile);
    }
    
    public static function logCustomError($message, $context = []) {
        $logMessage = sprintf(
            "[%s] %s",
            date('Y-m-d H:i:s'),
            $message
        );
        
        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context);
        }
        
        self::logError($logMessage);
    }
}
?>