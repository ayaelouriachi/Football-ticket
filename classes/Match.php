<?php
require_once 'config/database.php';

class FootballMatch {
    private $db;
    private $table = 'matches';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Obtenir tous les matchs avec pagination et filtres
    public function getAllMatches($page = 1, $limit = 10, $filters = []) {
        try {
            $offset = ($page - 1) * $limit;
            $where = "WHERE 1=1";
            $params = [];

            // Filtre par compétition
            if (!empty($filters['competition'])) {
                $where .= " AND m.competition = ?";
                $params[] = $filters['competition'];
            }

            // Filtre par équipe
            if (!empty($filters['team'])) {
                $where .= " AND (t1.name LIKE ? OR t2.name LIKE ?)";
                $params[] = "%{$filters['team']}%";
                $params[] = "%{$filters['team']}%";
            }

            $sql = "SELECT m.*, m.competition,
                           t1.name as home_team_name, t1.logo as home_team_logo,
                           t2.name as away_team_name, t2.logo as away_team_logo,
                           s.name as stadium_name, s.city as stadium_city,
                           COUNT(tc.id) as categories_count,
                           MIN(tc.price) as min_price
                    FROM {$this->table} m
                    LEFT JOIN teams t1 ON m.team1_id = t1.id
                    LEFT JOIN teams t2 ON m.team2_id = t2.id
                    LEFT JOIN stadiums s ON m.stadium_id = s.id
                    LEFT JOIN ticket_categories tc ON m.id = tc.match_id
                    {$where}
                    GROUP BY m.id, m.competition, m.match_date, m.team1_id, m.team2_id, m.stadium_id,
                             t1.name, t1.logo, t2.name, t2.logo, s.name, s.city
                    ORDER BY m.match_date ASC
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Get matches error: " . $e->getMessage());
            return [];
        }
    }
    
    // Obtenir un match par ID avec ses catégories de billets
    public function getMatchById($id) {
        try {
            $sql = "SELECT m.*, m.title,
                           t1.name as home_team_name, t1.logo as home_team_logo,
                           t2.name as away_team_name, t2.logo as away_team_logo,
                           s.name as stadium_name, s.city as stadium_city, s.capacity, s.address
                    FROM {$this->table} m
                    LEFT JOIN teams t1 ON m.team1_id = t1.id
                    LEFT JOIN teams t2 ON m.team2_id = t2.id
                    LEFT JOIN stadiums s ON m.stadium_id = s.id
                    WHERE m.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($match) {
                // Récupérer les catégories de billets
                $match['categories'] = $this->getTicketCategories($id);
            }
            
            return $match;
            
        } catch (PDOException $e) {
            error_log("Get match by ID error: " . $e->getMessage());
            return false;
        }
    }

    // Obtenir les matchs par compétition
    public function getMatchesByCompetition($competition) {
        try {
            $sql = "SELECT m.*, 
                           t1.name as home_team_name, t1.logo as home_team_logo,
                           t2.name as away_team_name, t2.logo as away_team_logo,
                           s.name as stadium_name, s.city as stadium_city,
                           COUNT(tc.id) as categories_count
                    FROM {$this->table} m
                    LEFT JOIN teams t1 ON m.team1_id = t1.id
                    LEFT JOIN teams t2 ON m.team2_id = t2.id
                    LEFT JOIN stadiums s ON m.stadium_id = s.id
                    LEFT JOIN ticket_categories tc ON m.id = tc.match_id
                    WHERE m.match_date > NOW() AND m.competition = ?
                    GROUP BY m.id
                    ORDER BY m.match_date ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$competition]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log("Get matches by competition error: " . $e->getMessage());
            return [];
        }
    }
    
    // Créer un nouveau match
    public function createMatch($data) {
        try {
            $this->db->beginTransaction();
            
            // Validation des données
            $errors = $this->validateMatchData($data);
            if (!empty($errors)) {
                $this->db->rollback();
                return ['success' => false, 'errors' => $errors];
            }
            
            // Insertion du match
            $sql = "INSERT INTO {$this->table} 
                    (team1_id, team2_id, stadium_id, match_date, competition, description, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'scheduled', NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['team1_id'],
                $data['team2_id'],
                $data['stadium_id'],
                $data['match_date'],
                $data['competition'] ?? 'Botola Pro',
                $data['description'] ?? ''
            ]);
            
            if ($result) {
                $matchId = $this->db->lastInsertId();
                $this->db->commit();
                
                return [
                    'success' => true,
                    'match_id' => $matchId,
                    'message' => 'Match créé avec succès'
                ];
            }
            
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du match'
            ];
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Create match error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la création du match'
            ];
        }
    }
    
    // Mettre à jour un match
    public function updateMatch($id, $data) {
        try {
            $this->db->beginTransaction();
            
            // Validation des données
            $errors = $this->validateMatchData($data);
            if (!empty($errors)) {
                $this->db->rollback();
                return ['success' => false, 'errors' => $errors];
            }
            
            $sql = "UPDATE {$this->table} SET 
                    team1_id = ?,
                    team2_id = ?,
                    stadium_id = ?,
                    match_date = ?,
                    competition = ?,
                    description = ?,
                    updated_at = NOW()
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['team1_id'],
                $data['team2_id'],
                $data['stadium_id'],
                $data['match_date'],
                $data['competition'],
                $data['description'] ?? '',
                $id
            ]);
            
            if ($result) {
                $this->db->commit();
                return [
                    'success' => true,
                    'message' => 'Match mis à jour avec succès'
                ];
            }
            
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du match'
            ];
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Update match error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du match'
            ];
        }
    }
    
    // Valider les données du match
    private function validateMatchData($data) {
        $errors = [];
        
        if (empty($data['team1_id'])) {
            $errors[] = "L'équipe 1 est requise";
        }
        
        if (empty($data['team2_id'])) {
            $errors[] = "L'équipe 2 est requise";
        }
        
        if (!empty($data['team1_id']) && !empty($data['team2_id']) && $data['team1_id'] === $data['team2_id']) {
            $errors[] = "Les équipes doivent être différentes";
        }
        
        if (empty($data['stadium_id'])) {
            $errors[] = "Le stade est requis";
        }
        
        if (empty($data['match_date'])) {
            $errors[] = "La date du match est requise";
        } elseif (strtotime($data['match_date']) < time()) {
            $errors[] = "La date du match doit être dans le futur";
        }
        
        if (empty($data['competition'])) {
            $errors[] = "La compétition est requise";
        }
        
        return $errors;
    }

    // MÉTHODE PRINCIPALE: Obtenir les catégories de billets d'un match
    public function getTicketCategories($matchId) {
        try {
            $sql = "SELECT tc.*, 
                           tc.remaining_tickets,
                           tc.capacity as original_capacity,
                           (tc.capacity - tc.remaining_tickets) as tickets_sold
                    FROM ticket_categories tc
                    WHERE tc.match_id = ?
                    ORDER BY tc.price ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$matchId]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Normaliser les données pour l'affichage
            foreach ($categories as &$category) {
                // Calculer les places restantes
                $remaining = max(0, $category['remaining_tickets']);
                
                // Conserver les valeurs originales et ajouter les calculées
                $category['remaining_tickets'] = $remaining;
                $category['is_sold_out'] = ($remaining <= 0);
                $category['sold_percentage'] = $category['original_capacity'] > 0 
                    ? round((($category['original_capacity'] - $remaining) / $category['original_capacity']) * 100, 1) 
                    : 0;
            }
            
            return $categories;
            
        } catch (PDOException $e) {
            error_log("Get ticket categories error: " . $e->getMessage());
            return [];
        }
    }

    // Supprimer un match
    public function deleteMatch($id) {
        try {
            $this->db->beginTransaction();
            
            // Vérifier s'il y a des commandes associées
            $sql = "SELECT COUNT(*) FROM order_items oi 
                    JOIN ticket_categories tc ON oi.ticket_category_id = tc.id 
                    WHERE tc.match_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            if ($stmt->fetchColumn() > 0) {
                $this->db->rollback();
                return ['success' => false, 'errors' => ['Impossible de supprimer : des commandes existent pour ce match']];
            }
            
            // Supprimer les catégories de billets
            $sql = "DELETE FROM ticket_categories WHERE match_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            // Supprimer le match
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                $this->db->commit();
                return ['success' => true, 'message' => 'Match supprimé avec succès'];
            }
            
            $this->db->rollback();
            return ['success' => false, 'errors' => ['Erreur lors de la suppression']];
            
        } catch (PDOException $e) {
            $this->db->rollback();
            error_log("Delete match error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Erreur technique lors de la suppression']];
        }
    }
    
    // Compter le nombre total de matchs
    public function getTotalMatches($filters = []) {
        try {
            $where = "WHERE 1=1";
            $params = [];
            
            // Application des filtres
            if (!empty($filters['competition'])) {
                $where .= " AND competition = ?";
                $params[] = $filters['competition'];
            }
            
            if (!empty($filters['team'])) {
                $where .= " AND (team1_id IN (SELECT id FROM teams WHERE name LIKE ?) 
                           OR team2_id IN (SELECT id FROM teams WHERE name LIKE ?))";
                $params[] = "%{$filters['team']}%";
                $params[] = "%{$filters['team']}%";
            }
            
            $sql = "SELECT COUNT(*) FROM {$this->table} {$where}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Count matches error: " . $e->getMessage());
            return 0;
        }
    }
}
?>