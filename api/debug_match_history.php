<?php
// api/debug_match_history.php - Debug version
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Debug: Starting match history fetch...\n";

try {
    $basePath = $_SERVER['DOCUMENT_ROOT'] . '/snooker_app';
    echo "Debug: Base path is: $basePath\n";
    
    $configPath = $basePath . '/config/database.php';
    $gamePath = $basePath . '/classes/Game.php';
    $shotPath = $basePath . '/classes/Shot.php';
    $playerPath = $basePath . '/classes/Player.php';
    
    echo "Debug: Checking if files exist...\n";
    echo "Config exists: " . (file_exists($configPath) ? 'YES' : 'NO') . "\n";
    echo "Game class exists: " . (file_exists($gamePath) ? 'YES' : 'NO') . "\n";
    echo "Shot class exists: " . (file_exists($shotPath) ? 'YES' : 'NO') . "\n";
    echo "Player class exists: " . (file_exists($playerPath) ? 'YES' : 'NO') . "\n";
    
    require_once $configPath;
    require_once $gamePath;
    require_once $shotPath;
    require_once $playerPath;
    
    echo "Debug: Files loaded successfully\n";

    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    echo "Debug: Database connected\n";

    $game = new Game($db);
    $shot = new Shot($db);
    $player = new Player($db);
    
    echo "Debug: Objects created\n";
    
    // Test basic query first
    echo "Debug: Testing basic game query...\n";
    $testQuery = "SELECT COUNT(*) as game_count FROM games";
    $stmt = $db->prepare($testQuery);
    $stmt->execute();
    $gameCount = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Debug: Total games in database: " . $gameCount['game_count'] . "\n";
    
    // Test players
    echo "Debug: Testing players query...\n";
    $testQuery2 = "SELECT COUNT(*) as player_count FROM players";
    $stmt2 = $db->prepare($testQuery2);
    $stmt2->execute();
    $playerCount = $stmt2->fetch(PDO::FETCH_ASSOC);
    echo "Debug: Total players in database: " . $playerCount['player_count'] . "\n";
    
    // Try to get recent games
    echo "Debug: Calling getRecentGames...\n";
    $recentGames = $game->getRecentGames(5); // Try with smaller limit
    echo "Debug: Got " . count($recentGames) . " recent games\n";
    
    // Try to get all players
    echo "Debug: Calling getAll players...\n";
    $players = $player->getAll();
    echo "Debug: Got " . count($players) . " players\n";
    
    // Simple response for now
    echo json_encode([
        'success' => true,
        'debug' => 'API is working',
        'game_count' => $gameCount['game_count'],
        'player_count' => $playerCount['player_count'],
        'recent_games_count' => count($recentGames),
        'players_count' => count($players)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>