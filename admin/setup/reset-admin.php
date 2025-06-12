<?php
require_once(__DIR__ . '/../includes/config.php');

try {
    // Générer le nouveau hash
    $password = 'admin123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Vérifier que le hash fonctionne avant de l'enregistrer
    if (!password_verify($password, $hash)) {
        throw new Exception("Erreur lors de la génération du hash");
    }
    
    // Mettre à jour le mot de passe de l'admin
    $stmt = $adminDb->prepare("
        UPDATE admin_users 
        SET password_hash = ?, 
            status = 'active',
            role = 'super_admin'
        WHERE email = ?
    ");
    
    $result = $stmt->execute([
        $hash,
        'admin@ticketfoot.com'
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Vérifier que la mise à jour a fonctionné
        $stmt = $adminDb->prepare("SELECT password_hash FROM admin_users WHERE email = ?");
        $stmt->execute(['admin@ticketfoot.com']);
        $storedHash = $stmt->fetchColumn();
        
        if (password_verify($password, $storedHash)) {
            echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
            echo "<h2 style='color: #28a745;'>Mot de passe réinitialisé avec succès !</h2>";
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
            echo "<p><strong>Email :</strong> admin@ticketfoot.com</p>";
            echo "<p><strong>Nouveau mot de passe :</strong> admin123</p>";
            echo "<p><strong>Hash généré :</strong> " . htmlspecialchars($hash) . "</p>";
            echo "<p><strong>Vérification :</strong> <span style='color: green;'>Succès</span></p>";
            echo "</div>";
            echo "<p style='color: #dc3545;'><strong>Important :</strong> Changez ce mot de passe dès votre première connexion !</p>";
            echo "<div style='margin-top: 20px;'>";
            echo "<a href='../login.php' style='display: inline-block; background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Se connecter</a>";
            echo "<a href='debug-admin.php' style='display: inline-block; background: #6c757d; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Vérifier les informations</a>";
            echo "</div>";
            echo "</div>";
        } else {
            throw new Exception("La vérification du nouveau mot de passe a échoué");
        }
    } else {
        echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
        echo "<h2 style='color: #dc3545;'>Erreur</h2>";
        echo "<p>L'utilisateur admin@ticketfoot.com n'existe pas dans la base de données.</p>";
        echo "<a href='create-admin.php' style='display: inline-block; background: #28a745; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Créer l'utilisateur admin</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h2 style='color: #dc3545;'>Erreur</h2>";
    echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?> 