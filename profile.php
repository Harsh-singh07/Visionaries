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

// Get user profile data
$profile_data = [];
$success_message = '';
$error_message = '';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db !== null) {
        // Handle form submission
        if ($_POST && isset($_POST['update_profile'])) {
            $update_query = "UPDATE users SET username = :username WHERE id = :user_id";
            $stmt = $db->prepare($update_query);
            $stmt->bindParam(':username', $_POST['username']);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $_SESSION['username'] = $_POST['username'];
                $username = $_POST['username'];
                $success_message = "Profile updated successfully!";
            } else {
                $error_message = "Failed to update profile.";
            }
        }
        
        // Get current user data
        $user_query = "SELECT u.*, up.height, up.weight, up.blood_group, up.medications, up.supplements 
                       FROM users u 
                       LEFT JOIN user_profile up ON u.id = up.user_id 
                       WHERE u.id = :user_id";
        $stmt = $db->prepare($user_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get latest assessment
        $assessment_query = "SELECT score, assessment_date FROM user_assessments 
                           WHERE user_id = :user_id ORDER BY assessment_date DESC LIMIT 1";
        $stmt = $db->prepare($assessment_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $latest_assessment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error_message = "Error loading profile data.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .profile-header {
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
        
        .profile-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .profile-avatar-section {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 15px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9ff;
            border-radius: 12px;
            border: 2px solid #e0e4ff;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
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
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .readonly-field {
            background: #f8f9fa;
            color: #666;
            cursor: not-allowed;
        }
        
        .update-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .update-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .retake-assessment-btn {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        
        .retake-assessment-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
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
        
        @media (max-width: 480px) {
            .profile-container {
                padding: 10px;
            }
            
            .profile-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                padding: 12px;
                margin-bottom: 20px;
            }
            
            .profile-header h1 {
                font-size: 1.3rem;
            }
            
            .back-btn {
                align-self: flex-start;
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .profile-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .profile-avatar-large {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .stat-item {
                padding: 15px;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 10px;
                font-size: 0.9rem;
            }
            
            .update-btn {
                padding: 12px 24px;
                font-size: 0.9rem;
            }
            
            .retake-assessment-btn {
                padding: 10px 20px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 768px) and (min-width: 481px) {
            .profile-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <a href="dashboard.php" class="back-btn">
                <span>←</span> Back to Dashboard
            </a>
            <h1>👤 My Profile</h1>
        </div>
        
        <!-- Profile Card -->
        <div class="profile-card">
            <!-- Avatar Section -->
            <div class="profile-avatar-section">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($username, 0, 1)); ?>
                </div>
                <h2><?php echo htmlspecialchars($username); ?></h2>
                <p style="color: #565656;">Member since <?php echo date('M Y', strtotime($profile_data['created_at'] ?? 'now')); ?></p>
            </div>
            
            <!-- Stats Section -->
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value"><?php echo $latest_assessment['score'] ?? 0; ?></div>
                    <div class="stat-label">Health Score</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $profile_data['height'] ?? '--'; ?></div>
                    <div class="stat-label">Height (cm)</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $profile_data['weight'] ?? '--'; ?></div>
                    <div class="stat-label">Weight (kg)</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php echo $profile_data['blood_group'] ?? '--'; ?></div>
                    <div class="stat-label">Blood Group</div>
                </div>
            </div>
            
            <?php if ($latest_assessment): ?>
                <div style="text-align: center;">
                    <p style="color: #565656; margin-bottom: 10px;">
                        Last assessment: <?php echo date('M j, Y', strtotime($latest_assessment['assessment_date'])); ?>
                    </p>
                    <a href="path_selection.php?retake=true" class="retake-assessment-btn">Retake Assessment</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Edit Profile Form -->
        <div class="profile-card">
            <div class="section-title">
                <span>✏️</span>
                <span>Edit Profile</span>
            </div>
            
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-section">
                    <h3 style="margin-bottom: 20px; color: #565656;">Basic Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($profile_data['email'] ?? ''); ?>" class="readonly-field" readonly>
                            <small style="color: #565656;">Email cannot be changed</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3 style="margin-bottom: 20px; color: #565656;">Health Information</h3>
                    <p style="color: #565656; margin-bottom: 20px;">
                        To update your health information, please retake the health assessment.
                    </p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="height">Height (cm)</label>
                            <input type="text" id="height" value="<?php echo htmlspecialchars($profile_data['height'] ?? 'Not set'); ?>" class="readonly-field" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="text" id="weight" value="<?php echo htmlspecialchars($profile_data['weight'] ?? 'Not set'); ?>" class="readonly-field" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="blood_group">Blood Group</label>
                            <input type="text" id="blood_group" value="<?php echo htmlspecialchars($profile_data['blood_group'] ?? 'Not set'); ?>" class="readonly-field" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="medications">Current Medications</label>
                        <textarea id="medications" rows="3" class="readonly-field" readonly><?php echo htmlspecialchars($profile_data['medications'] ?? 'Not set'); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="supplements">Supplements</label>
                        <textarea id="supplements" rows="3" class="readonly-field" readonly><?php echo htmlspecialchars($profile_data['supplements'] ?? 'Not set'); ?></textarea>
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="update-btn">
                    Update Profile
                </button>
            </form>
        </div>
    </div>
</body>
</html>
