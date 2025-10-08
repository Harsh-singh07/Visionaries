<?php
session_start();

// Redirect to appropriate page if already logged in
if (isset($_SESSION['user_id'])) {
    // Check if user has completed path selection and assessment
    require_once 'config/database.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db !== null) {
            // Check if user has any assessments
            $query = "SELECT COUNT(*) as assessment_count FROM user_assessments WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If user has assessments, go to dashboard, otherwise go to path selection
            if ($result['assessment_count'] > 0) {
                header('Location: dashboard.php');
            } else {
                header('Location: path_selection.php');
            }
        } else {
            header('Location: path_selection.php');
        }
    } catch (Exception $e) {
        header('Location: path_selection.php');
    }
    exit();
}

$error_message = '';

// Handle login form submission
if ($_POST) {
    require_once 'config/database.php';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db === null) {
                $error_message = 'Database connection failed. Please check your XAMPP MySQL service.';
            } else {
                // Check if user exists
                $query = "SELECT id, username, password_hash FROM users WHERE username = :username OR email = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Verify password
                    if (password_verify($password, $user['password_hash'])) {
                        // Login successful
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        
                        // Check if user has completed assessment
                        $assessment_query = "SELECT COUNT(*) as assessment_count FROM user_assessments WHERE user_id = :user_id";
                        $assessment_stmt = $db->prepare($assessment_query);
                        $assessment_stmt->bindParam(':user_id', $user['id']);
                        $assessment_stmt->execute();
                        $assessment_result = $assessment_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Redirect based on assessment completion
                        if ($assessment_result['assessment_count'] > 0) {
                            header('Location: dashboard.php');
                        } else {
                            header('Location: path_selection.php');
                        }
                        exit();
                    } else {
                        $error_message = 'Invalid username or password.';
                    }
                } else {
                    $error_message = 'Invalid username or password.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Login failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>HealthFirst</h1>
                <p>Welcome back! Please sign in to your account.</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                    >
                    <span class="error-text" id="usernameError"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        minlength="6"
                        required
                    >
                    <span class="error-text" id="passwordError"></span>
                </div>
                
                <button type="submit" class="login-btn">Sign In</button>
            </form>
            
            <div class="login-footer">
                <p><a href="forgot-password.php">Forgot Password?</a></p>
                <p>Don't have an account? <a href="register.php">Sign Up</a></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/login.js"></script>
</body>
</html>
