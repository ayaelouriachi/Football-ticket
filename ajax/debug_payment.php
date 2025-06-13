<?php
require_once(__DIR__ . '/../includes/json_response.php');
require_once(__DIR__ . '/../includes/logger.php');

// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Nettoyer tout output précédent
while (ob_get_level()) {
    ob_end_clean();
}

try {
    // Collecter les informations de debug
    $debugInfo = [
        'request' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query_string' => $_SERVER['QUERY_STRING'] ?? '',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'headers' => getallheaders(),
        ],
        'input' => [
            'raw' => file_get_contents('php://input'),
            'post' => $_POST,
            'get' => $_GET,
        ],
        'session' => [
            'id' => session_id(),
            'order_id' => $_SESSION['order_id'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
        ],
        'server' => [
            'software' => $_SERVER['SERVER_SOFTWARE'],
            'protocol' => $_SERVER['SERVER_PROTOCOL'],
            'document_root' => $_SERVER['DOCUMENT_ROOT'],
            'script_filename' => $_SERVER['SCRIPT_FILENAME'],
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    // Logger les informations
    Logger::debug('Debug payment info', $debugInfo);

    // Envoyer la réponse
    sendJsonResponse(true, $debugInfo, 'Informations de debug');

} catch (Exception $e) {
    Logger::error('Erreur de debug', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);

    sendJsonResponse(false, null, 'Erreur lors de la récupération des informations de debug');
} 