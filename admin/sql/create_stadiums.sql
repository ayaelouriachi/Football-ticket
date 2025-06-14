-- Create stadiums table
CREATE TABLE IF NOT EXISTS stadiums (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample stadiums
INSERT IGNORE INTO stadiums (name, city, capacity) VALUES
('Santiago Bernabéu', 'Madrid', 81044),
('Camp Nou', 'Barcelona', 99354),
('Parc des Princes', 'Paris', 47929),
('Allianz Arena', 'Munich', 75024); 