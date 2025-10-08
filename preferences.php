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

// Handle form submission
if ($_POST && isset($_POST['save_preferences'])) {
    // Here you would save preferences to database
    $success_message = "Preferences saved successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Preferences</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .preferences-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .preferences-header {
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
        
        .preferences-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
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
        
        .preference-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .preference-item:last-child {
            border-bottom: none;
        }
        
        .preference-label {
            font-weight: 500;
            color: #333;
        }
        
        .preference-description {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .toggle-switch {
            width: 50px;
            height: 26px;
            background-color: #E5E8EB;
            border-radius: 13px;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .toggle-switch.active {
            background-color: #2E86C1;
        }
        
        .toggle-slider {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: white;
            position: absolute;
            top: 3px;
            left: 3px;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .toggle-switch.active .toggle-slider {
            transform: translateX(24px);
        }
        
        .select-preference {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            background: white;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }
        
        .select-preference:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .save-btn {
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
        
        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        @media (max-width: 768px) {
            .preferences-container {
                padding: 10px;
            }
            
            .preferences-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .preferences-card {
                padding: 20px;
            }
            
            .preference-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="preferences-container">
        <!-- Header -->
        <div class="preferences-header">
            <a href="user_profile.php" class="back-btn">
                <span>←</span> Back to Profile
            </a>
            <h1>⚙️ Preferences</h1>
        </div>
        
        <!-- Preferences Card -->
        <div class="preferences-card">
            <?php if ($success_message): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <!-- Notifications Section -->
                <div class="section-title">
                    <span>🔔</span>
                    <span>Notifications</span>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Daily Reminders</div>
                        <div class="preference-description">Get daily reminders for health assessments</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Weekly Reports</div>
                        <div class="preference-description">Receive weekly health progress reports</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Achievement Notifications</div>
                        <div class="preference-description">Get notified when you reach health milestones</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <!-- Privacy Section -->
                <div class="section-title" style="margin-top: 40px;">
                    <span>🔒</span>
                    <span>Privacy</span>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Share Progress with Family</div>
                        <div class="preference-description">Allow family members to see your health progress</div>
                    </div>
                    <div class="toggle-switch active" onclick="toggleSwitch(this)">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Public Leaderboard</div>
                        <div class="preference-description">Show your progress on community leaderboard</div>
                    </div>
                    <div class="toggle-switch" onclick="toggleSwitch(this)">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
                
                <!-- App Settings Section -->
                <div class="section-title" style="margin-top: 40px;">
                    <span>⚙️</span>
                    <span>App Settings</span>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Theme</div>
                        <div class="preference-description">Choose your preferred app theme</div>
                    </div>
                    <select class="select-preference">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="auto">Auto</option>
                    </select>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Language</div>
                        <div class="preference-description">Select your preferred language</div>
                    </div>
                    <select class="select-preference">
                        <option value="en">English</option>
                        <option value="hi">Hindi</option>
                        <option value="es">Spanish</option>
                    </select>
                </div>
                
                <div class="preference-item">
                    <div>
                        <div class="preference-label">Units</div>
                        <div class="preference-description">Choose measurement units</div>
                    </div>
                    <select class="select-preference">
                        <option value="metric">Metric (kg, cm)</option>
                        <option value="imperial">Imperial (lbs, ft)</option>
                    </select>
                </div>
                
                <button type="submit" name="save_preferences" class="save-btn" style="margin-top: 30px;">
                    Save Preferences
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function toggleSwitch(element) {
            element.classList.toggle('active');
        }
    </script>
</body>
</html>
