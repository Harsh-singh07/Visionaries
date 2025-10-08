<?php
require_once 'config/database.php';

echo "<h2>HealthFirst Database Fix</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "<p style='color: red;'>❌ Database connection failed!</p>";
        echo "<h3>Troubleshooting Steps:</h3>";
        echo "<ol>";
        echo "<li>Make sure XAMPP MySQL service is running</li>";
        echo "<li>Check if MySQL is running on port 3306</li>";
        echo "<li>Verify database credentials in config/database.php</li>";
        echo "</ol>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Check if healthfirst database exists
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Current database: <strong>" . ($result['current_db'] ?? 'None') . "</strong></p>";
    
    // Check if users table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ users table missing! Creating database structure...</p>";
        
        // Run the complete setup SQL
        $sql_file = 'sql/setup.sql';
        if (file_exists($sql_file)) {
            $sql = file_get_contents($sql_file);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    try {
                        $db->exec($statement);
                        echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
                    } catch (Exception $e) {
                        echo "<p style='color: orange;'>⚠️ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
            }
        }
    } else {
        echo "<p style='color: green;'>✅ users table exists!</p>";
    }
    
    // Check if user_documents table exists
    $stmt = $db->query("SHOW TABLES LIKE 'user_documents'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: orange;'>⚠️ user_documents table missing. Creating...</p>";
        
        $create_table_sql = "CREATE TABLE IF NOT EXISTS user_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            document_type VARCHAR(50) NOT NULL,
            description TEXT DEFAULT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        if ($db->exec($create_table_sql)) {
            echo "<p style='color: green;'>✅ user_documents table created!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create user_documents table!</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ user_documents table exists!</p>";
    }
    
    // Test insert (if user exists)
    $stmt = $db->query("SELECT id FROM users LIMIT 1");
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p style='color: green;'>✅ Test user found (ID: {$user['id']})</p>";
        
        // Test if we can insert into user_documents
        try {
            $test_query = "INSERT INTO user_documents (user_id, filename, original_name, file_path, file_size, document_type) 
                          VALUES (:user_id, 'test.txt', 'test.txt', '/test/path', 1024, 'test') 
                          ON DUPLICATE KEY UPDATE filename = filename";
            $stmt = $db->prepare($test_query);
            $stmt->bindParam(':user_id', $user['id']);
            $stmt->execute();
            
            // Delete the test record
            $db->exec("DELETE FROM user_documents WHERE filename = 'test.txt' AND document_type = 'test'");
            
            echo "<p style='color: green;'>✅ Database insert/delete test successful!</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Database insert test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No users found. Creating test user...</p>";
        
        try {
            $password_hash = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute(['testuser', 'test@healthfirst.com', $password_hash]);
            echo "<p style='color: green;'>✅ Test user created! (username: testuser, password: password123)</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to create test user: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Check upload directory
    $upload_dir = 'uploads/documents/';
    if (!file_exists($upload_dir)) {
        echo "<p style='color: orange;'>⚠️ Upload directory missing. Creating...</p>";
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p style='color: green;'>✅ Upload directory created!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create upload directory!</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Upload directory exists!</p>";
    }
    
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✅ Upload directory is writable!</p>";
    } else {
        echo "<p style='color: red;'>❌ Upload directory is not writable!</p>";
        echo "<p>Try running: <code>chmod 777 " . realpath($upload_dir) . "</code></p>";
    }
    
    echo "<h3>✅ Database fix complete!</h3>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='index.php'>Login with testuser / password123</a></li>";
    echo "<li><a href='documents.php'>Test document upload</a></li>";
    echo "<li><a href='dashboard.php'>Go to dashboard</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Critical Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
