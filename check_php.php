<?php
echo "<h2>PHP Upload Configuration Check</h2>";

echo "<h3>Upload Settings:</h3>";
echo "<ul>";
echo "<li><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? 'Enabled' : 'Disabled') . "</li>";
echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</li>";
echo "<li><strong>upload_tmp_dir:</strong> " . (ini_get('upload_tmp_dir') ?: 'Default') . "</li>";
echo "</ul>";

echo "<h3>Directory Permissions:</h3>";
$upload_dir = 'uploads/documents/';

if (!file_exists($upload_dir)) {
    echo "<p style='color: orange;'>⚠️ Upload directory doesn't exist. Creating...</p>";
    if (mkdir($upload_dir, 0777, true)) {
        echo "<p style='color: green;'>✅ Directory created!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to create directory!</p>";
    }
}

if (file_exists($upload_dir)) {
    echo "<p style='color: green;'>✅ Upload directory exists</p>";
    echo "<p><strong>Path:</strong> " . realpath($upload_dir) . "</p>";
    echo "<p><strong>Writable:</strong> " . (is_writable($upload_dir) ? 'Yes' : 'No') . "</p>";
    echo "<p><strong>Permissions:</strong> " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "</p>";
} else {
    echo "<p style='color: red;'>❌ Upload directory doesn't exist</p>";
}

echo "<h3>Test Links:</h3>";
echo "<p><a href='test_upload.php'>Simple Upload Test</a></p>";
echo "<p><a href='documents.php'>Documents Page</a></p>";
echo "<p><a href='dashboard.php'>Dashboard</a></p>";
?>
