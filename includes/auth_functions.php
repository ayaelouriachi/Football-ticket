<?php
/**
 * Fonctions d'authentification
 */

/**
 * Hash un mot de passe
 * @param string $password Le mot de passe à hasher
 * @return string Le hash du mot de passe
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Vérifie un mot de passe
 * @param string $password Le mot de passe à vérifier
 * @param string $hash Le hash à comparer
 * @return bool True si le mot de passe correspond, false sinon
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Génère un token de réinitialisation de mot de passe
 * @return string Le token généré
 */
function generateResetToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Vérifie si un mot de passe est suffisamment fort
 * @param string $password Le mot de passe à vérifier
 * @return bool True si le mot de passe est fort, false sinon
 */
function isStrongPassword($password) {
    // Au moins 8 caractères
    if (strlen($password) < 8) {
        return false;
    }
    
    // Au moins une lettre majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }
    
    // Au moins une lettre minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }
    
    // Au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return false;
    }
    
    return true;
} 