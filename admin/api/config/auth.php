<?php
/**
 * Authentication Configuration and JWT Handling
 */

class Auth {
    private static $secretKey = 'your-secret-key-here'; // Change in production
    private static $tokenExpiration = 7200; // 2 hours in seconds
    
    /**
     * Generate a JWT token
     */
    public static function generateToken(array $payload): string {
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        
        $payload['exp'] = time() + self::$tokenExpiration;
        $payload['iat'] = time();
        $payload = json_encode($payload);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secretKey, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    /**
     * Verify and decode a JWT token
     */
    public static function verifyToken(string $token): ?array {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) != 3) {
            return null;
        }
        
        [$base64UrlHeader, $base64UrlPayload, $base64UrlSignature] = $tokenParts;
        
        $signature = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlSignature));
        $expectedSignature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, self::$secretKey, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            return null;
        }
        
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $base64UrlPayload)), true);
        
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Get bearer token from Authorization header
     */
    public static function getBearerToken(): ?string {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Check if request is authenticated
     */
    public static function isAuthenticated(): bool {
        $token = self::getBearerToken();
        if (!$token) {
            return false;
        }
        
        $payload = self::verifyToken($token);
        return $payload !== null;
    }
    
    /**
     * Get authenticated admin ID
     */
    public static function getAdminId(): ?int {
        $token = self::getBearerToken();
        if (!$token) {
            return null;
        }
        
        $payload = self::verifyToken($token);
        return $payload['admin_id'] ?? null;
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
} 