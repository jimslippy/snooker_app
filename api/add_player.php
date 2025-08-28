<?php
// api/add_player.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once '../config/database.php';
require_once '../classes/Player.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['name']) || trim($input['name']) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Player name is required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $player = new Player($db);
    $success = $player->create(
        trim($input['name']),
        isset($input['email']) && trim($input['email']) !== '' ? trim($input['email']) : null
    );
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Player added successfully'
        ]);
    } else {
        throw new Exception('Failed to add player');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error adding player: ' . $e->getMessage()]);
}
?>