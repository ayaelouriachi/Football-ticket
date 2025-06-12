<?php
require_once __DIR__ . '/../config/database.php';

class Team {
    private $db;
    private $table = 'teams';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get all teams with pagination and filters
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
            
            if (!empty($filters['search'])) {
                $where[] = "(name LIKE ? OR description LIKE ?)";
                $searchTerm = "%{$filters['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Build the query
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
            $totalStmt = $this->db->query($countSql, $params);
            $total = $totalStmt->fetch()['total'];
            
            // Get teams
            $sql = "SELECT 
                    t.*,
                    (SELECT COUNT(*) FROM matches m 
                     WHERE m.home_team_id = t.id OR m.away_team_id = t.id) as matches_count,
                    (SELECT COUNT(*) FROM matches m 
                     WHERE (m.home_team_id = t.id OR m.away_team_id = t.id)
                     AND m.match_date >= CURDATE()) as upcoming_matches
                FROM {$this->table} t
                {$whereClause}
                ORDER BY t.name ASC
                LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->db->query($sql, $params);
            $teams = $stmt->fetchAll();
            
            return [
                'data' => $teams,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($total / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching teams: " . $e->getMessage());
            throw new Exception("Failed to fetch teams");
        }
    }
    
    /**
     * Get team by ID
     */
    public function getById(int $id): ?array {
        try {
            $sql = "SELECT 
                    t.*,
                    (SELECT COUNT(*) FROM matches m 
                     WHERE m.home_team_id = t.id OR m.away_team_id = t.id) as matches_count,
                    (SELECT COUNT(*) FROM matches m 
                     WHERE (m.home_team_id = t.id OR m.away_team_id = t.id)
                     AND m.match_date >= CURDATE()) as upcoming_matches
                FROM {$this->table} t
                WHERE t.id = ?";
            
            $stmt = $this->db->query($sql, [$id]);
            $team = $stmt->fetch();
            
            if ($team) {
                // Get upcoming matches
                $matchesSql = "SELECT 
                    m.*,
                    ht.name as home_team_name,
                    at.name as away_team_name,
                    s.name as stadium_name,
                    (SELECT COUNT(*) FROM ticket_categories tc WHERE tc.match_id = m.id) as ticket_categories_count
                FROM matches m
                JOIN teams ht ON m.home_team_id = ht.id
                JOIN teams at ON m.away_team_id = at.id
                JOIN stadiums s ON m.stadium_id = s.id
                WHERE (m.home_team_id = ? OR m.away_team_id = ?)
                AND m.match_date >= CURDATE()
                ORDER BY m.match_date ASC
                LIMIT 5";
                
                $matchesStmt = $this->db->query($matchesSql, [$id, $id]);
                $team['upcoming_matches'] = $matchesStmt->fetchAll();
            }
            
            return $team ?: null;
            
        } catch (Exception $e) {
            error_log("Error fetching team: " . $e->getMessage());
            throw new Exception("Failed to fetch team details");
        }
    }
    
    /**
     * Create new team
     */
    public function create(array $data): int {
        try {
            // Validate name uniqueness
            $nameCheck = $this->db->query(
                "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ?",
                [$data['name']]
            );
            
            if ($nameCheck->fetch()['count'] > 0) {
                throw new Exception("Team name already exists");
            }
            
            // Insert team
            $sql = "INSERT INTO {$this->table} (
                name, description, logo_url, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())";
            
            $params = [
                $data['name'],
                $data['description'] ?? null,
                $data['logo_url'] ?? null,
                $data['status'] ?? 'active'
            ];
            
            $this->db->query($sql, $params);
            return $this->db->lastInsertId();
            
        } catch (Exception $e) {
            error_log("Error creating team: " . $e->getMessage());
            throw new Exception($e->getMessage() ?: "Failed to create team");
        }
    }
    
    /**
     * Update team
     */
    public function update(int $id, array $data): bool {
        try {
            // Check if name is being changed and validate uniqueness
            if (!empty($data['name'])) {
                $nameCheck = $this->db->query(
                    "SELECT COUNT(*) as count FROM {$this->table} WHERE name = ? AND id != ?",
                    [$data['name'], $id]
                );
                
                if ($nameCheck->fetch()['count'] > 0) {
                    throw new Exception("Team name already exists");
                }
            }
            
            $updates = [];
            $params = [];
            
            // Build update fields
            $allowedFields = [
                'name', 'description', 'logo_url', 'status'
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
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating team: " . $e->getMessage());
            throw new Exception($e->getMessage() ?: "Failed to update team");
        }
    }
    
    /**
     * Delete team
     */
    public function delete(int $id): bool {
        try {
            // Check if team has any matches
            $matchCheck = $this->db->query(
                "SELECT COUNT(*) as count FROM matches 
                WHERE home_team_id = ? OR away_team_id = ?",
                [$id, $id]
            );
            
            if ($matchCheck->fetch()['count'] > 0) {
                throw new Exception("Cannot delete team with existing matches");
            }
            
            // Delete team
            $this->db->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error deleting team: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Update team status
     */
    public function updateStatus(int $id, string $status): bool {
        try {
            $validStatuses = ['active', 'inactive'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status");
            }
            
            $sql = "UPDATE {$this->table} SET status = ?, updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$status, $id]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error updating team status: " . $e->getMessage());
            throw new Exception("Failed to update team status");
        }
    }
    
    /**
     * Get team statistics
     */
    public function getStats(): array {
        try {
            $sql = "SELECT 
                    COUNT(*) as total_teams,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_teams,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_teams,
                    (SELECT COUNT(*) FROM matches WHERE match_date >= CURDATE()) as upcoming_matches,
                    (SELECT COUNT(DISTINCT stadium_id) FROM matches) as stadiums_used
                FROM {$this->table}";
            
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            error_log("Error fetching team statistics: " . $e->getMessage());
            throw new Exception("Failed to fetch team statistics");
        }
    }
} 