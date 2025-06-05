<?php
require_once(__DIR__ . '/../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    // Email de l'utilisateur à modifier
    $email = "elouriachi.aya@etu.uae.ac.ma";
    
    // Nouveau mot de passe (à changer lors de la première connexion)
    $newPassword = "123456789";
    
    // Générer un nouveau hash
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Mettre à jour le mot de passe
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ?");
    if ($stmt->execute([$hash, $email])) {
        echo "✅ Mot de passe réinitialisé avec succès pour $email\n";
        echo "Nouveau mot de passe temporaire: $newPassword\n";
        echo "Hash généré: $hash\n";
        
        // Vérifier que le hash fonctionne
        if (password_verify($newPassword, $hash)) {
            echo "✅ Vérification du hash réussie\n";
        } else {
            echo "❌ Erreur de vérification du hash\n";
        }
    } else {
        echo "❌ Erreur lors de la réinitialisation du mot de passe\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
} 