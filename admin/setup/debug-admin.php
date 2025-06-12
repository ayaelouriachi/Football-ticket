<?php
require_once(__DIR__ . '/../includes/config.php');

try {
    // Récupérer les informations de l'admin
    $stmt = $adminDb->prepare("
        SELECT id, email, password_hash, first_name, last_name, role, status 
        FROM admin_users 
        WHERE email = ?
    ");
    $stmt->execute(['admin@ticketfoot.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h2>Informations de l'utilisateur admin</h2>";
    
    if ($user) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3>Utilisateur trouvé :</h3>";
        echo "<p><strong>ID:</strong> " . htmlspecialchars($user['id']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($user['email']) . "</p>";
        echo "<p><strong>Nom:</strong> " . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "</p>";
        echo "<p><strong>Rôle:</strong> " . htmlspecialchars($user['role']) . "</p>";
        echo "<p><strong>Statut:</strong> " . htmlspecialchars($user['status']) . "</p>";
        
        // Test du mot de passe
        $testPassword = 'admin123';
        $passwordValid = password_verify($testPassword, $user['password_hash']);
        
        echo "<h3>Test du mot de passe :</h3>";
        echo "<p><strong>Mot de passe testé:</strong> " . $testPassword . "</p>";
        echo "<p><strong>Hash stocké:</strong> " . htmlspecialchars($user['password_hash']) . "</p>";
        echo "<p><strong>Vérification:</strong> " . ($passwordValid ? "<span style='color: green;'>Succès</span>" : "<span style='color: red;'>Échec</span>") . "</p>";
        
        if (!$passwordValid) {
            echo "<div style='margin-top: 20px;'>";
            echo "<a href='reset-admin.php' style='display: inline-block; background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Réinitialiser le mot de passe</a>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 4px; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24;'>Utilisateur non trouvé !</h3>";
        echo "<p>L'utilisateur admin@ticketfoot.com n'existe pas dans la base de données.</p>";
        echo "<div style='margin-top: 20px;'>";
        echo "<a href='create-admin.php' style='display: inline-block; background: #28a745; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Créer l'utilisateur admin</a>";
        echo "</div>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='max-width: 600px; margin: 50px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
    echo "<h2 style='color: #dc3545;'>Erreur</h2>";
    echo "<p>Une erreur est survenue : " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?> 