<?php
// api/get_players.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../classes/Player.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $player = new Player($db);
    $players = $player->getAll();
    
    echo json_encode($players);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching players: ' . $e->getMessage()]);
}
?>