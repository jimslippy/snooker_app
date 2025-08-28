<?php
// api/record_shot.php
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
require_once '../classes/Shot.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['game_id'], $input['player_id'], $input['shot_quality'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields: game_id, player_id, shot_quality']);
        exit;
    }
    
    // Validate shot quality
    $valid_qualities = ['good', 'ok', 'bad'];
    if (!in_array($input['shot_quality'], $valid_qualities)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid shot quality. Must be: good, ok, or bad']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    $shot = new Shot($db);
    $success = $shot->recordShot(
        $input['game_id'],
        $input['player_id'],
        $input['shot_quality'],
        $input['shot_type'] ?? 'general',
        $input['ball_targeted'] ?? null,
        isset($input['points']) ? intval($input['points']) : 0,
        $input['notes'] ?? null
    );
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Shot recorded successfully'
        ]);
    } else {
        throw new Exception('Failed to record shot');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error recording shot: ' . $e->getMessage()]);
}
?>