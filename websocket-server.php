<?php
require 'vendor/autoload.php';
require_once 'config/init.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class FootballTicketsWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $adminClients;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->adminClients = new \SplObjectStorage;
        $this->db = Database::getInstance()->getConnection();
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            return;
        }

        switch ($data['type']) {
            case 'admin_auth':
                if ($this->verifyAdminToken($data['token'] ?? '')) {
                    $this->adminClients->attach($from);
                    $from->send(json_encode([
                        'type' => 'auth_success',
                        'message' => 'Successfully authenticated as admin'
                    ]));
                }
                break;

            case 'order_update':
                if ($this->adminClients->contains($from)) {
                    $this->broadcastOrderUpdate($data['order_id']);
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $this->adminClients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function verifyAdminToken($token) {
        // Implement your admin token verification logic here
        // This should match your admin authentication system
        return true; // Temporary for testing
    }

    protected function broadcastOrderUpdate($orderId) {
        try {
            // Get updated order information
            $sql = "SELECT o.*, 
                           u.name as user_name,
                           u.email as user_email,
                           COUNT(oi.id) as items_count
                    FROM orders o
                    LEFT JOIN users u ON o.user_id = u.id
                    LEFT JOIN order_items oi ON o.id = oi.order_id
                    WHERE o.id = ?
                    GROUP BY o.id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($order) {
                $update = json_encode([
                    'type' => 'order_update',
                    'data' => $order
                ]);

                foreach ($this->adminClients as $client) {
                    $client->send($update);
                }
            }
        } catch (\Exception $e) {
            error_log("Error broadcasting order update: " . $e->getMessage());
        }
    }
}

// Create event loop and socket server
$loop = Factory::create();
$webSocket = new WsServer(new FootballTicketsWebSocket());
$server = new Server('0.0.0.0:8080', $loop);

// Create HTTP server
$httpServer = new HttpServer($webSocket);

// Create IO server
$ioServer = new IoServer($httpServer, $server, $loop);

echo "WebSocket server started on port 8080\n";
$loop->run(); 