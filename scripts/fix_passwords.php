<?php
require_once(__DIR__ . '/../config/database.php');

/**
 * Script to check and fix password hashing in the database
 * IMPORTANT: Run this script only once and with caution!
 */

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all users
    $stmt = $db->query('SELECT id, email, password FROM users');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed = 0;
    $already_hashed = 0;
    $errors = 0;
    
    foreach ($users as $user) {
        // Check if password is already hashed with bcrypt
        $info = password_get_info($user['password']);
        
        if ($info['algoName'] === 'bcrypt') {
            error_log("Password already properly hashed for user: " . $user['email']);
            $already_hashed++;
            continue;
        }
        
        try {
            // Hash the plain password
            $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT, ['cost' => 12]);
            
            // Update the user's password
            $updateStmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $updateStmt->execute([$hashedPassword, $user['id']]);
            
            error_log("Fixed password hash for user: " . $user['email']);
            $fixed++;
            
        } catch (Exception $e) {
            error_log("Error fixing password for user " . $user['email'] . ": " . $e->getMessage());
            $errors++;
        }
    }
    
    echo "Password fix complete!\n";
    echo "Already properly hashed: $already_hashed\n";
    echo "Fixed: $fixed\n";
    echo "Errors: $errors\n";
    
} catch (Exception $e) {
    error_log("Script error: " . $e->getMessage());
    echo "An error occurred. Check the error log for details.\n";
} 