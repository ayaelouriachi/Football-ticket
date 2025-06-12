<?php
/**
 * Admin Authentication Functions
 */

// Ensure session is initialized
require_once(__DIR__ . '/../../config/session.php');
SessionManager::init();

// Include database
require_once(__DIR__ . '/../../config/database.php');

// Constants
define('ADMIN_SESSION_DURATION', 7200); // 2 hours in seconds
define('ADMIN_SESSION_NAME', 'admin_session');
define('CSRF_TOKEN_NAME', 'admin_csrf_token');

/**
 * Get current admin user data
 * @return array|null Admin user data if logged in, null otherwise
 */
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }

    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching admin user: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if an admin user is logged in
 * @return bool
 */
function isAdminLoggedIn(): bool {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_last_activity'])) {
        return false;
    }

    // Check session expiration
    if (time() - $_SESSION['admin_last_activity'] > ADMIN_SESSION_DURATION) {
        logoutAdmin();
        return false;
    }

    // Update last activity time
    $_SESSION['admin_last_activity'] = time();
    return true;
}

/**
 * Authenticate admin user
 * @param string $email
 * @param string $password
 * @return bool
 */
function loginAdmin(string $email, string $password): bool {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE email = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password'])) {
            // Set session variables
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_last_activity'] = time();
            $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

            // Log successful login
            logAdminActivity($admin['id'], 'login', 'Successfully logged in');
            return true;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log out admin user
 */
function logoutAdmin(): void {
    if (isset($_SESSION['admin_id'])) {
        // Log logout activity before destroying session
        logAdminActivity($_SESSION['admin_id'], 'logout', 'Successfully logged out');
    }

    // Destroy session
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

/**
 * Check if admin has required permission
 * @param string $permission
 * @return bool
 */
function checkAdminPermission(string $permission): bool {
    if (!isAdminLoggedIn()) {
        return false;
    }

    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            SELECT permissions 
            FROM admin_users 
            WHERE id = ? AND status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            return false;
        }

        $permissions = json_decode($admin['permissions'], true);
        return is_array($permissions) && in_array($permission, $permissions);
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['admin_csrf_token']) && 
           hash_equals($_SESSION['admin_csrf_token'], $token);
}

/**
 * Get CSRF token
 * @return string
 */
function getCSRFToken(): string {
    if (!isset($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

/**
 * Log admin activity
 * @param int $adminId
 * @param string $action
 * @param string $details
 * @return bool
 */
function logAdminActivity(int $adminId, string $action, string $details): bool {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
            INSERT INTO admin_activity_logs (admin_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $adminId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Set flash message for admin panel
 * @param string $type success|error|warning|info
 * @param string $message
 */
function setAdminFlashMessage(string $type, string $message): void {
    $_SESSION['admin_flash_type'] = $type;
    $_SESSION['admin_flash_message'] = $message;
}

/**
 * Require admin authentication
 * If not logged in, redirect to login page
 */
function requireAdminAuth(): void {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Hash admin password
 * @param string $password
 * @return string
 */
function hashAdminPassword(string $password): string {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Validate admin email
 * @param string $email
 * @return bool
 */
function validateAdminEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate admin password
 * @param string $password
 * @return bool
 */
function validateAdminPassword(string $password): bool {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password);
} 