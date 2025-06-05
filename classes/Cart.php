<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/session.php';
require_once __DIR__ . '/TicketCategory.php';

class Cart {
    private $db;
    private $table = 'cart_items';
    private $userId;
    private $sessionId;
    private $session;
    
    public function __construct($db, &$session) {
        $this->db = $db;
        $this->session = &$session;
        $this->userId = isset($session['user_id']) ? $session['user_id'] : null;
        $this->sessionId = session_id();
    }
    
    public function addItem($categoryId, $quantity) {
        try {
            // Input validation
            if (!is_numeric($categoryId) || !is_numeric($quantity)) {
                throw new Exception("Invalid input parameters");
            }
            
            if ($quantity < 1 || $quantity > MAX_TICKETS_PER_CATEGORY) {
                throw new Exception("Invalid quantity. Must be between 1 and " . MAX_TICKETS_PER_CATEGORY);
            }

            // Check ticket category
            $ticketCategory = new TicketCategory($this->db);
            $availability = $ticketCategory->checkAvailability($categoryId, $quantity);
            
            if (!$availability || !$availability['available']) {
                throw new Exception("Not enough tickets available");
            }
            
            // Check match date
            if (strtotime($availability['match_date']) < time()) {
                throw new Exception("This match has already taken place");
            }
            
            // Begin transaction
            $this->db->beginTransaction();
            
            // Check if item already exists in cart
            $stmt = $this->db->prepare("
                SELECT id, quantity 
                FROM {$this->table} 
                WHERE (user_id = ? OR session_id = ?) 
                AND ticket_category_id = ?
            ");
            $stmt->execute([$this->userId, $this->sessionId, $categoryId]);
            $existingItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update existing item
                $newQuantity = $existingItem['quantity'] + $quantity;
                if ($newQuantity > $availability['available_tickets']) {
                    throw new Exception("Cannot add more tickets than available");
                }
                if ($newQuantity > MAX_TICKETS_PER_CATEGORY) {
                    throw new Exception("Maximum " . MAX_TICKETS_PER_CATEGORY . " tickets allowed per category");
                }
                
                $stmt = $this->db->prepare("
                    UPDATE {$this->table} 
                    SET quantity = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$newQuantity, $existingItem['id']]);
            } else {
                // Add new item
                $stmt = $this->db->prepare("
                    INSERT INTO {$this->table} 
                    (user_id, session_id, ticket_category_id, quantity, price) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $this->userId,
                    $this->sessionId,
                    $categoryId,
                    $quantity,
                    $availability['price']
                ]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Item added to cart',
                'cart_count' => $this->getCartCount()
            ];
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Cart Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function updateItem($categoryId, $quantity) {
        try {
            if ($quantity < 1 || $quantity > MAX_TICKETS_PER_CATEGORY) {
                throw new Exception("Invalid quantity. Must be between 1 and " . MAX_TICKETS_PER_CATEGORY);
            }

            // Check availability
            $ticketCategory = new TicketCategory($this->db);
            $availability = $ticketCategory->checkAvailability($categoryId, $quantity);
            
            if (!$availability['available']) {
                throw new Exception("Not enough tickets available");
            }
            
            $stmt = $this->db->prepare("
                UPDATE {$this->table} 
                SET quantity = ?, updated_at = NOW() 
                WHERE (user_id = ? OR session_id = ?) 
                AND ticket_category_id = ?
            ");
            
            $stmt->execute([$quantity, $this->userId, $this->sessionId, $categoryId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Item not found in cart");
            }
            
            return [
                'success' => true,
                'message' => 'Cart updated successfully',
                'cart' => $this->getCartContents(),
                'cart_count' => $this->getCartCount()
            ];
            
        } catch (Exception $e) {
            error_log("Cart Update Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function removeItem($categoryId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM {$this->table} 
                WHERE (user_id = ? OR session_id = ?) 
                AND ticket_category_id = ?
            ");
            
            $stmt->execute([$this->userId, $this->sessionId, $categoryId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Item not found in cart");
            }
            
            return [
                'success' => true,
                'message' => 'Item removed from cart',
                'cart' => $this->getCartContents(),
                'cart_count' => $this->getCartCount()
            ];
            
        } catch (Exception $e) {
            error_log("Cart Remove Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function getCartContents() {
        try {
            $stmt = $this->db->prepare("
                SELECT ci.*, tc.name as category_name, tc.name, 
                       m.title as match_title, m.match_date,
                       t1.name as team1_name, t2.name as team2_name,
                       t1.logo as team1_logo, t2.logo as team2_logo,
                       s.name as stadium_name
                FROM {$this->table} ci
                JOIN ticket_categories tc ON ci.ticket_category_id = tc.id
                JOIN matches m ON tc.match_id = m.id
                JOIN teams t1 ON m.team1_id = t1.id
                JOIN teams t2 ON m.team2_id = t2.id
                JOIN stadiums s ON m.stadium_id = s.id
                WHERE ci.user_id = ? OR ci.session_id = ?
                ORDER BY ci.added_at DESC
            ");
            
            $stmt->execute([$this->userId, $this->sessionId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total = 0;
            $count = 0;
            
            foreach ($items as &$item) {
                // Remove expired items
                if (strtotime($item['match_date']) < time()) {
                    $this->removeItem($item['ticket_category_id']);
                    continue;
                }
                
                $item['subtotal'] = $item['price'] * $item['quantity'];
                $total += $item['subtotal'];
                $count += $item['quantity'];
            }
            
            return [
                'items' => $items,
                'total' => $total,
                'count' => $count
            ];
            
        } catch (Exception $e) {
            error_log("Cart Contents Error: " . $e->getMessage());
            return [
                'items' => [],
                'total' => 0,
                'count' => 0,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getCartCount() {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(quantity) as count
                FROM {$this->table}
                WHERE user_id = ? OR session_id = ?
            ");
            
            $stmt->execute([$this->userId, $this->sessionId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)($result['count'] ?? 0);
            
        } catch (Exception $e) {
            error_log("Cart Count Error: " . $e->getMessage());
            return 0;
        }
    }
    
    public function clearCart() {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM {$this->table}
                WHERE user_id = ? OR session_id = ?
            ");
            
            $stmt->execute([$this->userId, $this->sessionId]);
            
            return [
                'success' => true,
                'message' => 'Cart cleared successfully'
            ];
            
        } catch (Exception $e) {
            error_log("Clear Cart Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function validateCart() {
        try {
            $contents = $this->getCartContents();
            
            if (empty($contents['items'])) {
                throw new Exception("Cart is empty");
            }
            
            foreach ($contents['items'] as $item) {
                $ticketCategory = new TicketCategory($this->db);
                $availability = $ticketCategory->checkAvailability($item['ticket_category_id'], $item['quantity']);
                
                if (!$availability['available']) {
                    throw new Exception("Not enough tickets available for {$item['category_name']}");
                }
                
                if (strtotime($item['match_date']) < time()) {
                    $this->removeItem($item['ticket_category_id']);
                    throw new Exception("Match date has passed for {$item['match_title']}");
                }
            }
            
            return [
                'success' => true,
                'message' => 'Cart validation successful'
            ];
            
        } catch (Exception $e) {
            error_log("Cart Validation Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function transferCart($fromSessionId, $toUserId) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET user_id = ?, session_id = NULL 
                    WHERE session_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$toUserId, $fromSessionId]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Transfer cart error: " . $e->getMessage());
            return false;
        }
    }
}
?>