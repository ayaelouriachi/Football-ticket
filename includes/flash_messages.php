<?php
/**
 * Gestion des messages flash dans la session
 */

/**
 * Définit un message flash dans la session
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

/**
 * Récupère et supprime tous les messages flash
 * @return array Messages flash
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Vérifie s'il y a des messages flash
 * @return bool
 */
function hasFlashMessages() {
    return !empty($_SESSION['flash_messages']);
}

/**
 * Affiche les messages flash avec le style Bootstrap
 */
function displayFlashMessages() {
    $messages = getFlashMessages();
    foreach ($messages as $msg) {
        $type = $msg['type'];
        // Convertir le type en classe Bootstrap appropriée
        $class = match($type) {
            'error' => 'danger',
            default => $type
        };
        echo "<div class='alert alert-{$class} alert-dismissible fade show' role='alert'>";
        echo htmlspecialchars($msg['message']);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
        echo "</div>";
    }
} 