<?php
require_once dirname(__DIR__) . '/config/database.php';

class TicketCategory {
    private $db;
    private $table = 'ticket_categories';
    
    public function __construct($db = null) {
        $this->db = $db ?: Database::getInstance()->getConnection();
    }
    
    public function getCategoryById($id) {
        try {
            $sql = "SELECT tc.*, 
                           tc.remaining_tickets as remaining
                    FROM {$this->table} tc
                    WHERE tc.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get category by ID error: " . $e->getMessage());
            return false;
        }
    }
    
    public function checkAvailability($categoryId, $quantity) {
        try {
            $sql = "SELECT tc.*, m.match_date
                    FROM {$this->table} tc
                    JOIN matches m ON tc.match_id = m.id
                    WHERE tc.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$categoryId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            
            return [
                'available' => $result['remaining_tickets'] >= $quantity,
                'available_tickets' => $result['remaining_tickets'],
                'price' => $result['price'],
                'match_date' => $result['match_date']
            ];
            
        } catch (PDOException $e) {
            error_log("Check availability error: " . $e->getMessage());
            return false;
        }
    }
    
    public function updateRemainingTickets($categoryId, $quantity, $increase = false) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET remaining_tickets = remaining_tickets " . ($increase ? "+" : "-") . " ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quantity, $categoryId]);
            
        } catch (PDOException $e) {
            error_log("Update remaining tickets error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCategoriesForMatch($matchId) {
        try {
            $sql = "SELECT tc.*
                    FROM {$this->table} tc
                    WHERE tc.match_id = ?
                    ORDER BY tc.price ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$matchId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Get categories for match error: " . $e->getMessage());
            return [];
        }
    }
    
    public function createCategory($data) {
        try {
            $sql = "INSERT INTO {$this->table} 
                    (match_id, name, description, price, capacity, 
                     remaining_tickets, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['match_id'],
                $data['name'],
                $data['description'],
                $data['price'],
                $data['capacity'],
                $data['capacity'], // Initially, remaining = capacity
            ]);
            
            if ($result) {
                return $this->db->lastInsertId();
            }
            return false;
            
        } catch (PDOException $e) {
            error_log("Create category error: " . $e->getMessage());
            return false;
        }
    }
}
