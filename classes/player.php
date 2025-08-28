<?php
// classes/Player.php
class Player {
    private $conn;
    private $table_name = "players";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($name, $email = null) {
        $query = "INSERT INTO " . $this->table_name . " (name, email) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$name, $email]);
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getPlayerStats($player_id) {
        $query = "SELECT 
                    COUNT(*) as total_shots,
                    SUM(CASE WHEN shot_quality = 'good' THEN 1 ELSE 0 END) as good_shots,
                    SUM(CASE WHEN shot_quality = 'ok' THEN 1 ELSE 0 END) as ok_shots,
                    SUM(CASE WHEN shot_quality = 'bad' THEN 1 ELSE 0 END) as bad_shots,
                    SUM(points_scored) as total_points,
                    COUNT(DISTINCT game_id) as games_played,
                    ROUND(AVG(CASE WHEN shot_quality = 'good' THEN 100 WHEN shot_quality = 'ok' THEN 50 ELSE 0 END), 1) as avg_quality
                  FROM shots WHERE player_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$player_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getRecentGames($player_id, $limit = 5) {
        $query = "SELECT g.*, 
                         p1.name as player1_name, 
                         p2.name as player2_name,
                         COUNT(s.id) as total_shots
                  FROM games g
                  LEFT JOIN players p1 ON g.player1_id = p1.id
                  LEFT JOIN players p2 ON g.player2_id = p2.id
                  LEFT JOIN shots s ON g.id = s.game_id
                  WHERE g.player1_id = ? OR g.player2_id = ?
                  GROUP BY g.id
                  ORDER BY g.created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$player_id, $player_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>