<?php
/**
 * URL Helper Functions
 */

/**
 * Get the full URL for a path
 * @param string $path The path relative to BASE_URL
 * @return string The complete URL
 */
function url($path = '') {
    $path = ltrim($path, '/');
    return BASE_URL . $path;
}

/**
 * Get the full admin URL for a path
 * @param string $path The path relative to ADMIN_URL
 * @return string The complete admin URL
 */
function admin_url($path = '') {
    $path = ltrim($path, '/');
    return ADMIN_URL . $path;
}

/**
 * Get the full assets URL for a path
 * @param string $path The path relative to ASSETS_URL
 * @return string The complete assets URL
 */
function assets_url($path = '') {
    $path = ltrim($path, '/');
    return ASSETS_URL . $path;
}

/**
 * Get the current URL
 * @return string The current URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if the current URL matches a pattern
 * @param string $pattern The URL pattern to match
 * @return bool True if the current URL matches the pattern
 */
function is_current_url($pattern) {
    $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    return strpos($current, $pattern) !== false;
}

/**
 * Get the previous URL from the referer
 * @param string $default The default URL if no referer is found
 * @return string The previous URL or default
 */
function previous_url($default = '') {
    return $_SERVER['HTTP_REFERER'] ?? $default ?: BASE_URL;
} 