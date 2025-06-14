<?php
// Inclure le fichier d'authentification principal s'il n'est pas déjà inclus
require_once __DIR__ . '/../../includes/auth.php';

// Fonction pour journaliser les actions administratives
function logAdminAction($action, $details = '') {
    global $db;
    
    try {
        // Créer la table si elle n'existe pas
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

        // Logger l'action
        $stmt = $db->prepare("
            INSERT INTO admin_logs (admin_id, action, details, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $action,
            $details,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Erreur de logging admin: " . $e->getMessage());
        return false;
    }
}

// La fonction requireAdmin() est maintenant importée depuis includes/auth.php

// Fonction pour obtenir les statistiques du tableau de bord
function getDashboardStats() {
    global $db;
    
    try {
        // Statistiques des matchs
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_matches,
                COUNT(CASE WHEN date > NOW() THEN 1 END) as upcoming_matches,
                COUNT(CASE WHEN date <= NOW() THEN 1 END) as past_matches
            FROM matches
        ");
        $match_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Statistiques des commandes
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total_orders,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue
            FROM orders
        ");
        $order_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Statistiques des utilisateurs
        $stmt = $db->query("
            SELECT COUNT(*) as total_users
            FROM users
            WHERE is_admin = 0
        ");
        $user_stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'matches' => $match_stats,
            'orders' => $order_stats,
            'users' => $user_stats
        ];
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Fonction pour obtenir les derniers matchs
function getRecentMatches($limit = 5) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT m.*, 
                   COUNT(t.id) as tickets_sold,
                   (SELECT COUNT(*) FROM tickets WHERE match_id = m.id) as total_tickets
            FROM matches m
            LEFT JOIN tickets t ON m.id = t.match_id AND t.status = 'paid'
            GROUP BY m.id
            ORDER BY m.date DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

// Fonction pour obtenir les dernières commandes
function getRecentOrders($limit = 5) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT o.*,
                   CONCAT(u.firstname, ' ', u.lastname) as customer_name,
                   COUNT(t.id) as total_tickets
            FROM orders o
            JOIN users u ON o.user_id = u.id
            LEFT JOIN tickets t ON o.id = t.order_id
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

// Fonction pour vérifier si un utilisateur a accès à une ressource
function checkAdminAccess($resource, $action = 'view') {
    // Pour l'instant, tous les administrateurs ont accès à tout
    // À l'avenir, on pourra implémenter un système de permissions plus fin
    return isAdmin();
}

// Fonction pour formater le statut d'une commande
function formatOrderStatus($status) {
    $statusClasses = [
        'pending' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger',
        'refunded' => 'secondary'
    ];
    
    $statusClass = $statusClasses[$status] ?? 'secondary';
    
    return [
        'class' => $statusClass,
        'label' => ucfirst($status)
    ];
}

// Fonction pour obtenir les statistiques d'un match
function getMatchStats($matchId) {
    global $db;
    
    try {
        $stmt = $db->prepare("
            SELECT 
                COUNT(t.id) as tickets_sold,
                SUM(t.price) as total_revenue,
                (
                    SELECT COUNT(*)
                    FROM tickets
                    WHERE match_id = ?
                ) as total_tickets
            FROM tickets t
            WHERE t.match_id = ? AND t.status = 'paid'
        ");
        $stmt->execute([$matchId, $matchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return false;
    }
} 