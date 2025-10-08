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

// Get latest assessment score
$latest_score = 0;
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
            $latest_score = $result['score'];
        }
    }
} catch (Exception $e) {
    error_log("Progress tracking error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Tracking - HealthFirst</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #FFFCF7;
            min-height: 100vh;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            background: #F4E9D5;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .left-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 20px;
            position: absolute;
            top: 30px;
            right: 20px;
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
            border-color: #8BA690;
        }
        
        .toggle-switch.active {
            background: #8BA690;
            border-color: #8BA690;
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
            color: #565656;
            font-weight: 500;
        }
        
        .toggle-switch.active + .toggle-label {
            color: #8BA690;
            font-weight: 600;
        }
        
        .profile-icon {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .profile-icon:hover {
            transform: scale(1.1);
        }
        
        .profile-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8BA690 0%, #9BB69E 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .profile-initial {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .mode-indicator {
            margin-top: 10px;
        }
        
        .mode-badge {
            background: #8BA690;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .back-btn {
            background: #8BA690;
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
            background: #D4C8B2;
        }
        
        .progress-title h1 {
            margin: 0;
            color: #565656;
            font-size: 2rem;
        }
        
        .progress-title p {
            margin: 5px 0 0 0;
            color: #565656;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .activity-section {
            background: #F4E9D5;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #565656;
            margin: 0;
        }
        
        .activity-score {
            font-size: 3rem;
            font-weight: 700;
            color: #8BA690;
            text-align: center;
            margin: 20px 0;
        }
        
        .activity-categories {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .activity-item {
            background: #FFFCF7;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            border-color: #8BA690;
            background: #D4C8B2;
        }
        
        .activity-item.active {
            border-color: #8BA690;
            background: #D4C8B2;
            color: #565656;
        }
        
        .activity-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .activity-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .sleep-section {
            text-align: center;
        }
        
        .sleep-range {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            font-size: 1.2rem;
            color: #565656;
        }
        
        .sleep-slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: #e0e0e0;
            outline: none;
            margin: 20px 0;
        }
        
        .sleep-slider::-webkit-slider-thumb {
            appearance: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #8BA690;
            cursor: pointer;
        }
        
        .sleep-slider::-moz-range-thumb {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #8BA690;
            cursor: pointer;
            border: none;
        }
        
        .current-sleep {
            font-size: 2.5rem;
            font-weight: 700;
            color: #8BA690;
            margin: 15px 0;
        }
        
        .medicine-section {
            background: #F4E9D5;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            grid-column: 1 / -1;
        }
        
        .medicine-form {
            background: #FFFCF7;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 2px dashed #ddd;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            font-weight: 600;
            color: #565656;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-input {
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            background: #FFFCF7;
            transition: border-color 0.2s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #8BA690;
            box-shadow: 0 0 0 2px rgba(139, 166, 144, 0.1);
        }
        
        .save-entry-btn {
            background: #8BA690;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s ease;
        }
        
        .save-entry-btn:hover {
            background: #D4C8B2;
        }
        
        .delete-btn {
            background: #8BA690;
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .delete-btn:hover {
            background: #D4C8B2;
        }
        
        .medicine-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .medicine-table th,
        .medicine-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .medicine-table th {
            background: #FFFCF7;
            font-weight: 600;
            color: #565656;
        }
        
        .medicine-table td {
            color: #565656;
        }
        
        .medicine-table tr:hover {
            background: rgba(139, 166, 144, 0.05);
        }
        
        .add-entry-btn {
            background: #8BA690;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
        }
        
        .add-entry-btn:hover {
            background: #D4C8B2;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .progress-header {
                flex-direction: column;
                gap: 15px;
                text-align: left;
            }
            
            .header-controls {
                top: 22px;
                right: 15px;
                gap: 10px;
            }
            
            .toggle-container {
                flex-direction: row;
                gap: 8px;
                align-items: center;
            }
            
            .toggle-label {
                font-size: 0.7rem;
            }
            
            .activity-categories {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .progress-header {
                padding: 12px;
            }
            
            .header-controls {
                top: 18px;
                right: 10px;
                gap: 6px;
            }
            
            .toggle-switch {
                width: 36px;
                height: 20px;
            }
            
            .toggle-slider {
                width: 14px;
                height: 14px;
                top: 2px;
                left: 2px;
            }
            
            .toggle-switch.active .toggle-slider {
                transform: translateX(16px);
            }
            
            .profile-circle {
                width: 28px;
                height: 28px;
            }
            
            .profile-initial {
                font-size: 0.8rem;
            }
            
            .toggle-label {
                font-size: 0.55rem;
                font-weight: 600;
            }
        }
        
        /* Main Chart Section Styles */
        .main-chart-section {
            background: #F4E9D5;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .chart-header {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .time-toggles {
            display: flex;
            background: #FFFCF7;
            border-radius: 25px;
            padding: 4px;
            gap: 4px;
        }
        
        .time-toggle {
            background: transparent;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            color: #565656;
            transition: all 0.3s ease;
        }
        
        .time-toggle.active {
            background: #8BA690;
            color: white;
            box-shadow: 0 2px 8px rgba(139, 166, 144, 0.3);
        }
        
        .time-toggle:hover:not(.active) {
            background: #D4C8B2;
        }
        
        .main-chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
            position: relative;
        }
        
        #mainProgressChart {
            width: 100% !important;
            height: 400px !important;
        }
        
        /* Symptoms Section Styles */
        .symptoms-section {
            background: #F4E9D5;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .symptoms-section h4 {
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: #565656;
        }
        
        .symptoms-input {
            display: flex;
            gap: 10px;
        }
        
        .symptom-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .symptom-input:focus {
            outline: none;
            border-color: #8BA690;
            box-shadow: 0 0 0 3px rgba(139, 166, 144, 0.1);
        }
    </style>
</head>
<body>
    <div class="progress-container">
        <!-- Header -->
        <div class="progress-header">
            <div class="left-section">
                <a href="dashboard.php" class="back-btn">
                    ← Back to Dashboard
                </a>
                <div class="progress-title">
                    <h1 id="progressTitle">Physical Activity</h1>
                    <p id="progressSubtitle">Track your daily activities and progress</p>
                    <div class="mode-indicator" id="progressModeIndicator" style="display: none;">
                        <span class="mode-badge">👶 Tracking for Child</span>
                    </div>
                </div>
            </div>
            <div class="header-controls">
                <div class="toggle-container">
                    <div class="toggle-switch" onclick="toggleChildMode()" title="Child Mode - Track for Child">
                        <div class="toggle-slider"></div>
                    </div>
                    <span class="toggle-label" id="childToggleLabel">Child Mode</span>
                </div>
                <div class="profile-icon" onclick="toggleProfile()" title="Profile">
                    <div class="profile-circle">
                        <span class="profile-initial"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Chart Section -->
        <div class="main-chart-section">
            <div class="chart-header">
                <div class="time-toggles">
                    <button class="time-toggle active" data-period="weekly">Weekly</button>
                    <button class="time-toggle" data-period="monthly">Monthly</button>
                </div>
            </div>
            <div class="main-chart-container">
                <canvas id="mainProgressChart" width="800" height="400"></canvas>
            </div>
        </div>
        
        <!-- Symptoms Entry Section -->
        <div class="symptoms-section">
            <h4>Symptoms Entry</h4>
            <div class="symptoms-input">
                <input type="text" placeholder="Enter symptoms..." class="symptom-input">
            </div>
        </div>
    </div>
    
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Add a small delay to ensure Chart.js is fully loaded
            setTimeout(() => {
                initializeCharts();
                loadChildModePreference();
            }, 100);
        });
        
        // Initialize charts
        function initializeCharts() {
            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js is not loaded!');
                return;
            }
            
            initializeMainChart();
            initializeTimeToggles();
        }
        
        let currentPeriod = 'weekly';
        let mainChart = null;
        
        // Initialize Main Progress Chart
        function initializeMainChart() {
            const ctx = document.getElementById('mainProgressChart');
            console.log('Chart canvas element:', ctx);
            if (ctx) {
                const chartData = generateChartData(currentPeriod);
                console.log('Chart data:', chartData);
                
                try {
                    mainChart = new Chart(ctx.getContext('2d'), {
                        type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Med',
                                data: chartData.med,
                                borderColor: '#dc3545',
                                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.4,
                                pointBackgroundColor: '#dc3545',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 6
                            },
                            {
                                label: 'Sleep',
                                data: chartData.sleep,
                                borderColor: '#6f42c1',
                                backgroundColor: 'rgba(111, 66, 193, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.4,
                                pointBackgroundColor: '#6f42c1',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 6
                            },
                            {
                                label: 'Act',
                                data: chartData.activity,
                                borderColor: '#28a745',
                                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.4,
                                pointBackgroundColor: '#28a745',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 6
                            },
                            {
                                label: 'Diet',
                                data: chartData.diet,
                                borderColor: '#fd7e14',
                                backgroundColor: 'rgba(253, 126, 20, 0.1)',
                                borderWidth: 3,
                                fill: false,
                                tension: 0.4,
                                pointBackgroundColor: '#fd7e14',
                                pointBorderColor: '#fff',
                                pointBorderWidth: 2,
                                pointRadius: 6
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 14,
                                        weight: '500'
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#666',
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#666',
                                    font: {
                                        size: 12
                                    }
                                }
                            }
                        }
                    }
                });
                console.log('Chart created successfully:', mainChart);
                } catch (error) {
                    console.error('Error creating chart:', error);
                }
            }
        }
        
        // Generate chart data based on period
        function generateChartData(period) {
            let labels = [];
            let dataPoints = 0;
            
            if (period === 'weekly') {
                labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                dataPoints = 7;
            } else {
                labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                dataPoints = 4;
            }
            
            // Generate simple test data
            const med = [85, 90, 78, 92, 88, 85, 90];
            const sleep = [65, 70, 60, 75, 68, 72, 69];
            const activity = [45, 60, 55, 70, 65, 58, 62];
            const diet = [55, 65, 60, 70, 68, 63, 67];
            
            // Trim arrays to match dataPoints
            return { 
                labels, 
                med: med.slice(0, dataPoints), 
                sleep: sleep.slice(0, dataPoints), 
                activity: activity.slice(0, dataPoints), 
                diet: diet.slice(0, dataPoints) 
            };
        }
        
        // Initialize time toggles
        function initializeTimeToggles() {
            const toggles = document.querySelectorAll('.time-toggle');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    // Remove active class from all toggles
                    toggles.forEach(t => t.classList.remove('active'));
                    
                    // Add active class to clicked toggle
                    this.classList.add('active');
                    
                    // Update current period
                    currentPeriod = this.dataset.period;
                    
                    // Update chart
                    updateMainChart();
                });
            });
        }
        
        // Update main chart
        function updateMainChart() {
            if (mainChart) {
                const newData = generateChartData(currentPeriod);
                mainChart.data.labels = newData.labels;
                mainChart.data.datasets[0].data = newData.med;
                mainChart.data.datasets[1].data = newData.sleep;
                mainChart.data.datasets[2].data = newData.activity;
                mainChart.data.datasets[3].data = newData.diet;
                mainChart.update();
            }
        }
        
        // Activity toggles functionality
        function initializeActivityToggles() {
            document.querySelectorAll('.activity-item').forEach(item => {
                item.addEventListener('click', function() {
                    // Remove active class from all items
                    document.querySelectorAll('.activity-item').forEach(i => i.classList.remove('active'));
                    
                    // Add active class to clicked item
                    this.classList.add('active');
                    
                    // Update activity score based on selection
                    const activity = this.dataset.activity;
                    updateActivityScore(activity);
                });
            });
        }
        
        // Update activity score
        function updateActivityScore(activity) {
            const scoreElement = document.getElementById('activityScore');
            
            if (activity === 'lift') {
                scoreElement.textContent = '6-7';
            } else if (activity === 'cardio') {
                scoreElement.textContent = '8-9';
            }
        }
        
        // Sleep slider functionality
        function initializeSleepSlider() {
            const slider = document.getElementById('sleepSlider');
            const currentSleep = document.getElementById('currentSleep');
            
            slider.addEventListener('input', function() {
                currentSleep.textContent = this.value;
            });
        }
        
        // Toggle medicine entry form visibility
        function addMedicineEntry() {
            const form = document.querySelector('.medicine-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'block';
            
            // Clear form
            document.getElementById('medicineSelect').value = '';
            document.getElementById('medicineTime').value = '';
            document.getElementById('medicineSymptoms').value = '';
        }
        
        // Save medicine symptom entry
        function saveMedicineSymptom() {
            const medicine = document.getElementById('medicineSelect').value;
            const time = document.getElementById('medicineTime').value;
            const symptoms = document.getElementById('medicineSymptoms').value;
            
            if (!medicine || !time || !symptoms) {
                alert('Please fill in all fields');
                return;
            }
            
            // Convert time to readable format
            const timeFormatted = new Date('2000-01-01T' + time).toLocaleTimeString([], {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            // Get symptom text
            const symptomText = document.getElementById('medicineSymptoms').selectedOptions[0].text;
            
            // Get medicine text
            const medicineText = document.getElementById('medicineSelect').selectedOptions[0].text;
            
            // Add to table
            const tableBody = document.getElementById('medicineTableBody');
            const newRow = tableBody.insertRow();
            
            newRow.innerHTML = `
                <td>${medicineText}</td>
                <td>${timeFormatted}</td>
                <td>${symptomText}</td>
                <td><button class="delete-btn" onclick="deleteEntry(this)">×</button></td>
            `;
            
            // Save to localStorage
            saveMedicineEntry(medicine, time, symptoms, symptomText, medicineText);
            
            // Clear form
            document.getElementById('medicineSelect').value = '';
            document.getElementById('medicineTime').value = '';
            document.getElementById('medicineSymptoms').value = '';
        }
        
        // Delete entry
        function deleteEntry(button) {
            if (confirm('Are you sure you want to delete this entry?')) {
                button.closest('tr').remove();
            }
        }
        
        // Toggle child mode
        function toggleChildMode() {
            const toggleSwitch = document.querySelector('.toggle-switch');
            const modeIndicator = document.getElementById('progressModeIndicator');
            const progressTitle = document.getElementById('progressTitle');
            const progressSubtitle = document.getElementById('progressSubtitle');
            
            toggleSwitch.classList.toggle('active');
            
            const isChildMode = toggleSwitch.classList.contains('active');
            
            if (isChildMode) {
                // Switch to child mode
                modeIndicator.style.display = 'block';
                progressTitle.textContent = "Child's Physical Activity";
                progressSubtitle.textContent = "Track your child's daily activities and progress";
                
                // Update section titles
                updateSectionTitlesForChild();
            } else {
                // Switch back to personal mode
                modeIndicator.style.display = 'none';
                progressTitle.textContent = "Physical Activity";
                progressSubtitle.textContent = "Track your daily activities and progress";
                
                // Update section titles
                updateSectionTitlesForSelf();
            }
            
            // Save preference to localStorage
            localStorage.setItem('childModeProgress', isChildMode);
            
            // Load appropriate data
            loadMedicineEntries();
        }
        
        // Load child mode preference on page load
        function loadChildModePreference() {
            const isChildMode = localStorage.getItem('childModeProgress') === 'true';
            if (isChildMode) {
                const toggleSwitch = document.querySelector('.toggle-switch');
                toggleSwitch.classList.add('active');
                
                const modeIndicator = document.getElementById('progressModeIndicator');
                const progressTitle = document.getElementById('progressTitle');
                const progressSubtitle = document.getElementById('progressSubtitle');
                
                modeIndicator.style.display = 'block';
                progressTitle.textContent = "Child's Physical Activity";
                progressSubtitle.textContent = "Track your child's daily activities and progress";
                
                updateSectionTitlesForChild();
            }
        }
        
        // Update section titles for child mode
        function updateSectionTitlesForChild() {
            const sectionTitles = document.querySelectorAll('.section-title');
            sectionTitles.forEach(title => {
                if (title.textContent === 'Physical Activity') {
                    title.textContent = "Child's Physical Activity";
                } else if (title.textContent === 'Sleep') {
                    title.textContent = "Child's Sleep";
                } else if (title.textContent === 'Medicine & Symptoms') {
                    title.textContent = "Child's Medicine & Symptoms";
                }
            });
        }
        
        // Update section titles for self mode
        function updateSectionTitlesForSelf() {
            const sectionTitles = document.querySelectorAll('.section-title');
            sectionTitles.forEach(title => {
                if (title.textContent === "Child's Physical Activity") {
                    title.textContent = 'Physical Activity';
                } else if (title.textContent === "Child's Sleep") {
                    title.textContent = 'Sleep';
                } else if (title.textContent === "Child's Medicine & Symptoms") {
                    title.textContent = 'Medicine & Symptoms';
                }
            });
        }
        
        // Toggle profile dropdown/menu
        function toggleProfile() {
            alert('Profile menu - coming soon!');
        }
        
        // Save medicine entry to localStorage
        function saveMedicineEntry(medicine, time, symptoms, symptomText, medicineText) {
            const isChildMode = document.querySelector('.toggle-switch').classList.contains('active');
            const storageKey = isChildMode ? 'childMedicineEntries' : 'medicineEntries';
            
            const entries = JSON.parse(localStorage.getItem(storageKey) || '[]');
            entries.push({
                medicine: medicine,
                medicineText: medicineText,
                time: time,
                symptoms: symptoms,
                symptomText: symptomText,
                date: new Date().toISOString().split('T')[0]
            });
            localStorage.setItem(storageKey, JSON.stringify(entries));
        }
        
        // Load medicine entries from localStorage
        function loadMedicineEntries() {
            const isChildMode = document.querySelector('.toggle-switch').classList.contains('active');
            const storageKey = isChildMode ? 'childMedicineEntries' : 'medicineEntries';
            const entries = JSON.parse(localStorage.getItem(storageKey) || '[]');
            const tableBody = document.getElementById('medicineTableBody');
            
            // Clear existing entries
            tableBody.innerHTML = '';
            
            // Add loaded entries
            entries.forEach(entry => {
                const timeFormatted = new Date('2000-01-01T' + entry.time).toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
                
                const newRow = tableBody.insertRow();
                newRow.innerHTML = `
                    <td>${entry.medicineText}</td>
                    <td>${timeFormatted}</td>
                    <td>${entry.symptomText}</td>
                    <td><button class="delete-btn" onclick="deleteEntry(this)">×</button></td>
                `;
            });
            
            // Add sample entries if no data exists
            if (entries.length === 0) {
                const sampleEntries = isChildMode ? [
                    { medicineText: 'Children\'s Vitamin D', time: '08:00', symptomText: 'More energetic' },
                    { medicineText: 'Children\'s Multivitamin', time: '07:30', symptomText: 'Good appetite' }
                ] : [
                    { medicineText: 'Vitamin D', time: '08:00', symptomText: 'Feeling better' },
                    { medicineText: 'Paracetamol', time: '14:00', symptomText: 'Pain relief' }
                ];
                
                sampleEntries.forEach(entry => {
                    const newRow = tableBody.insertRow();
                    newRow.innerHTML = `
                        <td>${entry.medicineText}</td>
                        <td>${entry.time}</td>
                        <td>${entry.symptomText}</td>
                        <td><button class="delete-btn" onclick="deleteEntry(this)">×</button></td>
                    `;
                });
            }
        }
    </script>
</body>
</html>
