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
    private $logger;
    
    public function __construct($db, &$session) {
        $this->db = $db;
        $this->session = &$session;
        $this->userId = isset($session['user_id']) ? $session['user_id'] : null;
        $this->sessionId = session_id();
        $this->initializeCart();
        
        // Clean expired cart items
        $this->cleanExpiredItems();
    }
    
    private function initializeCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        if (!isset($_SESSION['cart_last_updated'])) {
            $_SESSION['cart_last_updated'] = time();
        }
    }
    
    private function cleanExpiredItems() {
        $timeout = time() - CART_EXPIRY;
        foreach ($_SESSION['cart'] as $categoryId => $item) {
            if ($item['added_at'] < $timeout) {
                unset($_SESSION['cart'][$categoryId]);
            }
        }
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
            
            // Check cart timeout
            if (isset($_SESSION['cart_last_updated']) && 
                (time() - $_SESSION['cart_last_updated'] > CART_EXPIRY)) {
                $this->clearCart();
                throw new Exception("Your cart has expired. Please try again.");
            }
            
            // Add to cart
            $cartItem = [
                'category_id' => $categoryId,
                'quantity' => $quantity,
                'price' => $availability['price'],
                'added_at' => time()
            ];

            if (isset($_SESSION['cart'][$categoryId])) {
                $newQuantity = $_SESSION['cart'][$categoryId]['quantity'] + $quantity;
                if ($newQuantity > $availability['available_tickets']) {
                    throw new Exception("Cannot add more tickets than available");
                }
                if ($newQuantity > MAX_TICKETS_PER_CATEGORY) {
                    throw new Exception("Maximum " . MAX_TICKETS_PER_CATEGORY . " tickets allowed per category");
                }
                $_SESSION['cart'][$categoryId]['quantity'] = $newQuantity;
            } else {
                $_SESSION['cart'][$categoryId] = $cartItem;
            }
            
            $_SESSION['cart_last_updated'] = time();
            
            return [
                'success' => true,
                'message' => 'Item added to cart',
                'cart_count' => $this->getCartCount()
            ];
            
        } catch (Exception $e) {
            error_log("Cart Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    public function updateItem($categoryId, $quantity) {
        try {
            if (!isset($_SESSION['cart'][$categoryId])) {
                throw new Exception("Item not found in cart");
            }

            if ($quantity < 1 || $quantity > MAX_TICKETS_PER_CATEGORY) {
                throw new Exception("Invalid quantity. Must be between 1 and " . MAX_TICKETS_PER_CATEGORY);
            }

            // Check availability
            $ticketCategory = new TicketCategory($this->db);
            $availability = $ticketCategory->checkAvailability($categoryId, $quantity);
            
            if (!$availability['available']) {
                throw new Exception("Not enough tickets available");
            }
            
            $_SESSION['cart'][$categoryId]['quantity'] = $quantity;
            $_SESSION['cart_last_updated'] = time();
            
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
            if (!isset($_SESSION['cart'][$categoryId])) {
                throw new Exception("Item not found in cart");
            }

            unset($_SESSION['cart'][$categoryId]);
            $_SESSION['cart_last_updated'] = time();
            
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
            if (empty($_SESSION['cart'])) {
                return [
                    'items' => [],
                    'total' => 0,
                    'count' => 0
                ];
            }

            $items = [];
            $total = 0;
            $count = 0;

            foreach ($_SESSION['cart'] as $categoryId => $item) {
                $stmt = $this->db->prepare("
                    SELECT tc.*, m.title as match_title, m.match_date,
                           t1.name as team1_name, t2.name as team2_name,
                           t1.logo as team1_logo, t2.logo as team2_logo,
                           s.name as stadium_name
                    FROM ticket_categories tc
                    JOIN matches m ON tc.match_id = m.id
                    JOIN teams t1 ON m.team1_id = t1.id
                    JOIN teams t2 ON m.team2_id = t2.id
                    JOIN stadiums s ON m.stadium_id = s.id
                    WHERE tc.id = ?
                ");
                $stmt->execute([$categoryId]);
                $ticketInfo = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($ticketInfo) {
                    // Check if match hasn't passed
                    if (strtotime($ticketInfo['match_date']) < time()) {
                        unset($_SESSION['cart'][$categoryId]);
                        continue;
                    }
                    
                    $subtotal = $item['quantity'] * $item['price'];
                    $items[] = array_merge($ticketInfo, [
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'subtotal' => $subtotal
                    ]);
                    $total += $subtotal;
                    $count += $item['quantity'];
                }
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
        return array_sum(array_column($_SESSION['cart'], 'quantity'));
    }
    
    public function clearCart() {
        $_SESSION['cart'] = [];
        $_SESSION['cart_last_updated'] = time();
        
        return [
            'success' => true,
            'message' => 'Cart cleared successfully'
        ];
    }
    
    public function validateCart() {
        try {
            if (empty($_SESSION['cart'])) {
                throw new Exception("Cart is empty");
            }

            foreach ($_SESSION['cart'] as $categoryId => $item) {
                $stmt = $this->db->prepare("
                    SELECT tc.*, m.match_date, m.title as match_title
                    FROM ticket_categories tc
                    JOIN matches m ON tc.match_id = m.id 
                    WHERE tc.id = ?
                ");
                $stmt->execute([$categoryId]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$ticket) {
                    unset($_SESSION['cart'][$categoryId]);
                    throw new Exception("One or more items in your cart are no longer available");
                }

                if ($ticket['remaining_tickets'] < $item['quantity']) {
                    throw new Exception("Not enough tickets available for {$ticket['name']}");
                }

                if (strtotime($ticket['match_date']) < time()) {
                    unset($_SESSION['cart'][$categoryId]);
                    throw new Exception("Match date has passed for {$ticket['match_title']}");
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