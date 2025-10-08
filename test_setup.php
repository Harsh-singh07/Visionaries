<?php
require_once 'config/database.php';

echo "<h2>HealthFirst Setup Test</h2>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "<p style='color: red;'>❌ Database connection failed!</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if user_documents table exists
    $stmt = $db->query("SHOW TABLES LIKE 'user_documents'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ user_documents table exists!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ user_documents table missing. Creating...</p>";
        
        // Create the table
        $sql = "CREATE TABLE IF NOT EXISTS user_documents (
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
        
        if ($db->exec($sql)) {
            echo "<p style='color: green;'>✅ user_documents table created!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create user_documents table!</p>";
        }
    }
    
    // Check if uploads directory exists
    $upload_dir = 'uploads/documents/';
    if (is_dir($upload_dir)) {
        echo "<p style='color: green;'>✅ Upload directory exists!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Upload directory missing. Creating...</p>";
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p style='color: green;'>✅ Upload directory created!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create upload directory!</p>";
        }
    }
    
    // Check directory permissions
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✅ Upload directory is writable!</p>";
    } else {
        echo "<p style='color: red;'>❌ Upload directory is not writable!</p>";
    }
    
    echo "<h3>✅ Setup test complete!</h3>";
    echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='documents.php'>Test Documents</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
