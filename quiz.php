<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get path type from POST or session
$path_type = '';
if (isset($_POST['path_type'])) {
    $path_type = $_POST['path_type'];
    $_SESSION['selected_path'] = $path_type;
} elseif (isset($_SESSION['selected_path'])) {
    $path_type = $_SESSION['selected_path'];
} else {
    // If no path selected, redirect to path selection
    header('Location: path_selection.php');
    exit();
}

// Handle quiz form submission
if ($_POST && isset($_POST['quiz_data'])) {
    require_once 'config/database.php';
    
    $quiz_data = json_decode($_POST['quiz_data'], true);
    $user_id = $_SESSION['user_id'];
    $is_child_mode = isset($_POST['is_child_mode']) && $_POST['is_child_mode'] === 'true';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Start transaction
        $db->beginTransaction();
        
        // Update user focus based on selected path
        $focus_query = "UPDATE users SET user_focus = :user_focus WHERE id = :user_id";
        $focus_stmt = $db->prepare($focus_query);
        $focus_stmt->bindParam(':user_focus', $path_type);
        $focus_stmt->bindParam(':user_id', $user_id);
        $focus_stmt->execute();
        
        // Insert/Update user profile (with child_mode flag)
        $profile_query = "INSERT INTO user_profile (user_id, height, weight, blood_group, medications, supplements, bed_time, wake_time, diet_vegetarian, diet_vegan, diet_gluten_free, diet_keto, diet_other, stress_level, mood_level, is_child_profile) 
                         VALUES (:user_id, :height, :weight, :blood_group, :medications, :supplements, :bed_time, :wake_time, :diet_vegetarian, :diet_vegan, :diet_gluten_free, :diet_keto, :diet_other, :stress_level, :mood_level, :is_child_profile)
                         ON DUPLICATE KEY UPDATE 
                         height = VALUES(height), weight = VALUES(weight), blood_group = VALUES(blood_group),
                         medications = VALUES(medications), supplements = VALUES(supplements),
                         bed_time = VALUES(bed_time), wake_time = VALUES(wake_time),
                         diet_vegetarian = VALUES(diet_vegetarian), diet_vegan = VALUES(diet_vegan),
                         diet_gluten_free = VALUES(diet_gluten_free), diet_keto = VALUES(diet_keto), diet_other = VALUES(diet_other),
                         stress_level = VALUES(stress_level), mood_level = VALUES(mood_level),
                         is_child_profile = VALUES(is_child_profile)";
        
        $stmt = $db->prepare($profile_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':height', $quiz_data['height']);
        $stmt->bindParam(':weight', $quiz_data['weight']);
        $stmt->bindParam(':blood_group', $quiz_data['blood_group']);
        $stmt->bindParam(':medications', $quiz_data['medications']);
        $stmt->bindParam(':supplements', $quiz_data['supplements']);
        $stmt->bindParam(':bed_time', $quiz_data['bed_time']);
        $stmt->bindParam(':wake_time', $quiz_data['wake_time']);
        $stmt->bindParam(':diet_vegetarian', $quiz_data['diet_vegetarian']);
        $stmt->bindParam(':diet_vegan', $quiz_data['diet_vegan']);
        $stmt->bindParam(':diet_gluten_free', $quiz_data['diet_gluten_free']);
        $stmt->bindParam(':diet_keto', $quiz_data['diet_keto']);
        $stmt->bindParam(':diet_other', $quiz_data['diet_other']);
        $stmt->bindParam(':stress_level', $quiz_data['stress_level']);
        $stmt->bindParam(':mood_level', $quiz_data['mood_level']);
        $stmt->bindParam(':is_child_profile', $is_child_mode, PDO::PARAM_BOOL);
        $stmt->execute();
        
        // Clear existing medical conditions and lifestyle preferences
        $db->prepare("DELETE FROM user_medical_conditions WHERE user_id = ?")->execute([$user_id]);
        $db->prepare("DELETE FROM user_lifestyle_preferences WHERE user_id = ?")->execute([$user_id]);
        
        // Insert medical conditions
        if (!empty($quiz_data['medical_conditions'])) {
            $condition_query = "INSERT INTO user_medical_conditions (user_id, condition_name) VALUES (?, ?)";
            $condition_stmt = $db->prepare($condition_query);
            foreach ($quiz_data['medical_conditions'] as $condition) {
                $condition_stmt->execute([$user_id, $condition]);
            }
        }
        
        // Insert lifestyle preferences
        if (!empty($quiz_data['lifestyle_preferences'])) {
            $lifestyle_query = "INSERT INTO user_lifestyle_preferences (user_id, preference_name) VALUES (?, ?)";
            $lifestyle_stmt = $db->prepare($lifestyle_query);
            foreach ($quiz_data['lifestyle_preferences'] as $preference) {
                $lifestyle_stmt->execute([$user_id, $preference]);
            }
        }
        
        $db->commit();
        
        // Store assessment data in session for scoring
        $session_key = $is_child_mode ? 'child_assessment_data' : 'assessment_data';
        $_SESSION[$session_key] = [
            'height' => $quiz_data['height'],
            'weight' => $quiz_data['weight'],
            'conditions' => $quiz_data['medical_conditions'] ?? [],
            'medications' => $quiz_data['medications'] ?? 'none',
            'sleep_hours' => calculateSleepHours($quiz_data['bed_time'], $quiz_data['wake_time']),
            'diet_quality' => determineDietQuality($quiz_data),
            'stress_level' => mapStressLevel($quiz_data['stress_level']),
            'activity_level' => determineActivityLevel($quiz_data['physical_activities'] ?? $quiz_data['lifestyle_preferences'] ?? []),
            'physical_activities' => $quiz_data['physical_activities'] ?? [],
            'is_child_profile' => $is_child_mode,
            'path_type' => $path_type
        ];
        
        // Redirect to assessment results page
        header('Location: assessment_results.php');
        exit();
        
    } catch (Exception $e) {
        $db->rollback();
        $error_message = "Failed to save quiz data. Please try again.";
    }
}

$username = $_SESSION['username'] ?? 'User';

// Helper functions for assessment data processing
function calculateSleepHours($bed_time, $wake_time) {
    if (!$bed_time || !$wake_time) return 8; // Default
    
    $bed = new DateTime($bed_time);
    $wake = new DateTime($wake_time);
    
    // If wake time is before bed time, add a day
    if ($wake < $bed) {
        $wake->add(new DateInterval('P1D'));
    }
    
    $diff = $bed->diff($wake);
    return $diff->h + ($diff->i / 60);
}

function determineDietQuality($quiz_data) {
    $score = 0;
    
    // Check dietary preferences for health indicators
    $dietary_prefs = $quiz_data['dietary_preferences'] ?? [];
    
    if (in_array('vegetarian', $dietary_prefs) || in_array('vegan', $dietary_prefs)) {
        $score += 2; // Plant-based diets often healthier
    }
    
    if (in_array('gluten_free', $dietary_prefs)) {
        $score += 1; // Gluten-free diet
    }
    
    if (in_array('keto', $dietary_prefs)) {
        $score += 1; // Keto diet can be beneficial
    }
    
    // Check for specific health conditions in other dietary preferences
    $diet_other = $quiz_data['diet_other'] ?? '';
    if (!empty($diet_other) && (strpos(strtolower($diet_other), 'celiac') !== false || 
                                strpos(strtolower($diet_other), 'diabetes') !== false)) {
        $score += 1; // Necessary dietary restriction for health
    }
    
    // Map to quality levels
    if ($score >= 4) return 'excellent';
    if ($score >= 3) return 'very_good';
    if ($score >= 2) return 'good';
    if ($score >= 1) return 'fair';
    return 'poor';
}

function mapStressLevel($stress_level) {
    if ($stress_level <= 2) return 'low';
    if ($stress_level <= 5) return 'moderate';
    if ($stress_level <= 8) return 'high';
    return 'very_high';
}

function determineActivityLevel($lifestyle_preferences) {
    // Check if we have the new physical_activities data
    $activities = $lifestyle_preferences;
    
    // Define activity intensity levels
    $high_intensity = ['gym', 'running', 'gymnastic', 'swimming'];
    $medium_intensity = ['cycling', 'yoga', 'skipping'];
    $low_intensity = ['walk'];
    
    $activity_score = 0;
    
    foreach ($activities as $activity) {
        if (in_array($activity, $high_intensity)) {
            $activity_score += 3;
        } elseif (in_array($activity, $medium_intensity)) {
            $activity_score += 2;
        } elseif (in_array($activity, $low_intensity)) {
            $activity_score += 1;
        }
    }
    
    // Determine activity level based on score
    if ($activity_score >= 8) return 'very_active';
    if ($activity_score >= 5) return 'active';
    if ($activity_score >= 2) return 'moderate';
    if ($activity_score >= 1) return 'lightly_active';
    return 'sedentary';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Health Assessment</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .header-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .toggle-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        
        .toggle-switch {
            width: 50px;
            height: 26px;
            background: #e0e0e0;
            border-radius: 13px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #ddd;
        }
        
        .toggle-switch:hover {
            border-color: #667eea;
        }
        
        .toggle-switch.active {
            background: #667eea;
            border-color: #667eea;
        }
        
        .toggle-slider {
            width: 20px;
            height: 20px;
            background: white;
            border-radius: 50%;
            position: absolute;
            top: 1px;
            left: 2px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .toggle-switch.active .toggle-slider {
            transform: translateX(24px);
        }
        
        .toggle-label {
            font-size: 0.8rem;
            color: #666;
            font-weight: 500;
        }
        
        .toggle-switch.active + .toggle-label {
            color: #667eea;
            font-weight: 600;
        }
        
        .mode-indicator {
            margin: 10px 0;
        }
        
        .mode-badge {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .quiz-header {
            position: relative;
        }
        
        /* Physical Activities Grid Styles */
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            padding: 20px 0;
            max-width: 500px;
            margin: 0 auto;
        }
        
        .activity-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover .activity-circle {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .activity-item.selected .activity-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }
        
        .activity-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f8f9fa;
            border: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .activity-icon {
            font-size: 2rem;
            line-height: 1;
        }
        
        .activity-label {
            font-size: 0.9rem;
            font-weight: 500;
            color: #333;
            text-align: center;
            transition: color 0.3s ease;
        }
        
        .activity-item.selected .activity-label {
            color: #667eea;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .header-controls {
                position: static;
                justify-content: center;
                margin-bottom: 15px;
            }
            
            .toggle-container {
                flex-direction: row;
                gap: 10px;
            }
            
            .activities-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 20px;
                max-width: 350px;
            }
            
            .activity-circle {
                width: 70px;
                height: 70px;
            }
            
            .activity-icon {
                font-size: 1.8rem;
            }
            
            .activity-label {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .activities-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                max-width: 280px;
            }
            
            .activity-circle {
                width: 60px;
                height: 60px;
            }
            
            .activity-icon {
                font-size: 1.5rem;
            }
        }
        
        /* Lifestyle Preferences Styles */
        .lifestyle-preferences {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .lifestyle-option-btn {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-align: left;
        }
        
        .lifestyle-option-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }
        
        .lifestyle-option-btn.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .option-content {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .option-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .lifestyle-option-btn.selected .option-icon {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .option-text {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
        }
        
        .lifestyle-option-btn.selected .option-text {
            color: white;
        }
        
        @media (max-width: 768px) {
            .lifestyle-preferences {
                max-width: 100%;
            }
            
            .lifestyle-option-btn {
                padding: 15px;
            }
            
            .option-content {
                gap: 12px;
            }
            
            .option-icon {
                width: 40px;
                height: 40px;
                font-size: 1.5rem;
            }
            
            .option-text {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="quiz-page">
    <div class="user-info">
        Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="quiz-container">
        <div class="quiz-header">
            <div class="header-controls">
                <div class="toggle-container">
                    <div class="toggle-switch" onclick="toggleChildMode()" title="Child Mode - Assessment for Child">
                        <div class="toggle-slider"></div>
                    </div>
                    <span class="toggle-label" id="childToggleLabel">Child Mode</span>
                </div>
            </div>
            <h1 id="assessmentTitle"><?php echo $path_type === 'medical' ? 'Medical Condition Assessment' : 'Lifestyle Improvement Assessment'; ?></h1>
            <p id="assessmentSubtitle"><?php echo $path_type === 'medical' ? 'Complete this assessment to get personalized recommendations for managing your health conditions' : 'Complete this assessment to get personalized recommendations for improving your overall lifestyle'; ?></p>
            <div class="path-indicator" style="margin: 15px 0;">
                <span class="path-badge" style="background: linear-gradient(135deg, #2E86C1, #3498DB); color: white; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600;">
                    📋 <?php echo $path_type === 'medical' ? 'Medical Focus' : 'Lifestyle Focus'; ?>
                </span>
            </div>
            <div class="mode-indicator" id="childModeIndicator" style="display: none;">
                <span class="mode-badge">👶 Assessment for Child</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <p class="step-indicator" id="stepIndicator">Step 1 of 7</p>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message" style="text-align: center; max-width: 600px; margin: 0 auto 30px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="quiz-card">
            <form id="quizForm" method="POST">
                <input type="hidden" name="quiz_data" id="quizDataInput">
                <input type="hidden" name="is_child_mode" id="isChildModeInput" value="false">
                <input type="hidden" name="path_type" value="<?php echo htmlspecialchars($path_type); ?>">
                
                <!-- Step 3a: Personal Info -->
                <div class="step active" id="step-3a">
                    <div class="step-header">
                        <h2>Personal Information</h2>
                        <p>Let's start with some basic information about you</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="height">Height (cm)</label>
                        <input type="number" id="height" name="height" min="50" max="250" step="0.1" required>
                        <span class="error-text" id="heightError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input type="number" id="weight" name="weight" min="2" max="300" step="0.1" required>
                        <span class="error-text" id="weightError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="blood_group">Blood Group</label>
                        <select id="blood_group" name="blood_group" required>
                            <option value="">Select your blood group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                        <span class="error-text" id="bloodGroupError"></span>
                    </div>
                </div>
                
                <!-- Step 3b: Medical Conditions -->
                <div class="step" id="step-3b">
                    <div class="step-header">
                        <h2>Medical Conditions</h2>
                        <p>Select any medical conditions that apply to you</p>
                    </div>
                    
                    <div class="button-group" id="medicalConditions">
                        <button type="button" class="option-btn" data-value="diabetes">Diabetes</button>
                        <button type="button" class="option-btn" data-value="hypertension">Hypertension</button>
                        <button type="button" class="option-btn" data-value="thyroid">Thyroid</button>
                        <button type="button" class="option-btn" data-value="asthma">Asthma</button>
                        <button type="button" class="option-btn" data-value="heart">Heart Disease</button>
                        <button type="button" class="option-btn" data-value="other">Other</button>
                        <button type="button" class="option-btn exclusive" data-value="none">None</button>
                    </div>
                    
                    <div class="form-group" id="otherConditionGroup" style="display: none;">
                        <label for="otherCondition">Please specify other condition:</label>
                        <input type="text" id="otherCondition" name="otherCondition">
                    </div>
                </div>
                
                <!-- Step 3c: Medications/Supplements -->
                <div class="step" id="step-3c">
                    <div class="step-header">
                        <h2>Medications & Supplements</h2>
                        <p>Tell us about any medications or supplements you're taking</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="medications">Current Medications</label>
                        <textarea id="medications" name="medications" rows="3" placeholder="List any medications you're currently taking (optional)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="supplements">Supplements & Vitamins</label>
                        <textarea id="supplements" name="supplements" rows="3" placeholder="List any supplements or vitamins you take (optional)"></textarea>
                    </div>
                </div>
                
                <!-- Step 3d: Sleep Pattern -->
                <div class="step" id="step-3d">
                    <div class="step-header">
                        <h2>Sleep Pattern</h2>
                        <p>Help us understand your sleep schedule</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="bed_time">Usual Bedtime</label>
                        <input type="time" id="bed_time" name="bed_time" required>
                        <span class="error-text" id="bedTimeError"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="wake_time">Usual Wake Time</label>
                        <input type="time" id="wake_time" name="wake_time" required>
                        <span class="error-text" id="wakeTimeError"></span>
                    </div>
                </div>
                
                <!-- Step 3e: Diet -->
                <div class="step" id="step-3e">
                    <div class="step-header">
                        <h2>Dietary Preferences</h2>
                        <p>Tell us about your dietary habits and restrictions</p>
                    </div>
                    
                    <div class="button-group" id="dietaryPreferences">
                        <button type="button" class="option-btn" data-value="vegetarian">Vegetarian</button>
                        <button type="button" class="option-btn" data-value="vegan">Vegan</button>
                        <button type="button" class="option-btn" data-value="gluten_free">Gluten-Free</button>
                        <button type="button" class="option-btn" data-value="keto">Keto/Low-Carb</button>
                        <button type="button" class="option-btn" data-value="other">Other</button>
                    </div>
                    
                    <div class="form-group" id="otherDietGroup" style="display: none;">
                        <label for="otherDiet">Please specify other dietary preference:</label>
                        <input type="text" id="otherDiet" name="otherDiet" placeholder="Enter your specific dietary preference or restriction">
                    </div>
                </div>
                
                <!-- Step 3f: Stress & Mood -->
                <div class="step" id="step-3f">
                    <div class="step-header">
                        <h2>Daily Stress & Mood</h2>
                        <p>Help us understand your typical stress and mood levels</p>
                    </div>
                    
                    <div class="slider-group">
                        <div class="slider-item">
                            <label for="stress_level">Daily Stress Level</label>
                            <div class="slider-container">
                                <span class="slider-label">Low</span>
                                <input type="range" id="stress_level" name="stress_level" min="1" max="10" value="5">
                                <span class="slider-label">High</span>
                            </div>
                            <div class="slider-value" id="stressValue">5</div>
                        </div>
                        
                        <div class="slider-item">
                            <label for="mood_level">Overall Mood</label>
                            <div class="slider-container">
                                <span class="slider-label">Low</span>
                                <input type="range" id="mood_level" name="mood_level" min="1" max="10" value="5">
                                <span class="slider-label">High</span>
                            </div>
                            <div class="slider-value" id="moodValue">5</div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3g: Lifestyle Preferences -->
                <div class="step" id="step-3g">
                    <div class="step-header">
                        <h2>Lifestyle and Activity Preferences</h2>
                        <p>What areas would you like to focus on for improvement?</p>
                    </div>
                    
                    <div class="button-group lifestyle-preferences" id="lifestylePreferences">
                        <button type="button" class="lifestyle-option-btn" data-value="exercise">
                            <div class="option-content">
                                <div class="option-icon">🏃‍♂️</div>
                                <div class="option-text">Exercise</div>
                            </div>
                        </button>
                        <button type="button" class="lifestyle-option-btn" data-value="mindfulness">
                            <div class="option-content">
                                <div class="option-icon">🧘‍♀️</div>
                                <div class="option-text">Mindfulness techniques</div>
                            </div>
                        </button>
                        <button type="button" class="lifestyle-option-btn" data-value="weight_management">
                            <div class="option-content">
                                <div class="option-icon">⚖️</div>
                                <div class="option-text">Weight Loss/Gain</div>
                            </div>
                        </button>
                    </div>
                </div>
                
                <!-- Navigation Buttons -->
                <div class="quiz-navigation">
                    <button type="button" id="backBtn" class="nav-btn secondary" style="display: none;">Back</button>
                    <button type="button" id="nextBtn" class="nav-btn primary">Next</button>
                    <button type="submit" id="submitBtn" class="nav-btn primary" style="display: none;">Complete Assessment</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/quiz.js"></script>
    <script>
        // Child mode functionality
        function toggleChildMode() {
            const toggleSwitch = document.querySelector('.toggle-switch');
            const modeIndicator = document.getElementById('childModeIndicator');
            const assessmentTitle = document.getElementById('assessmentTitle');
            const assessmentSubtitle = document.getElementById('assessmentSubtitle');
            const isChildModeInput = document.getElementById('isChildModeInput');
            
            toggleSwitch.classList.toggle('active');
            
            const isChildMode = toggleSwitch.classList.contains('active');
            
            if (isChildMode) {
                // Switch to child mode
                modeIndicator.style.display = 'block';
                assessmentTitle.textContent = "Child Health Assessment";
                assessmentSubtitle.textContent = "Complete this assessment to get personalized health recommendations for your child";
                isChildModeInput.value = 'true';
                
                // Update form labels and placeholders for child
                updateFormForChild();
            } else {
                // Switch back to personal mode
                modeIndicator.style.display = 'none';
                assessmentTitle.textContent = "Health Assessment";
                assessmentSubtitle.textContent = "Complete this assessment to get personalized health recommendations";
                isChildModeInput.value = 'false';
                
                // Update form labels and placeholders for self
                updateFormForSelf();
            }
            
            // Save preference to localStorage
            localStorage.setItem('childModeQuiz', isChildMode);
        }
        
        // Update form for child mode
        function updateFormForChild() {
            // Update step headers
            const stepHeaders = document.querySelectorAll('.step-header h2');
            stepHeaders.forEach(header => {
                if (header.textContent === 'Personal Information') {
                    header.nextElementSibling.textContent = "Let's start with some basic information about your child";
                } else if (header.textContent === 'Medical Conditions') {
                    header.nextElementSibling.textContent = "Select any medical conditions that apply to your child";
                } else if (header.textContent === 'Medications & Supplements') {
                    header.nextElementSibling.textContent = "Tell us about your child's medications and supplements";
                } else if (header.textContent === 'Sleep Pattern') {
                    header.nextElementSibling.textContent = "Help us understand your child's sleep schedule";
                } else if (header.textContent === 'Dietary Preferences') {
                    header.nextElementSibling.textContent = "Tell us about your child's dietary preferences";
                } else if (header.textContent === 'Daily Stress & Mood') {
                    header.nextElementSibling.textContent = "How would you rate your child's stress and mood levels?";
                } else if (header.textContent === 'Lifestyle & Activity Preferences') {
                    header.nextElementSibling.textContent = "What areas would you like to focus on for your child's improvement?";
                }
            });
            
            // Update form labels
            const labels = document.querySelectorAll('label');
            labels.forEach(label => {
                const text = label.textContent;
                if (text.includes('your ')) {
                    label.textContent = text.replace('your ', "your child's ");
                } else if (text.includes('you ')) {
                    label.textContent = text.replace('you ', "your child ");
                }
            });
            
            // Update placeholders
            const inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                if (input.placeholder) {
                    input.placeholder = input.placeholder.replace(/you /g, 'your child ').replace(/your /g, "your child's ");
                }
            });
            
            // Adjust height and weight limits for children
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            if (heightInput) {
                heightInput.min = '30'; // Newborn minimum
                heightInput.max = '200'; // Teen maximum
            }
            if (weightInput) {
                weightInput.min = '1'; // Newborn minimum
                weightInput.max = '150'; // Teen maximum
            }
        }
        
        // Update form for self mode
        function updateFormForSelf() {
            // Restore adult height and weight limits
            const heightInput = document.getElementById('height');
            const weightInput = document.getElementById('weight');
            if (heightInput) {
                heightInput.min = '50'; // Adult minimum
                heightInput.max = '250'; // Adult maximum
            }
            if (weightInput) {
                weightInput.min = '2'; // Adult minimum
                weightInput.max = '300'; // Adult maximum
            }
            
            // Restore original text - reload to reset form labels
            location.reload(); // Simple approach - reload to reset form
        }
        
        // Load child mode preference on page load
        function loadChildModePreference() {
            const isChildMode = localStorage.getItem('childModeQuiz') === 'true';
            if (isChildMode) {
                toggleChildMode();
            }
        }
        
        // Initialize dietary preference toggles
        function initializeDietaryToggles() {
            const toggleLabels = document.querySelectorAll('.toggle-label');
            toggleLabels.forEach(label => {
                label.addEventListener('click', function(e) {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        // Trigger change event for form validation
                        checkbox.dispatchEvent(new Event('change'));
                    }
                });
            });
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadChildModePreference();
            initializeDietaryToggles();
        });
    </script>
</body>
</html>
