<?php
/**
 * Fonction utilitaire pour envoyer une réponse JSON standardisée
 */
function sendJsonResponse($success, $data = null, $message = '', $httpCode = 200) {
    // Nettoyer tout output précédent
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Désactiver l'affichage des erreurs
    ini_set('display_errors', 0);
    error_reporting(0);
    
    // Définir les headers
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    http_response_code($httpCode);
    
    // Construire la réponse
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Logger la réponse en cas d'erreur
    if (!$success) {
        error_log(
            sprintf(
                "[%s] JSON Response Error: %s - Data: %s",
                date('Y-m-d H:i:s'),
                $message,
                json_encode($data)
            )
        );
    }
    
    // Envoyer la réponse
    echo json_encode($response);
    exit;
} 