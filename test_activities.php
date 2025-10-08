<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Physical Activities</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .test-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
        }
        
        .step-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .step-header h2 {
            font-family: 'Poppins', sans-serif;
            color: #2E86C1;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .step-header p {
            color: #666;
            font-size: 1.1rem;
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
        
        .selected-activities {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="step-header">
            <h2>Physical Activities</h2>
            <p>Select the physical activities you enjoy or would like to include in your routine</p>
        </div>
        
        <div class="activities-grid" id="physicalActivities">
            <div class="activity-item" data-value="gym">
                <div class="activity-circle">
                    <div class="activity-icon">🏋️</div>
                </div>
                <span class="activity-label">Gym</span>
            </div>
            
            <div class="activity-item" data-value="cycling">
                <div class="activity-circle">
                    <div class="activity-icon">🚴</div>
                </div>
                <span class="activity-label">Cycling</span>
            </div>
            
            <div class="activity-item" data-value="gymnastic">
                <div class="activity-circle">
                    <div class="activity-icon">🤸</div>
                </div>
                <span class="activity-label">Gymnastic</span>
            </div>
            
            <div class="activity-item" data-value="walk">
                <div class="activity-circle">
                    <div class="activity-icon">🚶</div>
                </div>
                <span class="activity-label">Walk</span>
            </div>
            
            <div class="activity-item" data-value="running">
                <div class="activity-circle">
                    <div class="activity-icon">🏃</div>
                </div>
                <span class="activity-label">Running</span>
            </div>
            
            <div class="activity-item" data-value="yoga">
                <div class="activity-circle">
                    <div class="activity-icon">🧘</div>
                </div>
                <span class="activity-label">Yoga</span>
            </div>
            
            <div class="activity-item" data-value="swimming">
                <div class="activity-circle">
                    <div class="activity-icon">🏊</div>
                </div>
                <span class="activity-label">Swimming</span>
            </div>
            
            <div class="activity-item" data-value="skipping">
                <div class="activity-circle">
                    <div class="activity-icon">🪢</div>
                </div>
                <span class="activity-label">Skipping</span>
            </div>
        </div>
        
        <div class="selected-activities">
            <strong>Selected Activities:</strong> <span id="selectedList">None</span>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="quiz.php" style="color: #667eea; text-decoration: none;">← Back to Full Assessment</a>
        </div>
    </div>

    <script>
        // Physical activities selection
        const activityItems = document.querySelectorAll('#physicalActivities .activity-item');
        const selectedList = document.getElementById('selectedList');
        
        activityItems.forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('selected');
                updateSelectedList();
            });
        });
        
        function updateSelectedList() {
            const selected = document.querySelectorAll('#physicalActivities .activity-item.selected');
            const activities = Array.from(selected).map(item => item.dataset.value);
            selectedList.textContent = activities.length > 0 ? activities.join(', ') : 'None';
        }
    </script>
</body>
</html>
