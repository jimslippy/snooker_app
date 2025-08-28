<?php
// api/start_game.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $basePath = $_SERVER['DOCUMENT_ROOT'] . '/snooker_app';
    
    // Check if database config exists
    if (!file_exists($basePath . '/config/database.php')) {
        throw new Exception('Database config file not found');
    }
    
    require_once $basePath . '/config/database.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['player1_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Player 1 ID is required']);
        exit;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Direct SQL instead of using Game class
    $query = "INSERT INTO games (player1_id, player2_id, game_date) VALUES (?, ?, CURDATE())";
    $stmt = $db->prepare($query);
    $success = $stmt->execute([
        $input['player1_id'],
        isset($input['player2_id']) && $input['player2_id'] ? $input['player2_id'] : null
    ]);
    
    if ($success) {
        $game_id = $db->lastInsertId();
        echo json_encode([
            'success' => true,
            'game_id' => $game_id,
            'message' => 'Game started successfully'
        ]);
    } else {
        throw new Exception('Failed to create game');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error starting game: ' . $e->getMessage()]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode(['error' => 'PHP Error: ' . $e->getMessage()]);
}
?>