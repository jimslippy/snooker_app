<?php
// file_check.php - Upload to /snooker_app/ directory to check file structure
header('Content-Type: text/html');

echo "<h2>File Structure Check</h2>";
echo "<p><strong>Current directory:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Document root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";

$basePath = __DIR__;
$files = [
    'config/database.php',
    'classes/Player.php', 
    'classes/Game.php',
    'classes/Shot.php',
    'api/get_players.php',
    'api/start_game.php',
    'api/record_shot.php',
    'api/end_game.php',
    'api/add_player.php'
];

echo "<h3>File Check:</h3>";
echo "<ul>";
foreach ($files as $file) {
    $fullPath = $basePath . '/' . $file;
    $exists = file_exists($fullPath);
    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✅ Found' : '❌ Missing';
    echo "<li style='color: $color;'>$file - $status</li>";
}
echo "</ul>";

// Check if we can include the database config
echo "<h3>Database Config Test:</h3>";
$configPath = $basePath . '/config/database.php';
if (file_exists($configPath)) {
    try {
        require_once $configPath;
        echo "<p style='color: green;'>✅ Database config loaded successfully</p>";
        
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            echo "<p style='color: green;'>✅ Database connection successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Database connection failed</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Database config file not found</p>";
}
?>