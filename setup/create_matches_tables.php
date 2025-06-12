<?php
require_once(__DIR__ . '/../config/init.php');

try {
    // Create matches table
    $db->exec("CREATE TABLE IF NOT EXISTS matches (
        id INT PRIMARY KEY AUTO_INCREMENT,
        home_team_id INT NOT NULL,
        away_team_id INT NOT NULL,
        stadium_id INT NOT NULL,
        match_date DATETIME NOT NULL,
        status ENUM('draft', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'draft',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (home_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
        FOREIGN KEY (away_team_id) REFERENCES teams(id) ON DELETE RESTRICT,
        FOREIGN KEY (stadium_id) REFERENCES stadiums(id) ON DELETE RESTRICT
    )");

    // Create teams table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS teams (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        logo VARCHAR(255),
        description TEXT,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create stadiums table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS stadiums (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        location VARCHAR(255),
        capacity INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Create ticket_categories table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS ticket_categories (
        id INT PRIMARY KEY AUTO_INCREMENT,
        match_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        capacity INT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
    )");

    // Create order_items table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        order_id INT NOT NULL,
        match_id INT NOT NULL,
        ticket_category_id INT NOT NULL,
        quantity INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE RESTRICT,
        FOREIGN KEY (ticket_category_id) REFERENCES ticket_categories(id) ON DELETE RESTRICT
    )");

    echo "Tables created successfully!";
} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
} 