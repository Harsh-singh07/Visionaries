<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$score = 0;
$tips = '';
$assessment_data = [];

// Check for both adult and child assessment data
$is_child_assessment = false;
if (isset($_SESSION['child_assessment_data'])) {
    $assessment_data = $_SESSION['child_assessment_data'];
    $is_child_assessment = true;
    unset($_SESSION['child_assessment_data']);
} elseif (isset($_SESSION['assessment_data'])) {
    $assessment_data = $_SESSION['assessment_data'];
    unset($_SESSION['assessment_data']);
} else {
    // Redirect back to quiz if no assessment data
    header('Location: quiz.php');
    exit();
}

// Calculate score and generate tips
$score = calculateWellnessScore($assessment_data, $is_child_assessment);
$tips = generateTips($score, $assessment_data, $is_child_assessment);

// Save assessment to database
saveAssessmentToDB($user_id, $score, $assessment_data, $is_child_assessment);

function calculateWellnessScore($data, $is_child = false) {
    $score = 0;
    
    // BMI Score (15 points max) - Different ranges for children
    if (isset($data['height']) && isset($data['weight'])) {
        $height_m = $data['height'] / 100; // Convert cm to meters
        $bmi = $data['weight'] / ($height_m * $height_m);
        
        if ($is_child) {
            // Child BMI ranges are more flexible
            if ($bmi >= 14 && $bmi <= 25) {
                $score += 15; // Healthy BMI for child
            } elseif ($bmi >= 12 && $bmi < 14 || $bmi > 25 && $bmi <= 30) {
                $score += 10; // Slightly outside healthy range
            } else {
                $score += 5; // Outside healthy range
            }
        } else {
            // Adult BMI ranges
            if ($bmi >= 18.5 && $bmi <= 24.9) {
                $score += 15; // Healthy BMI
            } elseif ($bmi >= 17 && $bmi < 18.5 || $bmi >= 25 && $bmi <= 29.9) {
                $score += 10; // Slightly outside healthy range
            } else {
                $score += 5; // Outside healthy range
            }
        }
    }
    
    // Health Conditions (15 points max)
    $conditions = $data['conditions'] ?? [];
    if (empty($conditions) || (count($conditions) == 1 && $conditions[0] == 'none')) {
        $score += 15; // No conditions
    } elseif (count($conditions) <= 2) {
        $score += 10; // Few conditions
    } else {
        $score += 5; // Multiple conditions
    }
    
    // Medications (5 points max)
    $medications = $data['medications'] ?? 'none';
    if ($medications == 'none' || $medications == 'vitamins') {
        $score += 5;
    } else {
        $score += 2;
    }
    
    // Sleep (15 points max)
    $sleep_hours = floatval($data['sleep_hours'] ?? 0);
    if ($sleep_hours >= 7 && $sleep_hours <= 9) {
        $score += 15; // Optimal sleep
    } elseif ($sleep_hours >= 6 && $sleep_hours < 7 || $sleep_hours > 9 && $sleep_hours <= 10) {
        $score += 10; // Good sleep
    } else {
        $score += 5; // Poor sleep
    }
    
    // Diet (15 points max)
    $diet_quality = $data['diet_quality'] ?? '';
    switch ($diet_quality) {
        case 'excellent':
            $score += 15;
            break;
        case 'good':
            $score += 12;
            break;
        case 'fair':
            $score += 8;
            break;
        case 'poor':
            $score += 3;
            break;
    }
    
    // Mood/Stress (15 points max)
    $stress_level = $data['stress_level'] ?? '';
    switch ($stress_level) {
        case 'low':
            $score += 15;
            break;
        case 'moderate':
            $score += 10;
            break;
        case 'high':
            $score += 5;
            break;
        case 'very_high':
            $score += 2;
            break;
    }
    
    // Activity (20 points max)
    $activity_level = $data['activity_level'] ?? '';
    switch ($activity_level) {
        case 'very_active':
            $score += 20;
            break;
        case 'active':
            $score += 15;
            break;
        case 'moderate':
            $score += 10;
            break;
        case 'sedentary':
            $score += 5;
            break;
    }
    
    return min($score, 100); // Cap at 100
}

function generateTips($score, $data, $is_child = false) {
    $subject = $is_child ? "your child" : "you";
    $possessive = $is_child ? "your child's" : "your";
    
    if ($score >= 85) {
        return $is_child ? 
            "Excellent! Your child has a very healthy lifestyle. Keep up the great work!" :
            "Excellent! You have a very healthy lifestyle. Keep up the great work!";
    } elseif ($score >= 70) {
        $tips = $is_child ? 
            "Good! Your child has a balanced routine. " :
            "Good! You have a balanced routine. ";
        
        // Specific improvement suggestions
        $sleep_hours = floatval($data['sleep_hours'] ?? 0);
        $ideal_sleep = $is_child ? "9-11 hours" : "7-9 hours";
        $min_sleep = $is_child ? 9 : 7;
        $max_sleep = $is_child ? 11 : 9;
        
        if ($sleep_hours < $min_sleep || $sleep_hours > $max_sleep) {
            $tips .= "Focus on getting {$ideal_sleep} of sleep for better recovery.";
        } elseif (($data['stress_level'] ?? '') == 'high') {
            $tips .= "Consider stress management techniques like meditation or yoga.";
        } else {
            $tips .= "Small improvements in diet and exercise can boost your score further.";
        }
        
        return $tips;
    } elseif ($score >= 50) {
        return "Fair. There's room for improvement in your health routine. Focus on better sleep, nutrition, and regular exercise.";
    } else {
        return "Your health routine needs attention. Consider consulting with healthcare professionals and making gradual lifestyle changes.";
    }
}

function saveAssessmentToDB($user_id, $score, $data, $is_child = false) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db === null) {
            return false;
        }
        
        // Create assessment table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS user_assessments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            score INT NOT NULL,
            assessment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            bmi DECIMAL(4,2),
            sleep_hours DECIMAL(3,1),
            diet_quality VARCHAR(20),
            stress_level VARCHAR(20),
            activity_level VARCHAR(20),
            conditions TEXT,
            medications VARCHAR(100),
            is_child_assessment BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $db->exec($createTable);
        
        // Add is_child_assessment column if it doesn't exist (for existing tables)
        try {
            $db->exec("ALTER TABLE user_assessments ADD COLUMN is_child_assessment BOOLEAN DEFAULT FALSE");
        } catch (Exception $e) {
            // Column already exists, ignore error
        }
        
        // Calculate BMI
        $bmi = null;
        if (isset($data['height']) && isset($data['weight'])) {
            $height_m = $data['height'] / 100;
            $bmi = $data['weight'] / ($height_m * $height_m);
        }
        
        // Prepare variables for binding
        $sleep_hours = $data['sleep_hours'] ?? null;
        $diet_quality = $data['diet_quality'] ?? null;
        $stress_level = $data['stress_level'] ?? null;
        $activity_level = $data['activity_level'] ?? null;
        $conditions = json_encode($data['conditions'] ?? []);
        $medications = $data['medications'] ?? null;
        
        // Insert assessment
        $query = "INSERT INTO user_assessments 
                  (user_id, score, bmi, sleep_hours, diet_quality, stress_level, activity_level, conditions, medications, is_child_assessment) 
                  VALUES (:user_id, :score, :bmi, :sleep_hours, :diet_quality, :stress_level, :activity_level, :conditions, :medications, :is_child_assessment)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':score', $score);
        $stmt->bindParam(':bmi', $bmi);
        $stmt->bindParam(':sleep_hours', $sleep_hours);
        $stmt->bindParam(':diet_quality', $diet_quality);
        $stmt->bindParam(':stress_level', $stress_level);
        $stmt->bindParam(':activity_level', $activity_level);
        $stmt->bindParam(':conditions', $conditions);
        $stmt->bindParam(':medications', $medications);
        $stmt->bindParam(':is_child_assessment', $is_child, PDO::PARAM_BOOL);
        
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Assessment save error: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Results - HealthFirst</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .results-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .results-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .results-header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.2rem;
        }
        
        .results-header p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .score-circle {
            width: 200px;
            height: 200px;
            margin: 0 auto 30px;
            position: relative;
        }
        
        .circle-progress {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: conic-gradient(#4CAF50 0deg, #4CAF50 var(--progress-deg), #e0e0e0 var(--progress-deg), #e0e0e0 360deg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transition: all 2s ease-in-out;
        }
        
        .circle-inner {
            width: 160px;
            height: 160px;
            background: white;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .score-number {
            font-size: 3rem;
            font-weight: 700;
            color: #333;
            margin: 0;
        }
        
        .score-label {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }
        
        .score-description {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #4CAF50;
        }
        
        .score-description p {
            margin: 0;
            color: #555;
            line-height: 1.6;
        }
        
        .continue-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .continue-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            .results-card {
                padding: 30px 20px;
            }
            
            .score-circle {
                width: 150px;
                height: 150px;
            }
            
            .circle-progress {
                width: 150px;
                height: 150px;
            }
            
            .circle-inner {
                width: 120px;
                height: 120px;
            }
            
            .score-number {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="results-container">
        <div class="results-card">
            <div class="results-header">
                <h1>Your Assessment Results</h1>
                <p>Based on your health and lifestyle responses</p>
            </div>
            
            <div class="score-circle">
                <div class="circle-progress" id="scoreCircle" style="--progress-deg: 0deg;">
                    <div class="circle-inner">
                        <div class="score-number" id="scoreNumber">0</div>
                        <div class="score-label">out of 100</div>
                    </div>
                </div>
            </div>
            
            <div class="score-description">
                <p><?php echo htmlspecialchars($tips); ?></p>
            </div>
            
            <a href="dashboard.php" class="continue-btn">View Your Dashboard</a>
        </div>
    </div>
    
    <script>
        // Animate score on page load
        document.addEventListener('DOMContentLoaded', function() {
            const targetScore = <?php echo $score; ?>;
            const scoreNumber = document.getElementById('scoreNumber');
            const scoreCircle = document.getElementById('scoreCircle');
            
            // Animate number counting
            let currentScore = 0;
            const increment = targetScore / 100; // 100 steps
            const timer = setInterval(() => {
                currentScore += increment;
                if (currentScore >= targetScore) {
                    currentScore = targetScore;
                    clearInterval(timer);
                }
                scoreNumber.textContent = Math.round(currentScore);
            }, 20);
            
            // Animate circle fill
            setTimeout(() => {
                const progressDeg = (targetScore / 100) * 360;
                scoreCircle.style.setProperty('--progress-deg', progressDeg + 'deg');
                
                // Change color based on score
                let color = '#4CAF50'; // Green for good scores
                if (targetScore < 50) {
                    color = '#f44336'; // Red for low scores
                } else if (targetScore < 70) {
                    color = '#ff9800'; // Orange for medium scores
                }
                
                scoreCircle.style.background = `conic-gradient(${color} 0deg, ${color} ${progressDeg}deg, #e0e0e0 ${progressDeg}deg, #e0e0e0 360deg)`;
            }, 500);
        });
    </script>
</body>
</html>
