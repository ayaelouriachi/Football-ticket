<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Vérifier l'utilisateur admin
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@example.com']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Utilisateur admin trouvé\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "is_admin: " . ($user['is_admin'] ? 'Oui' : 'Non') . "\n";
        echo "role: " . $user['role'] . "\n";
        
        // Mettre à jour les droits si nécessaire
        if (!$user['is_admin'] || $user['role'] !== 'admin') {
            $stmt = $db->prepare("UPDATE users SET is_admin = 1, role = 'admin' WHERE id = ?");
            $stmt->execute([$user['id']]);
            echo "✅ Droits d'administration mis à jour\n";
        }
    } else {
        echo "❌ Utilisateur admin non trouvé\n";
        
        // Créer l'utilisateur admin
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (firstname, lastname, email, password, is_admin, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['Admin', 'System', 'admin@example.com', $password, 1, 'admin']);
        echo "✅ Utilisateur admin créé\n";
    }
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
} 