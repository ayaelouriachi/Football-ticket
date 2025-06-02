<?php
require_once __DIR__ . '/../config/init.php';

echo "Running tests for cart functionality and image handling...\n\n";

// Test database connection
try {
    $db->query("SELECT 1");
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test session
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Session is active\n";
} else {
    echo "✗ Session is not active\n";
    exit(1);
}

// Test cart initialization
try {
    $cart = new Cart($db, $_SESSION);
    echo "✓ Cart class initialized successfully\n";
} catch (Exception $e) {
    echo "✗ Cart initialization failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test cart operations
try {
    // Clear cart first
    $cart->clearCart();
    echo "✓ Cart cleared successfully\n";
    
    // Get a valid ticket category for testing
    $stmt = $db->query("SELECT id FROM ticket_categories WHERE available_tickets > 0 LIMIT 1");
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        throw new Exception("No available ticket categories found");
    }
    
    // Test adding item to cart
    $result = $cart->addItem($category['id'], 1);
    if (!$result['success']) {
        throw new Exception("Failed to add item to cart: " . $result['message']);
    }
    echo "✓ Add to cart successful\n";
    
    // Test updating cart item
    $result = $cart->updateItem($category['id'], 2);
    if (!$result['success']) {
        throw new Exception("Failed to update cart item: " . $result['message']);
    }
    echo "✓ Update cart item successful\n";
    
    // Test cart contents
    $contents = $cart->getCartContents();
    if (empty($contents['items'])) {
        throw new Exception("Cart contents are empty after adding item");
    }
    echo "✓ Get cart contents successful\n";
    
    // Test cart validation
    $result = $cart->validateCart();
    if (!$result['success']) {
        throw new Exception("Cart validation failed: " . $result['message']);
    }
    echo "✓ Cart validation successful\n";
    
    // Test removing item from cart
    $result = $cart->removeItem($category['id']);
    if (!$result['success']) {
        throw new Exception("Failed to remove item from cart: " . $result['message']);
    }
    echo "✓ Remove from cart successful\n";
    
} catch (Exception $e) {
    echo "✗ Cart operations test failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test image directories
$directories = [
    UPLOAD_PATH => "Main uploads directory",
    TEAM_LOGOS_PATH => "Team logos directory",
    STADIUM_IMAGES_PATH => "Stadium images directory",
    MATCH_IMAGES_PATH => "Match images directory"
];

foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "✓ {$description} exists and is writable\n";
        } else {
            echo "✗ {$description} exists but is not writable\n";
        }
    } else {
        echo "✗ {$description} does not exist\n";
    }
}

// Test default images
$default_images = [
    DEFAULT_TEAM_LOGO => "Default team logo",
    DEFAULT_STADIUM_IMAGE => "Default stadium image",
    DEFAULT_MATCH_IMAGE => "Default match image",
    DEFAULT_PLACEHOLDER => "Default placeholder image"
];

foreach ($default_images as $file => $description) {
    $full_path = __DIR__ . "/.." . $file;
    if (file_exists($full_path)) {
        echo "✓ {$description} exists\n";
    } else {
        echo "✗ {$description} does not exist\n";
    }
}

// Test database image URLs
try {
    // Check team logos
    $stmt = $db->query("SELECT id, name, logo FROM teams WHERE logo IS NOT NULL LIMIT 5");
    $teams = $stmt->fetchAll();
    
    echo "\nTesting team logo URLs:\n";
    foreach ($teams as $team) {
        $url = parse_url($team['logo']);
        if ($url === false) {
            echo "✗ Invalid URL format for team {$team['name']}\n";
        } else {
            echo "✓ Valid URL format for team {$team['name']}\n";
        }
    }
    
    // Check stadium images
    $stmt = $db->query("SELECT id, name, image FROM stadiums WHERE image IS NOT NULL LIMIT 5");
    $stadiums = $stmt->fetchAll();
    
    echo "\nTesting stadium image URLs:\n";
    foreach ($stadiums as $stadium) {
        $url = parse_url($stadium['image']);
        if ($url === false) {
            echo "✗ Invalid URL format for stadium {$stadium['name']}\n";
        } else {
            echo "✓ Valid URL format for stadium {$stadium['name']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Database image URL test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nAll tests completed!\n"; 