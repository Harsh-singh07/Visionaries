<!DOCTYPE html>
<html>
<head>
    <title>Database Migration - Add Diet Keto Column</title>
</head>
<body>
    <h2>Database Migration: Adding diet_keto column</h2>
    
<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db !== null) {
        // Check if column already exists
        $check_query = "SHOW COLUMNS FROM user_profile LIKE 'diet_keto'";
        $result = $db->query($check_query);
        
        if ($result->rowCount() == 0) {
            // Column doesn't exist, add it
            $alter_query = "ALTER TABLE user_profile ADD COLUMN diet_keto BOOLEAN DEFAULT NULL AFTER diet_gluten_free";
            $db->exec($alter_query);
            echo "<p style='color: green;'>✅ Successfully added diet_keto column to user_profile table.</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ diet_keto column already exists in user_profile table.</p>";
        }
        
        // Show current table structure
        echo "<h3>Current user_profile table structure:</h3>";
        $columns_query = "SHOW COLUMNS FROM user_profile";
        $columns_result = $db->query($columns_query);
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($column = $columns_result->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Database connection failed.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="index.php">← Back to Login</a></p>
</body>
</html>
