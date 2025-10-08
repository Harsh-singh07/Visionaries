<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$username = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Health Information</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .info-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .info-header {
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
        
        .info-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .info-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .info-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .facts-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .fact-item {
            margin-bottom: 15px;
            border: 2px solid #e8ecf4;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .fact-item:hover {
            border-color: #667eea;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }
        
        .fact-item.active {
            border-color: #667eea;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.15);
        }
        
        .fact-header {
            padding: 20px;
            background: #f8f9ff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }
        
        .fact-item.active .fact-header {
            background: #667eea;
            color: white;
        }
        
        .fact-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 15px;
            transition: all 0.3s ease;
        }
        
        .fact-item.active .fact-number {
            background: white;
            color: #667eea;
        }
        
        .fact-title {
            flex: 1;
            font-weight: 600;
            font-size: 1.1rem;
            color: #333;
            transition: color 0.3s ease;
        }
        
        .fact-item.active .fact-title {
            color: white;
        }
        
        .expand-icon {
            font-size: 1.2rem;
            color: #667eea;
            transition: all 0.3s ease;
        }
        
        .fact-item.active .expand-icon {
            color: white;
            transform: rotate(180deg);
        }
        
        .fact-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            background: white;
        }
        
        .fact-content.expanded {
            max-height: 500px;
        }
        
        .fact-text {
            padding: 25px;
            color: #555;
            line-height: 1.8;
            font-size: 1rem;
            border-top: 1px solid #eee;
        }
        
        .blood-pressure-icon {
            font-size: 2.5rem;
        }
        
        .tip-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f4ff 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .tip-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tip-text {
            color: #555;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .info-container {
                padding: 10px;
            }
            
            .info-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .info-card {
                padding: 20px;
            }
            
            .info-title {
                font-size: 1.5rem;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .fact-header {
                padding: 15px;
            }
            
            .fact-title {
                font-size: 1rem;
            }
            
            .fact-text {
                padding: 20px;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            .fact-header {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .fact-number {
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            .expand-icon {
                position: absolute;
                top: 15px;
                right: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="info-container">
        <!-- Header -->
        <div class="info-header">
            <a href="dashboard.php" class="back-btn">
                <span>←</span> Back to Dashboard
            </a>
            <h1>ℹ️ Health Information</h1>
        </div>
        
        <!-- Info Card -->
        <div class="info-card">
            <h2 class="info-title">
                <span class="blood-pressure-icon">🩺</span>
                Blood Pressure Facts
            </h2>
            <p class="info-subtitle">
                Understanding blood pressure is crucial for maintaining good health. Here are 5 important facts that everyone should know about blood pressure and its impact on your overall well-being.
            </p>
            
            <ul class="facts-list">
                <li class="fact-item" data-fact="1">
                    <div class="fact-header">
                        <div class="fact-number">1</div>
                        <div class="fact-title">Blood pressure is linked to other medical issues.</div>
                        <div class="expand-icon">▼</div>
                    </div>
                    <div class="fact-content">
                        <div class="fact-text">
                            High blood pressure can signal serious underlying conditions. When detected, doctors check kidney function, heart size (via ECG), and lung health. Hypertension puts strain on blood vessels, increasing the risk of heart disease, stroke, kidney problems, and aneurysms. Chronic issues like diabetes, kidney disease, sleep apnea, and high cholesterol can also lead to high blood pressure. In women, pregnancy may trigger hypertension (preeclampsia), which usually resolves within six weeks after birth, though repeated cases can raise long-term cardiovascular risks.
                        </div>
                    </div>
                </li>
                
                <li class="fact-item" data-fact="2">
                    <div class="fact-header">
                        <div class="fact-number">2</div>
                        <div class="fact-title">Lowering systolic blood pressure more may cut health risks.</div>
                        <div class="expand-icon">▼</div>
                    </div>
                    <div class="fact-content">
                        <div class="fact-text">
                            Research shows that lowering systolic blood pressure (the top number) to below 120 mmHg can significantly reduce the risk of heart attack, stroke, and cardiovascular death. The SPRINT study demonstrated that intensive blood pressure control led to a 25% reduction in major cardiovascular events. However, this aggressive approach requires careful monitoring and may not be suitable for everyone, especially older adults or those with certain medical conditions.
                        </div>
                    </div>
                </li>
                
                <li class="fact-item" data-fact="3">
                    <div class="fact-header">
                        <div class="fact-number">3</div>
                        <div class="fact-title">You shouldn't ignore white coat hypertension.</div>
                        <div class="expand-icon">▼</div>
                    </div>
                    <div class="fact-content">
                        <div class="fact-text">
                            White coat hypertension occurs when blood pressure readings are higher in medical settings than at home due to anxiety or stress. While once considered harmless, recent studies suggest it may indicate increased cardiovascular risk. People with white coat hypertension should monitor their blood pressure at home regularly and may benefit from lifestyle modifications. It can also progress to sustained hypertension over time, making regular monitoring essential.
                        </div>
                    </div>
                </li>
                
                <li class="fact-item" data-fact="4">
                    <div class="fact-header">
                        <div class="fact-number">4</div>
                        <div class="fact-title">Learning to cope with stress can help.</div>
                        <div class="expand-icon">▼</div>
                    </div>
                    <div class="fact-content">
                        <div class="fact-text">
                            Chronic stress contributes to high blood pressure through various mechanisms, including increased cortisol production and activation of the sympathetic nervous system. Effective stress management techniques include deep breathing exercises, meditation, regular physical activity, yoga, and maintaining social connections. Studies show that stress reduction programs can lower blood pressure by 5-10 mmHg. Even simple techniques like taking slow, deep breaths for a few minutes daily can make a measurable difference.
                        </div>
                    </div>
                </li>
                
                <li class="fact-item" data-fact="5">
                    <div class="fact-header">
                        <div class="fact-number">5</div>
                        <div class="fact-title">Good sleep can prevent and manage high blood pressure.</div>
                        <div class="expand-icon">▼</div>
                    </div>
                    <div class="fact-content">
                        <div class="fact-text">
                            Quality sleep is essential for blood pressure regulation. During sleep, blood pressure naturally decreases by 10-20%, giving the cardiovascular system time to recover. Poor sleep quality, sleep deprivation, or sleep disorders like sleep apnea can lead to sustained high blood pressure. Adults should aim for 7-9 hours of quality sleep nightly. Good sleep hygiene includes maintaining a consistent sleep schedule, creating a comfortable sleep environment, avoiding caffeine late in the day, and limiting screen time before bed.
                        </div>
                    </div>
                </li>
            </ul>
            
            <div class="tip-box">
                <div class="tip-title">
                    <span>💡</span>
                    Health Tip
                </div>
                <div class="tip-text">
                    Regular blood pressure monitoring, a healthy diet low in sodium, regular exercise, stress management, and adequate sleep are the cornerstones of blood pressure management. Always consult with your healthcare provider for personalized advice.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const factItems = document.querySelectorAll('.fact-item');
            
            factItems.forEach(item => {
                const header = item.querySelector('.fact-header');
                const content = item.querySelector('.fact-content');
                
                header.addEventListener('click', function() {
                    const isActive = item.classList.contains('active');
                    
                    // Close all other items
                    factItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.fact-content').classList.remove('expanded');
                        }
                    });
                    
                    // Toggle current item
                    if (isActive) {
                        item.classList.remove('active');
                        content.classList.remove('expanded');
                    } else {
                        item.classList.add('active');
                        content.classList.add('expanded');
                        
                        // Smooth scroll to the item
                        setTimeout(() => {
                            item.scrollIntoView({
                                behavior: 'smooth',
                                block: 'nearest'
                            });
                        }, 100);
                    }
                });
            });
        });
    </script>
</body>
</html>
