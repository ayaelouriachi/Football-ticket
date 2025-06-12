<?php
require_once __DIR__ . '/../models/Match.php';
require_once __DIR__ . '/../config/auth.php';

class MatchController {
    private $matchManager;
    
    public function __construct() {
        $this->matchManager = new MatchManager();
    }
    
    /**
     * Get all matches
     */
    public function getAll(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get query parameters
            $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? min(50, max(1, (int) $_GET['limit'])) : 10;
            
            // Get filters
            $filters = [
                'status' => $_GET['status'] ?? null,
                'team_id' => isset($_GET['team_id']) ? (int) $_GET['team_id'] : null,
                'stadium_id' => isset($_GET['stadium_id']) ? (int) $_GET['stadium_id'] : null,
                'date_from' => $_GET['date_from'] ?? null,
                'date_to' => $_GET['date_to'] ?? null
            ];
            
            // Get matches
            $result = $this->matchManager->getAll($filters, $page, $limit);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Get match by ID
     */
    public function getById(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get match
            $match = $this->matchManager->getById($id);
            
            if (!$match) {
                throw new Exception('Match not found', 404);
            }
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => $match
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Create new match
     */
    public function create(): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$this->validateMatchData($data)) {
                throw new Exception('Invalid input data', 400);
            }
            
            // Create match
            $matchId = $this->matchManager->create($data);
            
            // Return success response
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $matchId
                ]
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Update match
     */
    public function update(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data)) {
                throw new Exception('No data provided', 400);
            }
            
            // Update match
            $success = $this->matchManager->update($id, $data);
            
            // Return success response
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Delete match
     */
    public function delete(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Delete match
            $success = $this->matchManager->delete($id);
            
            // Return success response
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Update match status
     */
    public function updateStatus(int $id): void {
        try {
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
                throw new Exception('Method not allowed', 405);
            }
            
            // Check authentication
            if (!Auth::isAuthenticated()) {
                throw new Exception('Unauthorized', 401);
            }
            
            // Get and validate input
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['status'])) {
                throw new Exception('Status is required', 400);
            }
            
            // Update status
            $success = $this->matchManager->updateStatus($id, $data['status']);
            
            // Return success response
            echo json_encode([
                'success' => $success
            ]);
            
        } catch (Exception $e) {
            $code = $e->getCode() ?: 500;
            http_response_code($code);
            echo json_encode([
                'error' => $e->getMessage(),
                'code' => $code
            ]);
        }
    }
    
    /**
     * Validate match data
     */
    private function validateMatchData(array $data): bool {
        $required = [
            'home_team_id',
            'away_team_id',
            'stadium_id',
            'match_date',
            'kickoff_time',
            'ticket_sale_start',
            'ticket_sale_end'
        ];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // Validate dates
        if (!strtotime($data['match_date']) || 
            !strtotime($data['ticket_sale_start']) || 
            !strtotime($data['ticket_sale_end'])) {
            return false;
        }
        
        // Validate ticket categories if provided
        if (isset($data['ticket_categories'])) {
            if (!is_array($data['ticket_categories'])) {
                return false;
            }
            
            foreach ($data['ticket_categories'] as $category) {
                if (!isset($category['name']) || 
                    !isset($category['price']) || 
                    !isset($category['capacity'])) {
                    return false;
                }
                
                if (!is_numeric($category['price']) || 
                    !is_numeric($category['capacity'])) {
                    return false;
                }
            }
        }
        
        return true;
    }
} 