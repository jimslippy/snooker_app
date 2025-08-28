<?php
// classes/Shot.php
class Shot {
    private $conn;
    private $table_name = "shots";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function recordShot($game_id, $player_id, $shot_quality, $shot_type, $ball_targeted = null, $points = 0, $notes = null) {
        // Get current shot number for this game
        $shot_number = $this->getNextShotNumber($game_id);
        $break_number = $this->getCurrentBreakNumber($game_id, $player_id);

        $query = "INSERT INTO " . $this->table_name . " 
                  (game_id, player_id, shot_number, shot_quality, shot_type, ball_targeted, points_scored, break_number, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$game_id, $player_id, $shot_number, $shot_quality, $shot_type, $ball_targeted, $points, $break_number, $notes]);
    }

    private function getNextShotNumber($game_id) {
        $query = "SELECT COALESCE(MAX(shot_number), 0) + 1 as next_shot FROM " . $this->table_name . " WHERE game_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$game_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['next_shot'];
    }

    private function getCurrentBreakNumber($game_id, $player_id) {
        // Simple logic: increment break number when player changes or after a miss/safety
        $query = "SELECT COALESCE(MAX(break_number), 0) as current_break 
                  FROM " . $this->table_name . " 
                  WHERE game_id = ? AND player_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$game_id, $player_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['current_break'] + 1;
    }

    public function getGameShots($game_id, $limit = null) {
        $limitClause = $limit ? "LIMIT " . intval($limit) : "";
        $query = "SELECT s.*, p.name as player_name 
                  FROM " . $this->table_name . " s
                  JOIN players p ON s.player_id = p.id
                  WHERE s.game_id = ? 
                  ORDER BY s.shot_number DESC $limitClause";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$game_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQualityBreakdown($game_id = null, $player_id = null) {
        $where_conditions = [];
        $params = [];

        if ($game_id) {
            $where_conditions[] = "game_id = ?";
            $params[] = $game_id;
        }
        if ($player_id) {
            $where_conditions[] = "player_id = ?";
            $params[] = $player_id;
        }

        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        $query = "SELECT 
                    shot_quality,
                    COUNT(*) as count,
                    ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM " . $this->table_name . " $where_clause)), 1) as percentage
                  FROM " . $this->table_name . " 
                  $where_clause
                  GROUP BY shot_quality";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array_merge($params, $params)); // Double params for subquery
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPlayerShotStats($player_id) {
        $query = "SELECT 
                    shot_type,
                    shot_quality,
                    COUNT(*) as count,
                    AVG(points_scored) as avg_points
                  FROM " . $this->table_name . "
                  WHERE player_id = ?
                  GROUP BY shot_type, shot_quality
                  ORDER BY shot_type, shot_quality";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$player_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteShot($shot_id, $game_id) {
        // Verify the shot belongs to the game for security
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND game_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$shot_id, $game_id]);
    }
}
?>