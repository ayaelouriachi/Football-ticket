<?php
require_once(__DIR__ . '/../includes/config.php');

try {
    // Vérifier si l'admin existe déjà
    $stmt = $adminDb->prepare("SELECT COUNT(*) FROM admin_users WHERE email = ?");
    $stmt->execute(['admin@ticketfoot.com']);
    
    if ($stmt->fetchColumn() > 0) {
        echo "L'utilisateur admin existe déjà.";
        exit;
    }
    
    // Créer l'utilisateur admin
    $stmt = $adminDb->prepare("
        INSERT INTO admin_users 
        (email, password_hash, first_name, last_name, role, status) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        'admin@ticketfoot.com',
        password_hash('admin123', PASSWORD_DEFAULT),
        'Super',
        'Admin',
        'super_admin',
        'active'
    ]);
    
    echo "Utilisateur admin créé avec succès !<br>";
    echo "Email : admin@ticketfoot.com<br>";
    echo "Mot de passe : admin123";
    
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?> 