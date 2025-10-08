<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user has already completed path selection and assessment
// Allow retake if 'retake' parameter is present
$allow_retake = isset($_GET['retake']) && $_GET['retake'] === 'true';

if (!$allow_retake) {
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
            
            // If user has assessments, redirect to dashboard
            if ($result['assessment_count'] > 0) {
                header('Location: dashboard.php');
                exit();
            }
        }
    } catch (Exception $e) {
        // Continue to show path selection page if there's an error
    }
}

$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Choose Your Path</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .path-selection-page {
            background-color: #FFFCF7;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .path-container {
            width: 100%;
            max-width: 600px;
        }

        .path-card {
            background: #F4E9D5;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            padding: 50px 40px;
            text-align: center;
        }

        .path-header {
            margin-bottom: 40px;
        }

        .path-header h1 {
            color: #8BA690;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .path-header .welcome-text {
            color: #565656;
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .path-description {
            color: #565656;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 40px;
        }

        .path-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .path-option {
            background: #FFFCF7;
            border: 3px solid #e9ecef;
            border-radius: 15px;
            padding: 30px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .path-option:hover {
            transform: translateY(-5px);
            border-color: #8BA690;
            box-shadow: 0 10px 25px rgba(139, 166, 144, 0.2);
            text-decoration: none;
            color: inherit;
        }

        .path-option.selected {
            border-color: #8BA690;
            background: rgba(139, 166, 144, 0.05);
        }

        .path-option h3 {
            color: #8BA690;
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .path-option p {
            color: #565656;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .continue-btn {
            background: linear-gradient(135deg, #8BA690, #9BB69E);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.5;
            pointer-events: none;
        }

        .continue-btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        .continue-btn:hover.active {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(139, 166, 144, 0.3);
        }

        @media (max-width: 768px) {
            .path-options {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .path-card {
                padding: 30px 25px;
            }
            
            .path-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="path-selection-page">
    <div class="path-container">
        <div class="path-card">
            <div class="path-header">
                <h1>Welcome, <?php echo htmlspecialchars($username); ?>!</h1>
                <?php if ($allow_retake): ?>
                    <div style="background: linear-gradient(135deg, #4CAF50, #45a049); color: white; padding: 10px 20px; border-radius: 25px; margin: 15px 0; display: inline-block;">
                        🔄 Retaking Assessment
                    </div>
                    <p class="welcome-text">Let's update your health journey</p>
                    <p class="path-description">
                        You're retaking your health assessment. Choose the path that best describes your current focus. 
                        This will help us provide you with updated recommendations based on your latest information.
                    </p>
                <?php else: ?>
                    <p class="welcome-text">Let's personalize your health journey</p>
                    <p class="path-description">
                        Choose the path that best describes what you want to build your health rating on. 
                        This will help us provide you with the most relevant assessment and recommendations.
                    </p>
                <?php endif; ?>
            </div>
            
            <form id="pathForm" method="POST" action="quiz.php">
                <div class="path-options">
                    <label class="path-option" for="medical">
                        <input type="radio" id="medical" name="path_type" value="medical" style="display: none;">
                        <h3>Medical Condition</h3>
                        <p>Focus on managing specific health conditions, symptoms, or medical concerns with targeted assessments and recommendations.</p>
                    </label>
                    
                    <label class="path-option" for="lifestyle">
                        <input type="radio" id="lifestyle" name="path_type" value="lifestyle" style="display: none;">
                        <h3>General Improvement of Lifestyle</h3>
                        <p>Enhance your overall wellness through better nutrition, fitness, sleep, and daily habits for a healthier lifestyle.</p>
                    </label>
                </div>
                
                <button type="submit" class="continue-btn" id="continueBtn">
                    Start Assessment
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pathOptions = document.querySelectorAll('.path-option');
            const continueBtn = document.getElementById('continueBtn');
            const radioInputs = document.querySelectorAll('input[name="path_type"]');
            
            pathOptions.forEach(option => {
                option.addEventListener('click', function() {
                    // Remove selected class from all options
                    pathOptions.forEach(opt => opt.classList.remove('selected'));
                    
                    // Add selected class to clicked option
                    this.classList.add('selected');
                    
                    // Check the corresponding radio button
                    const radioInput = this.querySelector('input[type="radio"]');
                    radioInput.checked = true;
                    
                    // Enable continue button
                    continueBtn.classList.add('active');
                });
            });
            
            // Handle form submission
            document.getElementById('pathForm').addEventListener('submit', function(e) {
                const selectedPath = document.querySelector('input[name="path_type"]:checked');
                if (!selectedPath) {
                    e.preventDefault();
                    alert('Please select a path before continuing.');
                }
            });
        });
    </script>
</body>
</html>
