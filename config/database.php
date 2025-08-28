<?php
// config/database.php - Database connection
class Database {
    // UPDATE THESE VALUES WITH YOUR BRIXLY DATABASE DETAILS
    private $host = 'localhost';                    // Usually 'localhost' for Brixly
    private $db_name = 'snooker_app';       // Replace with your actual database name
    private $username = 'snooker_admin ';        // Replace with your database username
    private $password = 'Seahuf0!(*$';        // Replace with your database password
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
?>