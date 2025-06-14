<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Inclure le fichier d'initialisation
    require_once __DIR__ . '/init.php';
    
    echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px;'>";
    echo "<h2>Configuration de l'administration</h2>";

    // 1. Vérifier la connexion à la base de données
    echo "<h3>1. Vérification de la connexion à la base de données</h3>";
    try {
        $db->query("SELECT 1");
        echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    } catch (PDOException $e) {
        throw new Exception("Erreur de connexion à la base de données : " . $e->getMessage());
    }

    // 2. Ajouter le champ role à la table users
    echo "<h3>2. Mise à jour de la table users</h3>";
    try {
        $db->exec("
            ALTER TABLE users 
            ADD COLUMN IF NOT EXISTS role ENUM('user', 'admin') DEFAULT 'user'
        ");
        echo "<p style='color: green;'>✅ Champ 'role' ajouté à la table users</p>";
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la modification de la table users : " . $e->getMessage());
    }

    // 3. Créer la table admin_logs
    echo "<h3>3. Création de la table admin_logs</h3>";
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES users(id)
            )
        ");
        echo "<p style='color: green;'>✅ Table admin_logs créée</p>";
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la création de la table admin_logs : " . $e->getMessage());
    }

    // 4. Créer le compte administrateur
    echo "<h3>4. Création du compte administrateur</h3>";
    $admin_email = 'admin@example.com';
    $admin_password = 'admin123';
    $admin_name = 'Administrateur';

    try {
        // Vérifier si l'admin existe déjà
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$admin_email]);
        
        if (!$stmt->fetch()) {
            // Créer l'administrateur
            $stmt = $db->prepare("
                INSERT INTO users (name, email, password, role) 
                VALUES (?, ?, ?, 'admin')
            ");
            
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt->execute([$admin_name, $admin_email, $hashed_password]);
            
            echo "<p style='color: green;'>✅ Compte administrateur créé avec succès!</p>";
            echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Identifiants de connexion :</strong><br>";
            echo "Email: " . htmlspecialchars($admin_email) . "<br>";
            echo "Mot de passe: " . htmlspecialchars($admin_password);
            echo "</div>";
        } else {
            echo "<p style='color: blue;'>ℹ️ Un compte administrateur existe déjà</p>";
        }
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la création du compte admin : " . $e->getMessage());
    }

    echo "<div style='margin-top: 20px;'>";
    echo "<a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Se connecter à l'administration</a>";
    echo "</div>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='color: red; margin: 20px; padding: 20px; border: 1px solid red; border-radius: 5px;'>";
    echo "<h3>❌ Erreur lors de la configuration :</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Trace :</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
} 