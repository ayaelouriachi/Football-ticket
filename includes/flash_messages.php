<?php
/**
 * Gestion des messages flash pour l'application principale
 */

// Vérifier si la fonction n'existe pas déjà pour éviter les redéclarations
if (!function_exists('setFlashMessage')) {
    /**
     * Définit un message flash
     * @param string $type Type de message (success, error, warning, info)
     * @param string $message Le message à afficher
     */
    function setFlashMessage($type, $message) {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('getFlashMessages')) {
    /**
     * Récupère tous les messages flash et les supprime de la session
     * @return array Messages flash
     */
    function getFlashMessages() {
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
}

if (!function_exists('hasFlashMessages')) {
    /**
     * Vérifie s'il y a des messages flash
     * @return bool
     */
    function hasFlashMessages() {
        return !empty($_SESSION['flash_messages']);
    }
}

if (!function_exists('displayFlashMessages')) {
    /**
     * Affiche les messages flash avec le style Bootstrap
     */
    function displayFlashMessages() {
        $messages = getFlashMessages();
        if (!empty($messages)) {
            foreach ($messages as $msg) {
                $alertClass = match($msg['type']) {
                    'success' => 'alert-success',
                    'error' => 'alert-danger',
                    'warning' => 'alert-warning',
                    'info' => 'alert-info',
                    default => 'alert-secondary'
                };
                echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>";
                echo htmlspecialchars($msg['message']);
                echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                echo "</div>";
            }
        }
    }
}
?> 