<?php
/**
 * Database Configuration and Connection Management
 */

class Database {
    private static $instance = null;
    private $connection = null;
    
    // Database configuration
    private $host = 'localhost';
    private $dbname = 'football_tickets';
    private $username = 'root';  // Change in production
    private $password = '';      // Change in production
    private $charset = 'utf8mb4';
    
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::ATTR_PERSISTENT => true
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserialization of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Get Database instance (Singleton pattern)
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get the database connection
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
    
    /**
     * Execute a query and return the statement
     */
    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Get the last inserted ID
     */
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
} 