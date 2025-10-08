<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get score from URL or database
$score = isset($_GET['score']) ? intval($_GET['score']) : 0;

if ($score == 0) {
    // Try to get latest score from database
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db !== null) {
            $query = "SELECT score FROM user_assessments 
                      WHERE user_id = :user_id 
                      ORDER BY assessment_date DESC LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $score = $result['score'];
            }
        }
    } catch (Exception $e) {
        error_log("Diet recommendations error: " . $e->getMessage());
    }
}

// Generate diet recommendations based on score
function getDietRecommendations($score) {
    $recommendations = [];
    
    if ($score >= 85) {
        // Excellent score (85-100)
        $recommendations = [
            'category' => 'Maintenance Diet',
            'title' => 'Excellent Health - Maintain Your Great Habits!',
            'description' => 'Your wellness score is outstanding! Keep up your current healthy eating patterns.',
            'main_foods' => [
                'Continue your balanced whole food diet',
                'Maintain variety in fruits and vegetables',
                'Keep up your lean protein intake',
                'Continue healthy fats from nuts, seeds, and fish'
            ],
            'meal_plan' => [
                'breakfast' => 'Greek yogurt with berries and nuts, or oatmeal with fruits',
                'lunch' => 'Quinoa salad with vegetables and grilled chicken/tofu',
                'dinner' => 'Baked fish with roasted vegetables and brown rice',
                'snacks' => 'Mixed nuts, fruits, or vegetable sticks with hummus'
            ],
            'avoid' => [
                'Processed foods (continue limiting)',
                'Excessive sugar',
                'Trans fats'
            ],
            'tips' => [
                'Stay hydrated with 8-10 glasses of water daily',
                'Continue meal prepping for consistency',
                'Listen to your body\'s hunger cues',
                'Enjoy occasional treats in moderation'
            ]
        ];
    } elseif ($score >= 70) {
        // Good score (70-84)
        $recommendations = [
            'category' => 'Optimization Diet',
            'title' => 'Good Health - Let\'s Optimize Further!',
            'description' => 'You\'re doing well! Small improvements can boost your wellness score even higher.',
            'main_foods' => [
                'Increase colorful vegetables (aim for 5-7 servings daily)',
                'Add more omega-3 rich foods (salmon, walnuts, chia seeds)',
                'Include more fiber-rich foods (beans, lentils, whole grains)',
                'Boost antioxidant intake with berries and green tea'
            ],
            'meal_plan' => [
                'breakfast' => 'Smoothie with spinach, berries, protein powder, and chia seeds',
                'lunch' => 'Large salad with mixed vegetables, quinoa, and lean protein',
                'dinner' => 'Grilled salmon with steamed broccoli and sweet potato',
                'snacks' => 'Apple with almond butter, or Greek yogurt with berries'
            ],
            'avoid' => [
                'Reduce refined sugars',
                'Limit processed snacks',
                'Cut back on fried foods',
                'Reduce alcohol consumption'
            ],
            'tips' => [
                'Add one extra serving of vegetables to each meal',
                'Try intermittent fasting (consult your doctor first)',
                'Drink green tea for antioxidants',
                'Plan meals ahead to avoid unhealthy choices'
            ]
        ];
    } elseif ($score >= 50) {
        // Fair score (50-69)
        $recommendations = [
            'category' => 'Improvement Diet',
            'title' => 'Fair Health - Time for Positive Changes!',
            'description' => 'Your body needs more nutritional support. Let\'s make some important dietary changes.',
            'main_foods' => [
                'Focus on whole, unprocessed foods',
                'Increase vegetable intake significantly',
                'Choose lean proteins (chicken, fish, legumes)',
                'Switch to whole grains instead of refined grains'
            ],
            'meal_plan' => [
                'breakfast' => 'Oatmeal with banana and nuts, or eggs with vegetables',
                'lunch' => 'Vegetable soup with whole grain bread and lean protein',
                'dinner' => 'Grilled chicken with large portion of steamed vegetables',
                'snacks' => 'Fresh fruits, raw vegetables, or a small handful of nuts'
            ],
            'avoid' => [
                'Fast food and takeout',
                'Sugary drinks and sodas',
                'White bread and refined grains',
                'Processed meats and snacks',
                'Excessive caffeine'
            ],
            'tips' => [
                'Start each meal with a salad or vegetables',
                'Drink water before each meal',
                'Eat slowly and mindfully',
                'Keep healthy snacks readily available',
                'Consider taking a multivitamin'
            ]
        ];
    } else {
        // Poor score (0-49)
        $recommendations = [
            'category' => 'Recovery Diet',
            'title' => 'Health Recovery - Let\'s Start Your Healing Journey!',
            'description' => 'Your body needs immediate nutritional support. These changes will help you feel better quickly.',
            'main_foods' => [
                'Anti-inflammatory foods (turmeric, ginger, leafy greens)',
                'Nutrient-dense vegetables (spinach, kale, broccoli)',
                'Easy-to-digest proteins (fish, eggs, chicken)',
                'Healing foods (bone broth, fermented foods)'
            ],
            'meal_plan' => [
                'breakfast' => 'Smoothie with spinach, banana, protein powder, and ginger',
                'lunch' => 'Bone broth with vegetables and small portion of rice',
                'dinner' => 'Baked fish with steamed vegetables and quinoa',
                'snacks' => 'Herbal tea, small portions of fruits, or vegetable juice'
            ],
            'avoid' => [
                'All processed and packaged foods',
                'Sugar and artificial sweeteners',
                'Fried and greasy foods',
                'Alcohol and excessive caffeine',
                'Dairy if you have digestive issues'
            ],
            'tips' => [
                'Start with small, frequent meals',
                'Focus on hydration - drink plenty of water',
                'Consider working with a nutritionist',
                'Take probiotics for gut health',
                'Get blood work done to check for deficiencies',
                'Meal prep to avoid unhealthy choices'
            ]
        ];
    }
    
    return $recommendations;
}

$diet_plan = getDietRecommendations($score);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diet Recommendations - HealthFirst</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .diet-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .diet-header {
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
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }
        
        .back-btn:hover {
            background: #5a6fd8;
        }
        
        .score-display {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .score-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .diet-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .diet-title {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .category-badge {
            background: #4CAF50;
            color: white;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .diet-description {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .diet-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: #333;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .food-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .food-list {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
        }
        
        .food-list h4 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .food-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .food-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            color: #555;
        }
        
        .food-list li:last-child {
            border-bottom: none;
        }
        
        .food-list li:before {
            content: "✓";
            color: #4CAF50;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .avoid-list li:before {
            content: "✗";
            color: #f44336;
        }
        
        .meal-plan {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin: 20px 0;
        }
        
        .meal-plan h4 {
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .meal-item {
            margin-bottom: 12px;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        
        .meal-time {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }
        
        .tips-section {
            background: #e8f5e8;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #4CAF50;
        }
        
        .warning-section {
            background: #ffebee;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #f44336;
        }
        
        @media (max-width: 768px) {
            .diet-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .food-grid {
                grid-template-columns: 1fr;
            }
            
            .diet-title {
                font-size: 1.5rem;
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="diet-container">
        <!-- Header -->
        <div class="diet-header">
            <a href="dashboard.php" class="back-btn">
                ← Back to Dashboard
            </a>
            <div class="score-display">
                <span>Your Wellness Score:</span>
                <div class="score-badge"><?php echo $score; ?>/100</div>
            </div>
        </div>
        
        <!-- Main Diet Recommendations Card -->
        <div class="diet-card">
            <h1 class="diet-title">
                🥗 <?php echo $diet_plan['title']; ?>
                <span class="category-badge"><?php echo $diet_plan['category']; ?></span>
            </h1>
            
            <p class="diet-description"><?php echo $diet_plan['description']; ?></p>
            
            <!-- Main Foods Section -->
            <div class="diet-section">
                <h3 class="section-title">
                    <span>🌟</span>
                    <span>Focus on These Foods</span>
                </h3>
                <div class="food-list">
                    <ul>
                        <?php foreach ($diet_plan['main_foods'] as $food): ?>
                            <li><?php echo htmlspecialchars($food); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Meal Plan Section -->
            <div class="diet-section">
                <h3 class="section-title">
                    <span>📅</span>
                    <span>Daily Meal Plan</span>
                </h3>
                <div class="meal-plan">
                    <h4>Recommended Daily Structure</h4>
                    <?php foreach ($diet_plan['meal_plan'] as $meal_time => $meal_desc): ?>
                        <div class="meal-item">
                            <div class="meal-time"><?php echo ucfirst($meal_time); ?></div>
                            <div><?php echo htmlspecialchars($meal_desc); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Foods to Avoid -->
            <div class="diet-section">
                <h3 class="section-title">
                    <span>⚠️</span>
                    <span>Foods to Limit or Avoid</span>
                </h3>
                <div class="warning-section">
                    <div class="food-list avoid-list">
                        <ul>
                            <?php foreach ($diet_plan['avoid'] as $avoid_food): ?>
                                <li><?php echo htmlspecialchars($avoid_food); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Tips Section -->
            <div class="diet-section">
                <h3 class="section-title">
                    <span>💡</span>
                    <span>Helpful Tips</span>
                </h3>
                <div class="tips-section">
                    <ul>
                        <?php foreach ($diet_plan['tips'] as $tip): ?>
                            <li><?php echo htmlspecialchars($tip); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
