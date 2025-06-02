<?php
require_once 'config/database.php';

class Order {
    private $db;
    private $table = 'orders';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Créer une nouvelle commande
    public function createOrder($userId, $cartItems) {
        try {
            $this->db->beginTransaction();
            
            // Calculer le total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['subtotal'];
            }
            
            // Créer la commande
            $sql = "INSERT INTO {$this->table} (user_id, total_amount, status, created_at) 
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$userId, $total, ORDER_STATUS_PENDING]);
            
            if (!$result) {
                $this->db->rollback();
                return ['success' => false, 'message' => 'Erreur lors de la création de la commande'];
            }
            
            $orderId = $this->db->lastInsertId();
            
            // Ajouter les articles à la commande
            foreach ($cartItems as $item) {
                $sql = "INSERT INTO order_items (order_id, tc_id, quantity, unit_price, total_price) 
                        VALUES (?, ?, ?, ?, ?)";
                
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $orderId,
                    $item['tc_id'],
                    $item['quantity'],
                    $item['price'],
                    $item['subtotal']
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'total' => $total
            ];
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Create order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique lors de la création de la commande'];
        }
    }
    
    // Mettre à jour le statut d'une commande
    public function updateOrderStatus($orderId, $status, $paymentData = null) {
        try {
            $this->db->beginTransaction();
            
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW()";
            $params = [$status];
            
            if ($paymentData) {
                $sql .= ", payment_method = ?, transaction_id = ?, paid_at = NOW()";
                $params[] = 'paypal';
                $params[] = $paymentData['transaction_id'];
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $orderId;
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result && $status === ORDER_STATUS_PAID) {
                // Décrémenter le stock des billets
                $this->decrementTicketStock($orderId);
            }
            
            $this->db->commit();
            return $result;
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }
    
    // Obtenir une commande par ID
    public function getOrderById($orderId) {
        try {
            $sql = "SELECT o.*, u.first_name, u.last_name, u.email
                    FROM {$this->table} o
                    JOIN users u ON o.user_id = u.id
                    WHERE o.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if ($order) {
                $order['items'] = $this->getOrderItems($orderId);
            }
            
            return $order;
            
        } catch (PDOException $e) {
            error_log("Get order by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    // Obtenir les articles d'une commande
    public function getOrderItems($orderId) {
        try {
            $sql = "SELECT oi.*, tc.category_name,
                           m.match_date,
                           t1.name as home_team_name, t2.name as away_team_name,
                           s.name as stadium_name
                    FROM order_items oi
                    JOIN ticket_categories tc ON oi.tc_id = tc.id
                    JOIN matches m ON tc.match_id = m.id
                    JOIN teams t1 ON m.home_team_id = t1.id
                    JOIN teams t2 ON m.away_team_id = t2.id
                    JOIN stadiums s ON m.stadium_id = s.id
                    WHERE oi.order_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get order items error: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtenir les commandes d'un utilisateur
    public function getUserOrders($userId, $page = 1, $limit = 10) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit, $offset]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Get user orders error: " . $e->getMessage());
            return [];
        }
    }
    
    // Décrémenter le stock des billets
    private function decrementTicketStock($orderId) {
        try {
            $sql = "UPDATE ticket_categories tc
                    JOIN order_items oi ON tc.id = oi.tc_id
                    SET tc.available_tickets = tc.available_tickets - oi.quantity
                    WHERE oi.order_id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$orderId]);
            
        } catch (PDOException $e) {
            error_log("Decrement ticket stock error: " . $e->getMessage());
            return false;
        }
    }
}
?>