<?php
require_once __DIR__ . '/../config/database.php';

class MatchManager {
    private $db;
    private $table = 'matches';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all matches with pagination and filters
     */
    public function getAll(array $filters = [], int $page = 1, int $limit = 10): array {
        try {
            $offset = ($page - 1) * $limit;
            $where = [];
            $params = [];
            
            // Build where clause from filters
            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['team_id'])) {
                $where[] = "(home_team_id = ? OR away_team_id = ?)";
                $params[] = $filters['team_id'];
                $params[] = $filters['team_id'];
            }
            
            if (!empty($filters['stadium_id'])) {
                $where[] = "stadium_id = ?";
                $params[] = $filters['stadium_id'];
            }
            
            if (!empty($filters['date_from'])) {
                $where[] = "match_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where[] = "match_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            // Build the query
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
            $totalStmt = $this->db->query($countSql, $params);
            $total = $totalStmt->fetch()['total'];
            
            // Get matches
            $sql = "SELECT 
                    m.*,
                    ht.name as home_team_name,
                    at.name as away_team_name,
                    s.name as stadium_name,
                    (SELECT COUNT(*) FROM ticket_categories tc WHERE tc.match_id = m.id) as ticket_categories_count,
                    (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.match_id = m.id) as tickets_sold
                FROM {$this->table} m
                LEFT JOIN teams ht ON m.home_team_id = ht.id
                LEFT JOIN teams at ON m.away_team_id = at.id
                LEFT JOIN stadiums s ON m.stadium_id = s.id
                {$whereClause}
                ORDER BY m.match_date DESC
                LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->query($sql, $params);
            $matches = $stmt->fetchAll();
            
            return [
                'data' => $matches,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching matches: " . $e->getMessage());
            throw new Exception("Failed to fetch matches");
        }
    }
    
    /**
     * Get match by ID
     */
    public function getById(int $id): ?array {
        try {
            $sql = "SELECT 
                    m.*,
                    ht.name as home_team_name,
                    at.name as away_team_name,
                    s.name as stadium_name,
                    s.capacity as stadium_capacity,
                    (SELECT COUNT(*) FROM ticket_categories tc WHERE tc.match_id = m.id) as ticket_categories_count,
                    (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.match_id = m.id) as tickets_sold
                FROM {$this->table} m
                LEFT JOIN teams ht ON m.home_team_id = ht.id
                LEFT JOIN teams at ON m.away_team_id = at.id
                LEFT JOIN stadiums s ON m.stadium_id = s.id
                WHERE m.id = ?";
            
            $stmt = $this->db->query($sql, [$id]);
            $match = $stmt->fetch();
            
            if ($match) {
                // Get ticket categories
                $categoriesSql = "SELECT * FROM ticket_categories WHERE match_id = ?";
                $categoriesStmt = $this->db->query($categoriesSql, [$id]);
                $match['ticket_categories'] = $categoriesStmt->fetchAll();
            }
            
            return $match ?: null;
            
        } catch (Exception $e) {
            error_log("Error fetching match: " . $e->getMessage());
            throw new Exception("Failed to fetch match details");
        }
    }
    
    /**
     * Create new match
     */
    public function create(array $data): int {
        try {
            $this->db->beginTransaction();
            
            // Insert match
            $sql = "INSERT INTO {$this->table} (
                home_team_id, away_team_id, stadium_id, match_date, 
                kickoff_time, status, description, ticket_sale_start,
                ticket_sale_end, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [
                $data['home_team_id'],
                $data['away_team_id'],
                $data['stadium_id'],
                $data['match_date'],
                $data['kickoff_time'],
                $data['status'] ?? 'draft',
                $data['description'] ?? null,
                $data['ticket_sale_start'],
                $data['ticket_sale_end']
            ];
            
            $this->db->query($sql, $params);
            $matchId = $this->db->lastInsertId();
            
            // Insert ticket categories if provided
            if (!empty($data['ticket_categories'])) {
                $categorySql = "INSERT INTO ticket_categories (
                    match_id, name, price, capacity, description
                ) VALUES (?, ?, ?, ?, ?)";
                
                foreach ($data['ticket_categories'] as $category) {
                    $this->db->query($categorySql, [
                        $matchId,
                        $category['name'],
                        $category['price'],
                        $category['capacity'],
                        $category['description'] ?? null
                    ]);
                }
            }
            
            $this->db->commit();
            return $matchId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error creating match: " . $e->getMessage());
            throw new Exception("Failed to create match");
        }
    }
    
    /**
     * Update match
     */
    public function update(int $id, array $data): bool {
        try {
            $this->db->beginTransaction();
            
            $updates = [];
            $params = [];
            
            // Build update fields
            $allowedFields = [
                'home_team_id', 'away_team_id', 'stadium_id', 'match_date',
                'kickoff_time', 'status', 'description', 'ticket_sale_start',
                'ticket_sale_end'
            ];
            
            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            
            if (!empty($updates)) {
                $updates[] = "updated_at = NOW()";
                $params[] = $id;
                
                $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = ?";
                $this->db->query($sql, $params);
            }
            
            // Update ticket categories if provided
            if (!empty($data['ticket_categories'])) {
                // Delete existing categories
                $this->db->query("DELETE FROM ticket_categories WHERE match_id = ?", [$id]);
                
                // Insert new categories
                $categorySql = "INSERT INTO ticket_categories (
                    match_id, name, price, capacity, description
                ) VALUES (?, ?, ?, ?, ?)";
                
                foreach ($data['ticket_categories'] as $category) {
                    $this->db->query($categorySql, [
                        $id,
                        $category['name'],
                        $category['price'],
                        $category['capacity'],
                        $category['description'] ?? null
                    ]);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error updating match: " . $e->getMessage());
            throw new Exception("Failed to update match");
        }
    }
    
    /**
     * Delete match
     */
    public function delete(int $id): bool {
        try {
            $this->db->beginTransaction();
            
            // Check if match has any orders
            $orderCheck = $this->db->query(
                "SELECT COUNT(*) as count FROM order_items WHERE match_id = ?",
                [$id]
            );
            
            if ($orderCheck->fetch()['count'] > 0) {
                throw new Exception("Cannot delete match with existing orders");
            }
            
            // Delete ticket categories
            $this->db->query("DELETE FROM ticket_categories WHERE match_id = ?", [$id]);
            
            // Delete match
            $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error deleting match: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Update match status
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            $validStatuses = ['draft', 'active', 'cancelled', 'completed'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$status, $id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating match status: " . $e->getMessage());
            throw new Exception("Failed to update match status");
        }
    }
} 