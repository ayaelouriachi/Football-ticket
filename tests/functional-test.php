<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Match.php';
require_once __DIR__ . '/../classes/TicketCategory.php';
require_once __DIR__ . '/../classes/Cart.php';

class FootballTicketsTest {
    private $db;
    private $match;
    private $cart;
    private $ticketCategory;
    private $testResults = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->match = new FootballMatch();
        $this->ticketCategory = new TicketCategory();
        $this->cart = new Cart();
    }
    
    public function runAllTests() {
        echo "🏃 Starting Football Tickets System Tests...\n\n";
        
        // Test Match Listing
        $this->testMatchListing();
        
        // Test Match Details
        $this->testMatchDetails();
        
        // Test Ticket Categories
        $this->testTicketCategories();
        
        // Test Shopping Cart
        $this->testShoppingCart();
        
        // Display Results
        $this->displayResults();
    }
    
    private function testMatchListing() {
        echo "📋 Testing Match Listing...\n";
        
        try {
            // Test getting all matches
            $matches = $this->match->getAllMatches();
            $this->assert(
                'Match listing returns array',
                is_array($matches),
                'Matches should be returned as an array'
            );
            
            $this->assert(
                'Match listing not empty',
                count($matches) > 0,
                'There should be at least one match'
            );
            
            // Test first match data structure
            if (!empty($matches)) {
                $firstMatch = $matches[0];
                $this->assert(
                    'Match has required fields',
                    isset($firstMatch['id']) && 
                    isset($firstMatch['home_team_name']) && 
                    isset($firstMatch['away_team_name']) && 
                    isset($firstMatch['match_date']) && 
                    isset($firstMatch['stadium_name']),
                    'Match should have all required fields'
                );
            }
            
        } catch (Exception $e) {
            $this->assert(
                'Match listing exception',
                false,
                'Exception: ' . $e->getMessage()
            );
        }
    }
    
    private function testMatchDetails() {
        echo "\n📊 Testing Match Details...\n";
        
        try {
            // Get first match ID from database
            $stmt = $this->db->query("SELECT id FROM matches LIMIT 1");
            $matchId = $stmt->fetch(PDO::FETCH_COLUMN);
            
            if ($matchId) {
                $matchDetails = $this->match->getMatchById($matchId);
                
                $this->assert(
                    'Match details retrieval',
                    $matchDetails !== false,
                    'Should be able to retrieve match details'
                );
                
                $this->assert(
                    'Match details structure',
                    isset($matchDetails['home_team']) && 
                    isset($matchDetails['away_team']) && 
                    isset($matchDetails['stadium_name']) && 
                    isset($matchDetails['match_date']),
                    'Match details should have all required fields'
                );
            }
            
        } catch (Exception $e) {
            $this->assert(
                'Match details exception',
                false,
                'Exception: ' . $e->getMessage()
            );
        }
    }
    
    private function testTicketCategories() {
        echo "\n🎫 Testing Ticket Categories...\n";
        
        try {
            // Get first match ID
            $stmt = $this->db->query("SELECT id FROM matches LIMIT 1");
            $matchId = $stmt->fetch(PDO::FETCH_COLUMN);
            
            if ($matchId) {
                $categories = $this->ticketCategory->getCategoriesForMatch($matchId);
                
                $this->assert(
                    'Ticket categories retrieval',
                    is_array($categories),
                    'Should return array of categories'
                );
                
                if (!empty($categories)) {
                    $firstCategory = $categories[0];
                    $this->assert(
                        'Ticket category structure',
                        isset($firstCategory['name']) && 
                        isset($firstCategory['price']) && 
                        isset($firstCategory['remaining_tickets']),
                        'Category should have all required fields'
                    );
                    
                    // Test availability check
                    $availability = $this->ticketCategory->checkAvailability(
                        $firstCategory['id'],
                        1
                    );
                    
                    $this->assert(
                        'Ticket availability check',
                        is_array($availability) && isset($availability['available']),
                        'Should return availability status'
                    );
                }
            }
            
        } catch (Exception $e) {
            $this->assert(
                'Ticket categories exception',
                false,
                'Exception: ' . $e->getMessage()
            );
        }
    }
    
    private function testShoppingCart() {
        echo "\n🛒 Testing Shopping Cart...\n";
        
        try {
            // Get a valid ticket category ID
            $stmt = $this->db->query("SELECT id FROM ticket_categories WHERE available_capacity > 0 LIMIT 1");
            $categoryId = $stmt->fetch(PDO::FETCH_COLUMN);
            
            if ($categoryId) {
                // Test adding item to cart
                $addResult = $this->cart->addItem($categoryId, 1);
                $this->assert(
                    'Add to cart',
                    $addResult['success'],
                    'Should successfully add item to cart'
                );
                
                // Get cart contents
                $cartContents = $this->cart->getCartContents();
                $this->assert(
                    'Cart contents',
                    count($cartContents['items']) > 0,
                    'Cart should contain items'
                );
                
                if (!empty($cartContents['items'])) {
                    $firstItem = $cartContents['items'][0];
                    
                    // Test updating quantity
                    $updateResult = $this->cart->updateQuantity($firstItem['id'], 2);
                    $this->assert(
                        'Update cart quantity',
                        $updateResult['success'],
                        'Should successfully update quantity'
                    );
                    
                    // Test removing item
                    $removeResult = $this->cart->removeItem($firstItem['id']);
                    $this->assert(
                        'Remove from cart',
                        $removeResult['success'],
                        'Should successfully remove item'
                    );
                }
            }
            
        } catch (Exception $e) {
            $this->assert(
                'Shopping cart exception',
                false,
                'Exception: ' . $e->getMessage()
            );
        }
    }
    
    private function assert($name, $condition, $message) {
        $result = [
            'name' => $name,
            'passed' => $condition,
            'message' => $message
        ];
        
        $this->testResults[] = $result;
        
        echo ($condition ? "✅" : "❌") . " {$name}: {$message}\n";
    }
    
    private function displayResults() {
        $total = count($this->testResults);
        $passed = count(array_filter($this->testResults, fn($r) => $r['passed']));
        
        echo "\n📊 Test Results Summary\n";
        echo "========================\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$passed}\n";
        echo "Failed: " . ($total - $passed) . "\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 2) . "%\n";
        
        if ($total !== $passed) {
            echo "\n❌ Failed Tests:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "- {$result['name']}: {$result['message']}\n";
                }
            }
        }
    }
}

// Run the tests
$tester = new FootballTicketsTest();
$tester->runAllTests(); 