<?php
require_once '../config/database.php';

try {
    // Informations de l'administrateur
    $admin_email = 'admin@example.com';
    $admin_password = 'admin123'; // À changer en production !
    $admin_name = 'Administrateur';

    // Vérifier si l'admin existe déjà
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$admin_email]);
    if ($stmt->fetch()) {
        die("Un administrateur existe déjà avec cet email.");
    }

    // Créer l'administrateur
    $stmt = $db->prepare("
        INSERT INTO users (name, email, password, role) 
        VALUES (?, ?, ?, ?)
    ");
    
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    $stmt->execute([
        $admin_name,
        $admin_email,
        $hashed_password,
        'admin'
    ]);

    echo "✅ Compte administrateur créé avec succès!\n";
    echo "Email: " . $admin_email . "\n";
    echo "Mot de passe: " . $admin_password . "\n";
    echo "Vous pouvez maintenant vous connecter à l'interface d'administration.";

} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage());
} 