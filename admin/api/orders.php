<?php
header('Content-Type: application/json');
require_once('../../config/init.php');

// Ensure admin is logged in
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_orders':
            // Get orders with pagination and filters
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = intval($_GET['limit'] ?? 10);
            $status = $_GET['status'] ?? '';
            $search = $_GET['search'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            
            $where = [];
            $params = [];
            
            if ($status) {
                $where[] = "o.status = ?";
                $params[] = $status;
            }
            
            if ($search) {
                $where[] = "(o.id LIKE ? OR u.email LIKE ? OR u.name LIKE ?)";
                $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
            }
            
            if ($dateFrom) {
                $where[] = "DATE(o.created_at) >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $where[] = "DATE(o.created_at) <= ?";
                $params[] = $dateTo;
            }
            
            $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
            $offset = ($page - 1) * $limit;
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        $whereClause";
            $stmt = $db->prepare($countSql);
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];
            
            // Get orders
            $sql = "SELECT 
                        o.*,
                        u.name as user_name,
                        u.email as user_email,
                        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    $whereClause
                    ORDER BY o.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute(array_merge($params, [$limit, $offset]));
            $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        case 'update_status':
            // Update order status
            if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
                throw new Exception('Missing required parameters');
            }
            
            $orderId = $_POST['order_id'];
            $status = $_POST['status'];
            $validStatuses = ['pending', 'completed', 'cancelled', 'refunded'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$status, $orderId]);
            
            // Log the action
            logAdminActivity('update_order_status', "Updated order #$orderId status to $status");
            
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully'
            ]);
            break;
            
        case 'delete':
            // Delete order (soft delete or handle based on business rules)
            if (!isset($_POST['order_id'])) {
                throw new Exception('Missing order ID');
            }
            
            $orderId = $_POST['order_id'];
            
            // Check if order can be deleted
            $checkSql = "SELECT status FROM orders WHERE id = ?";
            $stmt = $db->prepare($checkSql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            if ($order['status'] === 'completed') {
                throw new Exception('Cannot delete completed orders');
            }
            
            // Soft delete by updating status to cancelled
            $sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderId]);
            
            // Log the action
            logAdminActivity('delete_order', "Cancelled order #$orderId");
            
            echo json_encode([
                'success' => true,
                'message' => 'Order cancelled successfully'
            ]);
            break;
            
        case 'get_details':
            // Get detailed order information
            if (!isset($_GET['order_id'])) {
                throw new Exception('Missing order ID');
            }
            
            $orderId = $_GET['order_id'];
            
            $sql = "SELECT o.*, 
                           u.name as user_name, 
                           u.email as user_email,
                           oi.quantity,
                           oi.price_per_ticket,
                           oi.subtotal,
                           m.match_date,
                           t1.name as home_team,
                           t2.name as away_team,
                           tc.name as ticket_category
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    LEFT JOIN matches m ON oi.match_id = m.id
                    LEFT JOIN teams t1 ON m.team1_id = t1.id
                    LEFT JOIN teams t2 ON m.team2_id = t2.id
                    LEFT JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
                    WHERE o.id = ?";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$orderId]);
            $orderDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $orderDetails
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
    error_log("Order API error: " . $e->getMessage());
} 