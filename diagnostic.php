<?php
require_once 'config/database.php';

echo "<h2>HealthFirst Database Diagnostic</h2>";

try {
    echo "<h3>1. Testing Database Connection</h3>";
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "<p style='color: red;'>❌ Database connection failed!</p>";
        echo "<p><strong>Possible issues:</strong></p>";
        echo "<ul>";
        echo "<li>XAMPP MySQL service is not running</li>";
        echo "<li>MySQL is not running on port 3306</li>";
        echo "<li>Database credentials are incorrect</li>";
        echo "</ul>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    echo "<h3>2. Checking Database Existence</h3>";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Current database: <strong>" . ($result['current_db'] ?? 'None') . "</strong></p>";
    
    echo "<h3>3. Checking Required Tables</h3>";
    $required_tables = ['users', 'user_profile', 'user_medical_conditions', 'user_lifestyle_preferences', 'user_assessments'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>❌ Table '$table' missing</p>";
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<h3>4. Creating Missing Tables</h3>";
        echo "<p>Attempting to create missing tables...</p>";
        
        // Read and execute SQL setup file
        $sql_file = 'sql/setup.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Split by semicolon and execute each statement
            $statements = explode(';', $sql);
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    try {
                        $db->exec($statement);
                    } catch (Exception $e) {
                        // Ignore errors for statements that might already exist
                    }
                }
            }
            
            echo "<p style='color: green;'>✅ Database setup completed!</p>";
        } else {
            echo "<p style='color: red;'>❌ SQL setup file not found!</p>";
        }
    }
    
    echo "<h3>5. Verifying user_profile Table Structure</h3>";
    $stmt = $db->query("DESCRIBE user_profile");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = ['is_child_profile', 'height', 'weight', 'blood_group'];
    foreach ($required_columns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $col) {
                echo "<p style='color: green;'>✅ Column '$col' exists</p>";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "<p style='color: red;'>❌ Column '$col' missing</p>";
        }
    }
    
    echo "<h3>✅ Diagnostic Complete!</h3>";
    echo "<p><a href='quiz.php' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Quiz Again</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Start Apache and MySQL services</li>";
    echo "<li>Make sure MySQL is running on port 3306</li>";
    echo "<li>Refresh this page</li>";
    echo "</ol>";
}
?>
