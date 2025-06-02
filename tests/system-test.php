<?php
require_once __DIR__ . '/../config/init.php';

class SystemTest {
    private $db;
    private $cart;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;

    public function __construct($db) {
        $this->db = $db;
        $this->cart = new Cart($db, $_SESSION);
    }

    private function assert($condition, $message) {
        $this->totalTests++;
        if ($condition) {
            $this->passedTests++;
            $this->results[] = "✅ {$message}";
            return true;
        } else {
            $this->results[] = "❌ {$message}";
            return false;
        }
    }

    public function runAllTests() {
        echo "Starting system tests...\n\n";

        $this->testConfiguration();
        $this->testDirectories();
        $this->testDatabase();
        $this->testCart();
        $this->testImages();
        $this->testSecurity();

        $this->displayResults();
    }

    private function testConfiguration() {
        echo "Testing Configuration...\n";
        
        $this->assert(defined('ROOT_PATH'), 'ROOT_PATH is defined');
        $this->assert(defined('UPLOADS_PATH'), 'UPLOADS_PATH is defined');
        $this->assert(defined('CART_TIMEOUT'), 'CART_TIMEOUT is defined');
        $this->assert(defined('MAX_TICKETS_PER_CATEGORY'), 'MAX_TICKETS_PER_CATEGORY is defined');
        
        $this->assert(session_status() === PHP_SESSION_ACTIVE, 'Session is active');
        $this->assert(ini_get('session.cookie_httponly') == 1, 'Session cookies are httponly');
        
        echo "\n";
    }

    private function testDirectories() {
        echo "Testing Directory Structure...\n";
        
        $directories = [
            UPLOADS_PATH => "Main uploads directory",
            UPLOADS_PATH . '/teams' => "Teams directory",
            UPLOADS_PATH . '/stadiums' => "Stadiums directory",
            UPLOADS_PATH . '/matches' => "Matches directory",
            ASSETS_PATH . '/images' => "Images directory",
        ];

        foreach ($directories as $dir => $description) {
            $exists = is_dir($dir);
            $writable = $exists && is_writable($dir);
            $this->assert($exists && $writable, "{$description} exists and is writable");
        }
        
        echo "\n";
    }

    private function testDatabase() {
        echo "Testing Database Connection and Tables...\n";
        
        try {
            $this->db->query("SELECT 1");
            $this->assert(true, "Database connection successful");
            
            $tables = ['matches', 'teams', 'ticket_categories', 'stadiums', 'users'];
            foreach ($tables as $table) {
                $stmt = $this->db->query("SHOW TABLES LIKE '{$table}'");
                $this->assert($stmt->rowCount() > 0, "Table {$table} exists");
            }
            
            // Test for sample data
            $stmt = $this->db->query("SELECT COUNT(*) FROM matches");
            $matchCount = $stmt->fetchColumn();
            $this->assert($matchCount > 0, "Found {$matchCount} matches in database");
            
        } catch (PDOException $e) {
            $this->assert(false, "Database error: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function testCart() {
        echo "Testing Cart Functionality...\n";
        
        // Clear cart first
        $this->cart->clearCart();
        $this->assert(empty($_SESSION['cart']), "Cart cleared successfully");
        
        // Get a valid ticket category for testing
        $stmt = $this->db->query("SELECT id FROM ticket_categories WHERE remaining_tickets > 0 LIMIT 1");
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            // Test adding item
            $result = $this->cart->addItem($category['id'], 1);
            $this->assert($result['success'], "Add item to cart");
            
            // Test updating quantity
            $result = $this->cart->updateItem($category['id'], 2);
            $this->assert($result['success'], "Update cart item quantity");
            
            // Test cart contents
            $contents = $this->cart->getCartContents();
            $this->assert(!empty($contents['items']), "Cart contains items");
            
            // Test cart validation
            $result = $this->cart->validateCart();
            $this->assert($result['success'], "Cart validation");
            
            // Test removing item
            $result = $this->cart->removeItem($category['id']);
            $this->assert($result['success'], "Remove item from cart");
        } else {
            $this->assert(true, "No available tickets for testing cart functionality");
        }
        
        echo "\n";
    }

    private function testImages() {
        echo "Testing Image System...\n";
        
        $defaultImages = [
            DEFAULT_TEAM_LOGO => "Default team logo",
            DEFAULT_STADIUM_IMAGE => "Default stadium image",
            DEFAULT_MATCH_IMAGE => "Default match image",
            DEFAULT_PLACEHOLDER => "Default placeholder image"
        ];

        foreach ($defaultImages as $path => $description) {
            $fullPath = ROOT_PATH . $path;
            $this->assert(file_exists($fullPath), "{$description} exists at {$path}");
        }
        
        // Test image URLs in database
        try {
            $stmt = $this->db->query("SELECT logo FROM teams WHERE logo IS NOT NULL LIMIT 1");
            $team = $stmt->fetch();
            if ($team) {
                $this->assert(
                    filter_var($team['logo'], FILTER_VALIDATE_URL) || 
                    file_exists(ROOT_PATH . $team['logo']),
                    "Team logo URL/path is valid"
                );
            }
        } catch (PDOException $e) {
            $this->assert(false, "Error checking team images: " . $e->getMessage());
        }
        
        echo "\n";
    }

    private function testSecurity() {
        echo "Testing Security Settings...\n";
        
        // Test security headers
        $headers = headers_list();
        $headerTests = [
            'X-Frame-Options: DENY',
            'X-XSS-Protection: 1; mode=block',
            'X-Content-Type-Options: nosniff'
        ];

        foreach ($headerTests as $header) {
            $this->assert(
                in_array($header, $headers),
                "Security header '{$header}' is set"
            );
        }
        
        // Test session settings
        $this->assert(
            ini_get('session.cookie_httponly') == 1,
            "Session cookies are HttpOnly"
        );
        
        $this->assert(
            ini_get('session.use_strict_mode') == 1,
            "Session strict mode is enabled"
        );
        
        echo "\n";
    }

    private function displayResults() {
        echo "\nTest Results:\n";
        echo "============\n";
        foreach ($this->results as $result) {
            echo $result . "\n";
        }
        
        $percentage = ($this->passedTests / $this->totalTests) * 100;
        echo "\nSummary:\n";
        echo "--------\n";
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests}\n";
        echo "Failed: " . ($this->totalTests - $this->passedTests) . "\n";
        echo "Success Rate: " . number_format($percentage, 2) . "%\n";
    }
}

// Run the tests
$tester = new SystemTest($db);
$tester->runAllTests(); 