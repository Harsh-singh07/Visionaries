<?php
session_start();
require_once 'config/database.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

$success_message = '';
$error_message = '';
$uploaded_documents = [];

// Handle file upload
if ($_POST && isset($_FILES['document'])) {
    $upload_dir = 'uploads/documents/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $error_message = "Failed to create upload directory.";
        }
    }
    
    $file = $_FILES['document'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    // Check for upload errors
    if ($file_error === UPLOAD_ERR_OK && empty($error_message)) {
        // Check file size (4MB = 4 * 1024 * 1024 bytes)
        if ($file_size <= 4 * 1024 * 1024) {
            // Get file extension
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
            
            if (in_array($file_ext, $allowed_extensions)) {
                // Generate unique filename
                $new_filename = $user_id . '_' . time() . '_' . $file_name;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Save to database
                    try {
                        $database = new Database();
                        $db = $database->getConnection();
                        
                        if ($db !== null) {
                            $query = "INSERT INTO user_documents (user_id, filename, original_name, file_path, file_size, document_type, upload_date) 
                                     VALUES (:user_id, :filename, :original_name, :file_path, :file_size, :document_type, NOW())";
                            
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':user_id', $user_id);
                            $stmt->bindParam(':filename', $new_filename);
                            $stmt->bindParam(':original_name', $file_name);
                            $stmt->bindParam(':file_path', $upload_path);
                            $stmt->bindParam(':file_size', $file_size);
                            $stmt->bindParam(':document_type', $_POST['document_type']);
                            
                            if ($stmt->execute()) {
                                $success_message = "Document uploaded successfully!";
                            } else {
                                $error_message = "Failed to save document information.";
                            }
                        }
                    } catch (Exception $e) {
                        error_log("Document upload error: " . $e->getMessage());
                        $error_message = "Database error: " . $e->getMessage() . " (Check if user_documents table exists)";
                    }
                } else {
                    $error_message = "Failed to upload file.";
                }
            } else {
                $error_message = "Invalid file type. Allowed: PDF, DOC, DOCX, JPG, JPEG, PNG, TXT";
            }
        } else {
            $error_message = "File size must be less than 4MB. Your file size: " . number_format($file_size / 1024 / 1024, 2) . "MB";
        }
    } else {
        switch ($file_error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "File is too large.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "File upload was interrupted.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = "No file was selected.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Missing temporary folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Failed to write file to disk.";
                break;
            default:
                $error_message = "Upload error occurred. Error code: " . $file_error;
                break;
        }
    }
}

// Get user's uploaded documents
try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db !== null) {
        $query = "SELECT * FROM user_documents WHERE user_id = :user_id ORDER BY upload_date DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $uploaded_documents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Documents fetch error: " . $e->getMessage());
    $error_message = "Error loading documents: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Documents</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .documents-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .documents-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .back-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .upload-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .upload-area {
            border: 2px dashed #667eea;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #f8f9ff;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .upload-area:hover {
            border-color: #5a6fd8;
            background: #f0f4ff;
        }
        
        .upload-area.dragover {
            border-color: #4CAF50;
            background: #f0fff0;
        }
        
        .upload-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .upload-text {
            font-size: 1.1rem;
            color: #333;
            margin-bottom: 10px;
        }
        
        .upload-subtext {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        
        .file-input {
            display: none;
        }
        
        .select-file-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .select-file-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .form-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .upload-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .upload-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .upload-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .documents-list {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .document-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .document-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }
        
        .document-icon {
            font-size: 2rem;
            margin-right: 15px;
            color: #667eea;
        }
        
        .document-info {
            flex: 1;
        }
        
        .document-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .document-meta {
            font-size: 0.9rem;
            color: #666;
        }
        
        .document-actions {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .view-btn {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .view-btn:hover {
            background: #bbdefb;
        }
        
        .delete-btn {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .delete-btn:hover {
            background: #ffcdd2;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .file-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .file-preview-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .file-preview-icon {
            font-size: 1.5rem;
            color: #667eea;
        }
        
        .file-preview-details {
            flex: 1;
        }
        
        .file-preview-name {
            font-weight: 500;
            color: #333;
        }
        
        .file-preview-size {
            font-size: 0.9rem;
            color: #666;
        }
        
        .remove-file-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .documents-container {
                padding: 10px;
            }
            
            .documents-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .upload-section,
            .documents-list {
                padding: 20px;
            }
            
            .upload-area {
                padding: 30px 20px;
            }
            
            .document-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .document-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="documents-container">
        <!-- Header -->
        <div class="documents-header">
            <a href="dashboard.php" class="back-btn">
                <span>←</span> Back to Dashboard
            </a>
            <h1>📄 Documents</h1>
        </div>
        
        <!-- Upload Section -->
        <div class="upload-section">
            <h2 style="margin-bottom: 25px; color: #333;">Upload Medical Report</h2>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">Drag & drop your file here</div>
                    <div class="upload-subtext">or click to select a file (Max 4MB)</div>
                    <button type="button" class="select-file-btn" id="selectFileBtn">
                        Select File
                    </button>
                    <input type="file" id="fileInput" name="document" class="file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt">
                </div>
                
                <div id="filePreview" class="file-preview" style="display: none;">
                    <div class="file-preview-info">
                        <div class="file-preview-icon">📄</div>
                        <div class="file-preview-details">
                            <div class="file-preview-name" id="fileName"></div>
                            <div class="file-preview-size" id="fileSize"></div>
                        </div>
                        <button type="button" class="remove-file-btn" id="removeFileBtn">Remove</button>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-group">
                        <label for="document_type">Document Type</label>
                        <select id="document_type" name="document_type" required>
                            <option value="">Select document type</option>
                            <option value="medical_report">Medical Report</option>
                            <option value="lab_results">Lab Results</option>
                            <option value="prescription">Prescription</option>
                            <option value="x_ray">X-Ray</option>
                            <option value="mri_scan">MRI Scan</option>
                            <option value="ct_scan">CT Scan</option>
                            <option value="ultrasound">Ultrasound</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description (Optional)</label>
                        <textarea id="description" name="description" rows="3" placeholder="Add any notes about this document..."></textarea>
                    </div>
                    
                    <button type="submit" class="upload-btn" id="uploadBtn" disabled>
                        Upload Document
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Documents List -->
        <div class="documents-list">
            <h2 style="margin-bottom: 25px; color: #333;">Your Documents</h2>
            
            <?php if (empty($uploaded_documents)): ?>
                <div class="empty-state">
                    <h3>No documents uploaded yet</h3>
                    <p>Upload your first medical document using the form above.</p>
                </div>
            <?php else: ?>
                <?php foreach ($uploaded_documents as $doc): ?>
                    <div class="document-item">
                        <div class="document-icon">
                            <?php
                            $ext = strtolower(pathinfo($doc['original_name'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png'])) echo '🖼️';
                            elseif ($ext === 'pdf') echo '📄';
                            elseif (in_array($ext, ['doc', 'docx'])) echo '📝';
                            else echo '📋';
                            ?>
                        </div>
                        <div class="document-info">
                            <div class="document-name"><?php echo htmlspecialchars($doc['original_name']); ?></div>
                            <div class="document-meta">
                                Type: <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?> | 
                                Size: <?php echo number_format($doc['file_size'] / 1024, 1); ?> KB | 
                                Uploaded: <?php echo date('M j, Y', strtotime($doc['upload_date'])); ?>
                            </div>
                        </div>
                        <div class="document-actions">
                            <button class="action-btn view-btn" onclick="viewDocument('<?php echo $doc['file_path']; ?>')">
                                View
                            </button>
                            <button class="action-btn delete-btn" onclick="deleteDocument(<?php echo $doc['id']; ?>)">
                                Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const filePreview = document.getElementById('filePreview');
            const uploadBtn = document.getElementById('uploadBtn');
            const selectFileBtn = document.getElementById('selectFileBtn');
            const removeFileBtn = document.getElementById('removeFileBtn');
        
            // Select file button click
            selectFileBtn.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Remove file button click
            removeFileBtn.addEventListener('click', function() {
                removeFile();
            });
        
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
        
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect();
                }
            });
            
            // File input change
            fileInput.addEventListener('change', handleFileSelect);
            
            // Click on upload area to select file
            uploadArea.addEventListener('click', (e) => {
                if (e.target.tagName !== 'BUTTON') {
                    fileInput.click();
                }
            });
        
            function handleFileSelect() {
                const file = fileInput.files[0];
                if (file) {
                    // Check file size (4MB)
                    if (file.size > 4 * 1024 * 1024) {
                        alert('File size must be less than 4MB');
                        fileInput.value = '';
                        removeFile();
                        return;
                    }
                    
                    // Show file preview
                    document.getElementById('fileName').textContent = file.name;
                    document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' KB';
                    filePreview.style.display = 'block';
                    uploadBtn.disabled = false;
                    uploadBtn.textContent = 'Upload Document';
                } else {
                    removeFile();
                }
            }
        
            function removeFile() {
                fileInput.value = '';
                filePreview.style.display = 'none';
                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Upload Document';
            }
            
            // Form validation
            document.getElementById('uploadForm').addEventListener('submit', function(e) {
                const file = fileInput.files[0];
                const documentType = document.getElementById('document_type').value;
                
                if (!file) {
                    e.preventDefault();
                    alert('Please select a file to upload');
                    return false;
                }
                
                if (!documentType) {
                    e.preventDefault();
                    alert('Please select a document type');
                    return false;
                }
                
                // Show loading state
                uploadBtn.disabled = true;
                uploadBtn.textContent = 'Uploading...';
            });
        }); // End of DOMContentLoaded
        
        function viewDocument(filePath) {
            window.open(filePath, '_blank');
        }
        
        function deleteDocument(docId) {
            if (confirm('Are you sure you want to delete this document?')) {
                // Add delete functionality here
                alert('Delete functionality will be implemented');
            }
        }
    </script>
</body>
</html>
