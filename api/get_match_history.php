<?php
// api/simple_match_history.php - Clean simple version using existing config
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Use existing database config
    $basePath = $_SERVER['DOCUMENT_ROOT'] . '/snooker_app';
    require_once $basePath . '/config/database.php';
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }
    
    // Get recent games
    $gamesQuery = "SELECT g.*, 
                          p1.name as player1_name, 
                          p2.name as player2_name,
                          COUNT(s.id) as total_shots
                   FROM games g
                   LEFT JOIN players p1 ON g.player1_id = p1.id
                   LEFT JOIN players p2 ON g.player2_id = p2.id
                   LEFT JOIN shots s ON g.id = s.game_id
                   GROUP BY g.id
                   ORDER BY g.created_at DESC 
                   LIMIT 20";
    
    $stmt = $pdo->prepare($gamesQuery);
    $stmt->execute();
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get player statistics
    $playersQuery = "SELECT p.*, 
                            COUNT(DISTINCT g.id) as games_played,
                            COUNT(s.id) as total_shots,
                            SUM(CASE WHEN s.shot_quality = 'good' THEN 1 ELSE 0 END) as good_shots,
                            SUM(CASE WHEN s.shot_quality = 'ok' THEN 1 ELSE 0 END) as ok_shots,
                            SUM(CASE WHEN s.shot_quality = 'bad' THEN 1 ELSE 0 END) as bad_shots,
                            SUM(s.points_scored) as total_points
                     FROM players p
                     LEFT JOIN games g ON (p.id = g.player1_id OR p.id = g.player2_id)
                     LEFT JOIN shots s ON p.id = s.player_id
                     GROUP BY p.id
                     ORDER BY p.name";
    
    $stmt2 = $pdo->prepare($playersQuery);
    $stmt2->execute();
    $players = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Process games with success rates
    $processedGames = [];
    foreach ($games as $game) {
        // Get shot stats for each player in this game
        $shotStatsQuery = "SELECT player_id, shot_quality, COUNT(*) as count
                          FROM shots 
                          WHERE game_id = ?
                          GROUP BY player_id, shot_quality";
        $stmt3 = $pdo->prepare($shotStatsQuery);
        $stmt3->execute([$game['id']]);
        $shotStats = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        $player1Stats = ['good' => 0, 'ok' => 0, 'bad' => 0];
        $player2Stats = ['good' => 0, 'ok' => 0, 'bad' => 0];
        
        foreach ($shotStats as $stat) {
            if ($stat['player_id'] == $game['player1_id']) {
                $player1Stats[$stat['shot_quality']] = $stat['count'];
            } elseif ($game['player2_id'] && $stat['player_id'] == $game['player2_id']) {
                $player2Stats[$stat['shot_quality']] = $stat['count'];
            }
        }
        
        $p1Total = array_sum($player1Stats);
        $p2Total = array_sum($player2Stats);
        
        $game['player1_success_rate'] = $p1Total > 0 ? round(($player1Stats['good'] / $p1Total) * 100, 1) : 0;
        $game['player2_success_rate'] = $p2Total > 0 ? round(($player2Stats['good'] / $p2Total) * 100, 1) : 0;
        $game['total_player1_shots'] = $p1Total;
        $game['total_player2_shots'] = $p2Total;
        
        $processedGames[] = $game;
    }
    
    echo json_encode([
        'success' => true,
        'games' => $processedGames,
        'player_stats' => $players
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Error: ' . $e->getMessage()
    ]);
}
?>