<?php
// api/end_game.php
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
require_once '../classes/Game.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['game_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Game ID is required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $game = new Game($db);
    $success = $game->endGame(
        $input['game_id'],
        $input['winner_id'] ?? null,
        isset($input['score_p1']) ? intval($input['score_p1']) : 0,
        isset($input['score_p2']) ? intval($input['score_p2']) : 0
    );
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Game ended successfully'
        ]);
    } else {
        throw new Exception('Failed to end game');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error ending game: ' . $e->getMessage()]);
}
?>