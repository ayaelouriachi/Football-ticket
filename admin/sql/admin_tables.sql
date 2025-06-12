-- Create admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    role ENUM('super_admin', 'admin', 'moderator') NOT NULL DEFAULT 'moderator',
    status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_login DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin activity logs
CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);

-- Create admin failed login attempts
CREATE TABLE IF NOT EXISTS admin_failed_logins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_ip (email, ip_address)
);

-- Create admin settings
CREATE TABLE IF NOT EXISTS admin_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'float', 'boolean', 'json') NOT NULL DEFAULT 'string',
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create admin password resets
CREATE TABLE IF NOT EXISTS admin_password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- Create admin notifications
CREATE TABLE IF NOT EXISTS admin_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read BOOLEAN NOT NULL DEFAULT FALSE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- Insert default admin settings
INSERT IGNORE INTO admin_settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Football Tickets Admin', 'string', 'Nom du site d''administration'),
('items_per_page', '10', 'integer', 'Nombre d''éléments par page dans les listes'),
('enable_notifications', 'true', 'boolean', 'Activer les notifications'),
('session_lifetime', '3600', 'integer', 'Durée de vie de la session en secondes'),
('allowed_file_types', '["jpg","jpeg","png","pdf"]', 'json', 'Types de fichiers autorisés pour l''upload'),
('max_file_size', '5242880', 'integer', 'Taille maximale des fichiers en octets'),
('payment_mode', 'sandbox', 'string', 'Mode de paiement PayPal (sandbox/live)'),
('paypal_client_id', 'YOUR_SANDBOX_CLIENT_ID', 'string', 'Client ID PayPal Sandbox'),
('currency', 'EUR', 'string', 'Devise par défaut');

-- Insert default super admin user
INSERT IGNORE INTO admin_users (email, password_hash, first_name, last_name, role) VALUES
('admin@ticketfoot.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'super_admin');

-- Insert some sample teams
INSERT IGNORE INTO teams (name, description) VALUES
('Paris Saint-Germain', 'Club de la capitale'),
('Olympique de Marseille', 'Club phocéen'),
('Olympique Lyonnais', 'Club rhodanien'),
('AS Monaco', 'Club de la principauté');

-- Insert some sample stadiums
INSERT IGNORE INTO stadiums (name, location, capacity) VALUES
('Parc des Princes', 'Paris', 47929),
('Orange Vélodrome', 'Marseille', 67394),
('Groupama Stadium', 'Lyon', 59186),
('Stade Louis-II', 'Monaco', 18523); 