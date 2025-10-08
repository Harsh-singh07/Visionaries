<?php
// Database initialization script
// Run this file once to set up the database and tables

require_once 'config/database.php';

echo "<h2>HealthFirst Database Initialization</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        throw new Exception("Could not connect to database. Please check your XAMPP MySQL service.");
    }
    
    echo "<p>✅ Database connection successful</p>";
    
    // Read and execute SQL file
    $sql_file = file_get_contents('sql/setup.sql');
    
    if ($sql_file === false) {
        throw new Exception("Could not read SQL setup file");
    }
    
    // Split SQL commands by semicolon and execute each one
    $sql_commands = explode(';', $sql_file);
    
    foreach ($sql_commands as $command) {
        $command = trim($command);
        if (!empty($command)) {
            $db->exec($command);
        }
    }
    
    echo "<p>✅ Database tables created successfully</p>";
    
    // Test if sample user exists
    $check_user = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'testuser'");
    $check_user->execute();
    $user_count = $check_user->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($user_count > 0) {
        echo "<p>✅ Sample user already exists</p>";
    } else {
        echo "<p>⚠️ Sample user not found - you may need to run the SQL file manually</p>";
    }
    
    echo "<h3>Database Setup Complete!</h3>";
    echo "<p><strong>Test Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Username: <code>testuser</code></li>";
    echo "<li>Password: <code>password123</code></li>";
    echo "</ul>";
    echo "<p><a href='index.php'>Go to Login Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Make sure XAMPP is running</li>";
    echo "<li>Start Apache and MySQL services</li>";
    echo "<li>Check if MySQL is running on port 3306</li>";
    echo "<li>Verify database credentials in config/database.php</li>";
    echo "</ol>";
}
?>
