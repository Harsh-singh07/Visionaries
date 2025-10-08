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
$family_members = [];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db !== null) {
        // Get current user data
        $user_query = "SELECT u.*, up.height, up.weight, up.blood_group 
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
    error_log("User profile error: " . $e->getMessage());
}

// Demo family members
$family_members = [
    ['name' => 'Mira Singh', 'relation' => 'Mother', 'avatar' => 'M'],
    ['name' => 'Ray Singh', 'relation' => 'Father', 'avatar' => 'R']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - User Profile</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .user-profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            gap: 20px;
        }
        
        .main-profile-section {
            flex: 1;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        
        .family-mode-toggle {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .close-btn:hover {
            background: #f0f0f0;
            color: #333;
        }
        
        .main-profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4rem;
            font-weight: 700;
            margin: 20px auto 20px;
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.3);
            border: 5px solid white;
        }
        
        .profile-name {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        
        .profile-info {
            color: #666;
            margin-bottom: 40px;
        }
        
        .family-section {
            margin-top: 40px;
        }
        
        .family-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 25px;
        }
        
        .family-members {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .family-member {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .family-member:hover {
            transform: translateY(-5px);
        }
        
        .family-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            border: 3px solid white;
        }
        
        .add-member {
            background: linear-gradient(135deg, #FFA726 0%, #FF9800 100%);
            box-shadow: 0 4px 15px rgba(255, 167, 38, 0.3);
        }
        
        .family-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
        }
        
        .family-relation {
            font-size: 0.8rem;
            color: #666;
        }
        
        .sidebar {
            width: 250px;
            background: white;
            border-radius: 20px;
            padding: 30px 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-item {
            margin-bottom: 15px;
        }
        
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            color: #333;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .sidebar-link:hover {
            background: #f0f4ff;
            color: #667eea;
            transform: translateX(5px);
        }
        
        .sidebar-icon {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .back-to-dashboard {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .back-to-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }
        
        @media (max-width: 768px) {
            .user-profile-container {
                flex-direction: column;
                padding: 10px;
                gap: 15px;
            }
            
            .main-profile-section {
                padding: 25px;
            }
            
            .main-profile-avatar {
                width: 120px;
                height: 120px;
                font-size: 3rem;
            }
            
            .profile-name {
                font-size: 1.5rem;
            }
            
            .family-members {
                gap: 20px;
            }
            
            .family-avatar {
                width: 70px;
                height: 70px;
                font-size: 1.5rem;
            }
            
            .sidebar {
                width: 100%;
                padding: 20px;
            }
            
            .family-mode-toggle {
                position: static;
                justify-content: center;
                margin-bottom: 20px;
            }
            
            .close-btn {
                top: 15px;
                right: 15px;
            }
            
            .back-to-dashboard {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .main-profile-section {
                padding: 20px;
            }
            
            .main-profile-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            
            .profile-name {
                font-size: 1.3rem;
            }
            
            .family-members {
                gap: 15px;
            }
            
            .family-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.3rem;
            }
            
            .family-name {
                font-size: 0.8rem;
            }
            
            .family-relation {
                font-size: 0.7rem;
            }
            
            .sidebar-link {
                padding: 12px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="user-profile-container">
        <!-- Main Profile Section -->
        <div class="main-profile-section">
            <div class="family-mode-toggle">
                <span>👨‍👩‍👧‍👦</span>
                <span>Family Mode</span>
            </div>
            
            <button class="close-btn" onclick="goToDashboard()">×</button>
            
            <div class="main-profile-avatar">
                <?php echo strtoupper(substr($username, 0, 1)); ?>
            </div>
            
            <h1 class="profile-name"><?php echo htmlspecialchars($username); ?></h1>
            <div class="profile-info">
                <p>Health Score: <?php echo $latest_assessment['score'] ?? 0; ?>/100</p>
                <p>Member since <?php echo date('M Y', strtotime($profile_data['created_at'] ?? 'now')); ?></p>
            </div>
            
            <!-- Family Section -->
            <div class="family-section">
                <h3 class="family-title">Family Members</h3>
                <div class="family-members">
                    <?php foreach ($family_members as $member): ?>
                        <div class="family-member" onclick="selectFamilyMember('<?php echo $member['name']; ?>')">
                            <div class="family-avatar">
                                <?php echo $member['avatar']; ?>
                            </div>
                            <div class="family-name"><?php echo htmlspecialchars($member['name']); ?></div>
                            <div class="family-relation"><?php echo $member['relation']; ?></div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Add Member Button -->
                    <div class="family-member" onclick="addFamilyMember()">
                        <div class="family-avatar add-member">
                            +
                        </div>
                        <div class="family-name">Add Member</div>
                        <div class="family-relation">Family</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="leaderboard.php" class="sidebar-link">
                        <span class="sidebar-icon">🏆</span>
                        <span>Board</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="assessment_results.php" class="sidebar-link">
                        <span class="sidebar-icon">ℹ️</span>
                        <span>Info</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="profile.php" class="sidebar-link">
                        <span class="sidebar-icon">✏️</span>
                        <span>Edit</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="preferences.php" class="sidebar-link">
                        <span class="sidebar-icon">⚙️</span>
                        <span>Preferences</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="progress_tracking.php" class="sidebar-link">
                        <span class="sidebar-icon">📊</span>
                        <span>Progress</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="logout.php" class="sidebar-link" style="color: #e74c3c;">
                        <span class="sidebar-icon">🚪</span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Floating Back Button -->
    <button class="back-to-dashboard" onclick="goToDashboard()" title="Back to Dashboard">
        🏠
    </button>
    
    <script>
        function goToDashboard() {
            window.location.href = 'dashboard.php';
        }
        
        function selectFamilyMember(name) {
            alert('Selected family member: ' + name + '\nFamily member profiles coming soon!');
        }
        
        function addFamilyMember() {
            alert('Add family member feature coming soon!');
        }
        
    </script>
</body>
</html>
