<?php
session_start();

// For testing, set a dummy user ID if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'TestUser';
}

echo "<h2>Upload Test</h2>";

if ($_POST && isset($_FILES['document'])) {
    echo "<h3>POST Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<h3>File Data:</h3>";
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";
    
    $upload_dir = 'uploads/documents/';
    
    // Check if directory exists
    if (!file_exists($upload_dir)) {
        echo "<p>Creating upload directory...</p>";
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p style='color: green;'>✅ Directory created successfully!</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create directory!</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Upload directory exists!</p>";
    }
    
    // Check if directory is writable
    if (is_writable($upload_dir)) {
        echo "<p style='color: green;'>✅ Directory is writable!</p>";
    } else {
        echo "<p style='color: red;'>❌ Directory is not writable!</p>";
    }
    
    $file = $_FILES['document'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $new_filename = 'test_' . time() . '_' . $file['name'];
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo "<p style='color: green;'>✅ File uploaded successfully to: $upload_path</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to move uploaded file!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Upload error: " . $file['error'] . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Test</title>
</head>
<body>
    <h3>Simple Upload Test</h3>
    <form method="POST" enctype="multipart/form-data">
        <p>
            <label>Select File:</label><br>
            <input type="file" name="document" required>
        </p>
        <p>
            <label>Document Type:</label><br>
            <select name="document_type" required>
                <option value="">Select type</option>
                <option value="medical_report">Medical Report</option>
                <option value="lab_results">Lab Results</option>
            </select>
        </p>
        <p>
            <button type="submit">Upload File</button>
        </p>
    </form>
    
    <p><a href="documents.php">Back to Documents Page</a></p>
</body>
</html>
