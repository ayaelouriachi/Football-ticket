<?php
require_once __DIR__ . '/../../config/init.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Disable foreign key checks
    $db->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // Drop existing tables in reverse order of dependencies
    $db->query("DROP TABLE IF EXISTS order_items");
    $db->query("DROP TABLE IF EXISTS orders");
    $db->query("DROP TABLE IF EXISTS cart_items");
    $db->query("DROP TABLE IF EXISTS ticket_categories");
    $db->query("DROP TABLE IF EXISTS matches");
    $db->query("DROP TABLE IF EXISTS teams");
    $db->query("DROP TABLE IF EXISTS stadiums");
    $db->query("DROP TABLE IF EXISTS admin_activity_logs");
    $db->query("DROP TABLE IF EXISTS admin_users");
    $db->query("DROP TABLE IF EXISTS users");
    
    // Re-enable foreign key checks
    $db->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create teams table
    $db->query("
        CREATE TABLE IF NOT EXISTS teams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            logo VARCHAR(255),
            city VARCHAR(100),
            country VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create stadiums table
    $db->query("
        CREATE TABLE IF NOT EXISTS stadiums (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            city VARCHAR(100) NOT NULL,
            capacity INT,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create admin_users table
    $db->query("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            permissions TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create admin_activity_logs table
    $db->query("
        CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create users table if not exists
    $db->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create matches table with updated schema
    $db->query("
        CREATE TABLE IF NOT EXISTS matches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            team1_id INT NOT NULL,
            team2_id INT NOT NULL,
            stadium_id INT NOT NULL,
            competition VARCHAR(100) NOT NULL,
            match_date DATETIME NOT NULL,
            description TEXT,
            status ENUM('draft', 'active', 'completed', 'cancelled') DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (team1_id) REFERENCES teams(id),
            FOREIGN KEY (team2_id) REFERENCES teams(id),
            FOREIGN KEY (stadium_id) REFERENCES stadiums(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create ticket_categories table
    $db->query("
        CREATE TABLE IF NOT EXISTS ticket_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            match_id INT NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            capacity INT NOT NULL,
            remaining_tickets INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE,
            INDEX idx_match (match_id),
            INDEX idx_remaining (remaining_tickets)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create orders table
    $db->query("
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50),
            payment_id VARCHAR(100),
            status ENUM('pending', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create order_items table
    $db->query("
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            match_id INT NOT NULL,
            ticket_category_id INT NOT NULL,
            quantity INT NOT NULL,
            price_per_ticket DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (match_id) REFERENCES matches(id),
            FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Create cart_items table
    $db->query("
        CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            session_id VARCHAR(255) NOT NULL,
            ticket_category_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE CASCADE,
            INDEX idx_session_user (session_id, user_id),
            INDEX idx_ticket_category (ticket_category_id),
            INDEX idx_added_at (added_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Insert sample data
    
    // Create default admin user if not exists
    $stmt = $db->prepare("SELECT id FROM admin_users WHERE email = 'admin@example.com' LIMIT 1");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $permissions = json_encode(['all']);
        
        $stmt = $db->prepare("
            INSERT INTO admin_users (name, email, password, permissions)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute(['Admin User', 'admin@example.com', $password, $permissions]);
    }
    
    // Insert sample users
    $db->query("
        INSERT INTO users (name, email, password, status) VALUES
        ('John Doe', 'john@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'active'),
        ('Jane Smith', 'jane@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'active'),
        ('Bob Wilson', 'bob@example.com', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'inactive')
    ");
    
    // Insert sample teams
    $db->query("
        INSERT INTO teams (name, city, country) VALUES
        ('Real Madrid', 'Madrid', 'Spain'),
        ('Barcelona', 'Barcelona', 'Spain'),
        ('Manchester United', 'Manchester', 'England'),
        ('Liverpool', 'Liverpool', 'England'),
        ('Bayern Munich', 'Munich', 'Germany'),
        ('Borussia Dortmund', 'Dortmund', 'Germany')
    ");
    
    // Insert sample stadiums
    $db->query("
        INSERT INTO stadiums (name, city, capacity) VALUES
        ('Santiago Bernabeu', 'Madrid', 81044),
        ('Camp Nou', 'Barcelona', 99354),
        ('Old Trafford', 'Manchester', 74140),
        ('Anfield', 'Liverpool', 53394),
        ('Allianz Arena', 'Munich', 75024),
        ('Signal Iduna Park', 'Dortmund', 81365)
    ");
    
    // Get team and stadium IDs for matches
    $teams = $db->query("SELECT id, name FROM teams")->fetchAll(PDO::FETCH_KEY_PAIR);
    $stadiums = $db->query("SELECT id, name FROM stadiums")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Insert sample matches
    $competitions = ['Champions League', 'Europa League', 'Premier League', 'La Liga', 'Bundesliga'];
    
    $team_pairs = [
        ['Real Madrid', 'Barcelona', 'Santiago Bernabeu'],
        ['Manchester United', 'Liverpool', 'Old Trafford'],
        ['Bayern Munich', 'Borussia Dortmund', 'Allianz Arena']
    ];
    
    foreach ($team_pairs as $pair) {
        $team1_id = array_search($pair[0], $teams);
        $team2_id = array_search($pair[1], $teams);
        $stadium_id = array_search($pair[2], $stadiums);
        $competition = $competitions[array_rand($competitions)];
        
        $db->query("
            INSERT INTO matches (team1_id, team2_id, stadium_id, competition, match_date, status) VALUES
            ($team1_id, $team2_id, $stadium_id, '$competition', DATE_ADD(NOW(), INTERVAL " . rand(7, 30) . " DAY), 'active')
        ");
        
        $match_id = $db->lastInsertId();
        
        // Add ticket categories for the match
        $db->query("
            INSERT INTO ticket_categories (match_id, name, description, price, capacity, remaining_tickets) VALUES
            ($match_id, 'VIP', 'Best seats in the house', 299.99, 100, 100),
            ($match_id, 'Premium', 'Great viewing experience', 199.99, 500, 500),
            ($match_id, 'Standard', 'Regular seating', 99.99, 1000, 1000)
        ");
    }
    
    // Get user IDs for orders
    $user_ids = $db->query("SELECT id FROM users WHERE status = 'active'")->fetchAll(PDO::FETCH_COLUMN);
    $match_ids = $db->query("SELECT id FROM matches")->fetchAll(PDO::FETCH_COLUMN);
    
    // Insert sample orders
    foreach ($user_ids as $user_id) {
        // Create completed orders
        $db->query("
            INSERT INTO orders (user_id, total_amount, payment_method, payment_id, status, created_at) VALUES
            ($user_id, 599.98, 'credit_card', 'PAYMENT_" . uniqid() . "', 'completed', DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30) DAY))
        ");
        
        $order_id = $db->lastInsertId();
        $match_id = $match_ids[array_rand($match_ids)];
        $ticket_category_id = $db->query("SELECT id FROM ticket_categories WHERE match_id = $match_id LIMIT 1")->fetch(PDO::FETCH_COLUMN);
        
        $db->query("
            INSERT INTO order_items (order_id, match_id, ticket_category_id, quantity, price_per_ticket, subtotal)
            VALUES ($order_id, $match_id, $ticket_category_id, 2, 299.99, 599.98)
        ");
        
        // Create pending orders
        $db->query("
            INSERT INTO orders (user_id, total_amount, payment_method, payment_id, status, created_at) VALUES
            ($user_id, 199.98, 'pending', NULL, 'pending', DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 7) DAY))
        ");
        
        $order_id = $db->lastInsertId();
        $match_id = $match_ids[array_rand($match_ids)];
        $ticket_category_id = $db->query("SELECT id FROM ticket_categories WHERE match_id = $match_id LIMIT 1")->fetch(PDO::FETCH_COLUMN);
        
        $db->query("
            INSERT INTO order_items (order_id, match_id, ticket_category_id, quantity, price_per_ticket, subtotal)
            VALUES ($order_id, $match_id, $ticket_category_id, 2, 99.99, 199.98)
        ");
    }
    
    // Insert admin activities
    $admin_id = $db->query("SELECT id FROM admin_users LIMIT 1")->fetch(PDO::FETCH_COLUMN);
    $db->query("
        INSERT INTO admin_activity_logs (admin_id, action, details, ip_address) VALUES
        ($admin_id, 'login', 'Admin logged in', '127.0.0.1'),
        ($admin_id, 'create_match', 'Created new match: Real Madrid vs Barcelona', '127.0.0.1'),
        ($admin_id, 'update_match', 'Updated match details for: Manchester United vs Liverpool', '127.0.0.1')
    ");
    
    echo "Database tables created successfully!\n";
    echo "Default admin credentials:\n";
    echo "Email: admin@example.com\n";
    echo "Password: admin123\n";
    echo "\nSample data has been inserted.\n";
    
} catch (Exception $e) {
    die("Error creating tables: " . $e->getMessage());
} 