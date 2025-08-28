<?php
// db_test.php - Upload this to your root directory for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// CHANGE THESE TO YOUR ACTUAL DATABASE DETAILS
$host = 'localhost';
$db_name = 'snooker_tracking';    // ← UPDATE THIS
$username = 'snooker_admin';        // ← UPDATE THIS  
$password = 'Seahuf0!(*$';        // ← UPDATE THIS

echo "<p><strong>Attempting to connect to:</strong></p>";
echo "<p>Host: $host</p>";
echo "<p>Database: $db_name</p>";
echo "<p>Username: $username</p>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'><strong>✅ Connection successful!</strong></p>";
    
    // Test if players table exists
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM players");
    $result = $stmt->fetch();
    
    echo "<p style='color: green;'>📊 Players table found with {$result['count']} players</p>";
    
    // Show all players
    $stmt = $pdo->query("SELECT * FROM players ORDER BY name");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Players:</h3>";
    echo "<ul>";
    foreach($players as $player) {
        echo "<li>ID: {$player['id']}, Name: {$player['name']}, Email: " . ($player['email'] ?: 'None') . "</li>";
    }
    echo "</ul>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'><strong>❌ Connection failed:</strong></p>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    
    // Common issues and solutions
    echo "<h3>Common Solutions:</h3>";
    echo "<ul>";
    echo "<li>Check your database name is correct (usually prefixed with your username)</li>";
    echo "<li>Verify your database username and password</li>";
    echo "<li>Make sure you've created the database and imported the SQL file</li>";
    echo "<li>Check if the database user has permissions for this database</li>";
    echo "</ul>";
}
?>

<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
    p { margin: 10px 0; }
    ul { margin: 10px 0; padding-left: 20px; }
    li { margin: 5px 0; }
</style>