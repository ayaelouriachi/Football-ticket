<?php
/**
 * Authentication helper functions
 */

/**
 * Check if a user is logged in as admin
 * @return bool
 */
function isAdminLoggedIn() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_token']);
}

/**
 * Check if a user is logged in (regular user)
 * @return bool
 */
function isUserLoggedIn() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Get current logged in user ID
 * @return int|null
 */
function getCurrentUserId() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged in admin ID
 * @return int|null
 */
function getCurrentAdminId() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin token
 * @return string|null
 */
function getCurrentAdminToken() {
    if (!isset($_SESSION)) {
        session_start();
    }
    return $_SESSION['admin_token'] ?? null;
}

/**
 * Verify if a token matches the current admin token
 * @param string $token
 * @return bool
 */
function verifyAdminToken($token) {
    if (!isset($_SESSION)) {
        session_start();
    }
    return isset($_SESSION['admin_token']) && hash_equals($_SESSION['admin_token'], $token);
}

/**
 * Log activity for auditing
 * @param string $action
 * @param string $description
 * @return void
 */
function logAdminActivity($action, $description) {
    try {
        $db = Database::getInstance()->getConnection();
        $adminId = getCurrentAdminId();
        
        if (!$adminId) {
            return;
        }
        
        $sql = "INSERT INTO admin_activity_log (admin_id, action, description, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$adminId, $action, $description]);
    } catch (Exception $e) {
        error_log("Error logging admin activity: " . $e->getMessage());
    }
} 