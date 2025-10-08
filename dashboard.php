<?php
session_start();
require_once 'config/database.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if user has completed path selection and assessment
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
        
        // If user has no assessments, redirect to path selection
        if ($result['assessment_count'] == 0) {
            header('Location: path_selection.php');
            exit();
        }
    }
} catch (Exception $e) {
    // Continue to dashboard if there's an error
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Get latest assessment score
$latest_score = 0;
$assessment_date = null;

// Initialize assessment variables to prevent undefined variable warnings
$adult_assessment = null;
$child_assessment = null;

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db !== null) {
        // Get latest assessment (both adult and child)
        $adult_query = "SELECT score, assessment_date, 'adult' as type FROM user_assessments 
                       WHERE user_id = :user_id AND (is_child_assessment = 0 OR is_child_assessment IS NULL)
                       ORDER BY assessment_date DESC LIMIT 1";
        $child_query = "SELECT score, assessment_date, 'child' as type FROM user_assessments 
                       WHERE user_id = :user_id AND is_child_assessment = 1
                       ORDER BY assessment_date DESC LIMIT 1";
        
        // Get adult assessment
        $stmt = $db->prepare($adult_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $adult_assessment = $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        
        // Get child assessment
        $stmt = $db->prepare($child_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $child_assessment = $stmt->rowCount() > 0 ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        
        // Default to adult assessment
        if ($adult_assessment) {
            $latest_score = $adult_assessment['score'];
            $assessment_date = $adult_assessment['assessment_date'];
        }
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

// Format assessment date
$formatted_date = $assessment_date ? date('d M Y', strtotime($assessment_date)) : 'Not assessed yet';

// Pass assessment data to JavaScript
$adult_score = $adult_assessment ? $adult_assessment['score'] : 0;
$child_score = $child_assessment ? $child_assessment['score'] : 0;
$adult_date = $adult_assessment ? date('d M Y', strtotime($adult_assessment['assessment_date'])) : 'Not assessed yet';
$child_date = $child_assessment ? date('d M Y', strtotime($child_assessment['assessment_date'])) : 'Not assessed yet';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #FFFCF7;
            min-height: 100vh;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            margin-bottom: 30px;
            background: #F4E9D5;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .hamburger-menu {
            cursor: pointer;
            padding: 8px;
            transition: all 0.3s ease;
        }
        
        .hamburger-menu:hover {
            background: #f0f4ff;
            border-radius: 6px;
        }
        
        .dash {
            width: 24px;
            height: 3px;
            background: #565656;
            margin: 4px 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .hamburger-menu.active .dash:nth-child(1) {
            transform: rotate(45deg) translate(6px, 6px);
        }
        
        .hamburger-menu.active .dash:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger-menu.active .dash:nth-child(3) {
            transform: rotate(-45deg) translate(6px, -6px);
        }
        
        .left-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .home-title {
            margin: 0;
            color: #565656;
            font-size: 2rem;
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
            border-color: #667eea;
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
        
        .routine-card {
            background: #F4E9D5;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .routine-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .routine-info h2 {
            margin: 0;
            color: #565656;
            font-size: 1.8rem;
        }
        
        .routine-info p {
            margin: 5px 0 0 0;
            color: #565656;
            font-size: 0.9rem;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            position: relative;
        }
        
        .circle-progress {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: conic-gradient(#8BA690 0deg, #8BA690 var(--progress-deg), #D4C8B2 var(--progress-deg), #D4C8B2 360deg);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .circle-inner {
            width: 90px;
            height: 90px;
            background: #FFFCF7;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .score-number {
            font-size: 2rem;
            font-weight: 700;
            color: #565656;
            margin: 0;
        }
        
        .score-label {
            font-size: 0.7rem;
            color: #565656;
            margin: 0;
        }
        
        .routine-entry-section {
            border-top: 2px dashed #e0e0e0;
            padding-top: 20px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #565656;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .routine-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .routine-item {
            border: 2px dashed #ddd;
            border-radius: 12px;
            padding: 20px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .routine-item:hover {
            border-color: #8BA690;
            background: rgba(139, 166, 144, 0.05);
        }
        
        .routine-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .routine-item-title {
            font-weight: 600;
            color: #565656;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .routine-item-icon {
            font-size: 1.2rem;
        }
        
        .routine-item-actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 24px;
            height: 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }
        
        .add-btn {
            background: #8BA690;
            color: white;
        }
        
        .add-btn:hover {
            background: #7A9580;
        }
        
        .routine-close-btn {
            background: #f44336;
            color: white;
        }
        
        .routine-close-btn:hover {
            background: #da190b;
        }
        
        .routine-content {
            min-height: 80px;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 12px;
            background: #FFFCF7;
            font-size: 0.9rem;
            color: #565656;
            outline: none;
            resize: vertical;
            width: 100%;
            font-family: inherit;
        }
        
        .routine-content:focus {
            border-color: #8BA690;
            background: #FFFCF7;
        }
        
        .routine-content.empty {
            color: #aaa;
            font-style: italic;
        }
        
        /* Physical Activities Styles for Dashboard */
        .physical-activities-item .activities-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 15px 0;
            margin: 0;
        }
        
        .physical-activities-item .activity-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .physical-activities-item .activity-item:hover .activity-circle {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        
        .physical-activities-item .activity-item.selected .activity-circle {
            background: linear-gradient(135deg, #8BA690 0%, #9BB69E 100%);
            color: white;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(139, 166, 144, 0.4);
        }
        
        .physical-activities-item .activity-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #FFFCF7;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .physical-activities-item .activity-icon {
            font-size: 1.5rem;
            line-height: 1;
        }
        
        .physical-activities-item .activity-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: #565656;
            text-align: center;
            transition: color 0.3s ease;
        }
        
        .physical-activities-item .activity-item.selected .activity-label {
            color: #8BA690;
            font-weight: 600;
        }
        
        /* Sleep Section Styles */
        .sleep-record-container {
            padding: 20px 0;
        }
        
        .sleep-record-btn {
            width: 100%;
            background: linear-gradient(135deg, #8BA690, #8BA690);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .sleep-record-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }
        
        .sleep-record-btn .sleep-icon {
            font-size: 1.2rem;
        }
        
        /* Sleep Modal Styles */
        .sleep-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        
        .sleep-modal.active {
            display: flex;
        }
        
        .sleep-modal-content {
            background: #F4E9D5;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            color: #565656;
            position: relative;
        }
        
        .sleep-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .sleep-modal-header h3 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #565656;
        }
        
        .sleep-modal-header .close-btn {
            background: none;
            border: none;
            color: #565656;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }
        
        .sleep-circle-container {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        
        .sleep-circle {
            width: 250px;
            height: 250px;
            border-radius: 50%;
            background: #D4C8B2;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sleep-arc {
            position: absolute;
            top: 10px;
            left: 10px;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            background: conic-gradient(from 270deg, #8BA690 0deg, #8BA690 120deg, transparent 120deg);
        }
        
        .sleep-center {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .sleep-icon-center, .wake-icon-center {
            font-size: 1.5rem;
            margin: 5px 0;
        }
        
        .sleep-time-display {
            margin: 10px 0;
        }
        
        .sleep-label {
            font-size: 0.9rem;
            color: #565656;
            margin-bottom: 5px;
        }
        
        .sleep-duration {
            font-size: 1.8rem;
            font-weight: 600;
            color: #565656;
        }
        
        
        .sleep-time-inputs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 20px;
        }
        
        .time-input-group {
            text-align: center;
            flex: 1;
        }
        
        .time-input {
            background: #FFFCF7;
            color: #565656;
            border: 2px solid #D4C8B2;
            border-radius: 10px;
            padding: 12px 20px;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 8px;
            width: 100%;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .time-input:focus {
            outline: none;
            border-color: #8BA690;
            background: #FFFCF7;
        }
        
        .time-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }
        
        .time-input-group label {
            font-size: 0.9rem;
            color: #565656;
        }
        
        .ok-btn {
            width: 100%;
            background: #8BA690;
            color: white;
            border: none;
            border-radius: 15px;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .ok-btn:hover {
            background: #D4C8B2;
        }
        
        /* Sleep Graph Section in Progress Overview */
        .sleep-graph-section {
            margin-top: 30px;
            padding: 20px;
            background: #F4E9D5;
            border-radius: 15px;
            color: #565656;
        }
        
        .sleep-graph-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .sleep-graph-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: #565656;
        }
        
        .sleep-graph-container {
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 250px;
        }
        
        #sleepCycleChart {
            width: 100% !important;
            height: 300px !important;
            max-width: 600px;
        }
        
        /* Progress Modal Styles */
        .progress-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }
        
        .progress-modal.active {
            display: flex;
        }
        
        .progress-modal-content {
            background: #F4E9D5;
            border-radius: 20px;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .progress-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 15px;
        }
        
        .progress-modal-header h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: #565656;
        }
        
        .progress-sections {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .progress-section {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            background: #FFFCF7;
        }
        
        .progress-section h4 {
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
            padding: 10px 15px;
            border: 1px solid #cbd5e0;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .add-symptom-btn {
            background: #8BA690;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .activity-chart-container, .sleep-chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }
        
        .med-progress {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .med-count {
            font-size: 1.5rem;
            font-weight: 600;
            color: #565656;
            min-width: 80px;
        }
        
        .med-progress-bar {
            flex: 1;
            height: 20px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .med-progress-fill {
            height: 100%;
            background: #8BA690;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        /* Med Management Styles */
        .med-schedule {
            padding: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .med-time-slot {
            display: flex;
            align-items: center;
        }
        
        .med-checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            width: 100%;
            padding: 12px 15px;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #FFFCF7;
            border: 2px solid #e9ecef;
        }
        
        .med-checkbox-label:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        
        .med-checkbox-label.checked {
            background: #8BA690;
            border-color: #8BA690;
            color: white;
        }
        
        .med-checkbox {
            display: none;
        }
        
        .med-checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            background: white;
        }
        
        .med-checkbox:checked + .med-checkmark {
            background: white;
            border-color: white;
        }
        
        .med-checkbox:checked + .med-checkmark::after {
            content: '✓';
            color: #8BA690;
            font-weight: bold;
            font-size: 14px;
        }
        
        .med-time-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .med-time-icon {
            font-size: 1.2rem;
        }
        
        .med-time-text {
            font-size: 1rem;
            font-weight: 500;
        }
        
        .med-checkbox-label.checked .med-time-text {
            color: white;
        }
        
        .med-save-container {
            margin-top: 20px;
            display: flex;
            justify-content: center;
        }
        
        .med-save-btn {
            background: linear-gradient(135deg, #8BA690 0%, #9BB69E 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(139, 166, 144, 0.3);
        }
        
        .med-save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(139, 166, 144, 0.4);
        }
        
        .med-save-btn:active {
            transform: translateY(0);
        }
        
        .save-icon {
            font-size: 1.1rem;
        }
        
        /* Sleep Overview Styles */
        .sleep-overview-section {
            margin-top: 30px;
            padding: 20px;
            background: #F4E9D5;
            border-radius: 15px;
            color: #565656;
            text-align: center;
        }
        
        .sleep-overview-header {
            margin-bottom: 20px;
        }
        
        .sleep-overview-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: #565656;
        }
        
        .sleep-simple-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        
        .sleep-hours-only {
            font-size: 3rem;
            font-weight: 700;
            color: #565656;
            line-height: 1;
        }
        
        .sleep-hours-label {
            font-size: 1rem;
            color: #565656;
            font-weight: 500;
        }
        
        /* Medicines & Symptoms Overview Styles */
        .med-symptoms-overview-section {
            margin-top: 30px;
            padding: 20px;
            background: #F4E9D5;
            border-radius: 15px;
            color: #565656;
        }
        
        .med-symptoms-header {
            margin-bottom: 20px;
            text-align: center;
        }
        
        .med-symptoms-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
            color: #565656;
        }
        
        .med-symptoms-stats {
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }
        
        .med-symptoms-stat-item {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            padding: 15px;
            background: #D4C8B2;
            border-radius: 12px;
        }
        
        .med-symptoms-stat-icon {
            font-size: 2rem;
            opacity: 0.9;
        }
        
        .med-symptoms-stat-info {
            flex: 1;
        }
        
        .med-symptoms-stat-label {
            font-size: 0.9rem;
            color: #565656;
            margin-bottom: 4px;
        }
        
        .med-symptoms-stat-value {
            font-size: 1.4rem;
            font-weight: 600;
            color: #565656;
        }
        
        .score-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }
        
        
        .graph-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .graph-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #565656;
            margin: 0;
        }
        
        .time-toggles {
            display: flex;
            gap: 10px;
        }
        
        .time-toggle {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: #FFFCF7;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 0.9rem;
        }
        
        .time-toggle:hover {
            border-color: #8BA690;
        }
        
        .time-toggle.active {
            background: #8BA690;
            color: white;
            border-color: #8BA690;
        }
        
        .chart-container {
            height: 300px;
            position: relative;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .chart-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .no-data {
            text-align: center;
            color: #888;
            padding: 60px 20px;
            border: 2px dashed #ddd;
            border-radius: 8px;
        }
        
        /* Dark mode styles */
        body.dark-mode {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        
        body.dark-mode .dashboard-container {
            background: #1a1a1a;
        }
        
        body.dark-mode .dashboard-header,
        body.dark-mode .routine-card {
            background: #2d2d2d;
            color: #e0e0e0;
        }
        
        body.dark-mode .routine-item {
            background: #3a3a3a;
            border-color: #555;
        }
        
        body.dark-mode .routine-content {
            background: #404040;
            color: #e0e0e0;
            border-color: #555;
        }
        
        body.dark-mode .form-input {
            background: #404040;
            color: #e0e0e0;
            border-color: #555;
        }
        
        body.dark-mode .dash {
            background: #e0e0e0;
        }
        
        body.dark-mode .hamburger-menu:hover {
            background: #404040;
        }
        
        body.dark-mode .home-title {
            color: #e0e0e0;
        }
        
        body.dark-mode .toggle-label {
            color: #e0e0e0;
        }
        
        body.dark-mode .toggle-switch.active + .toggle-label {
            color: #8BA690;
        }
        
        .bottom-nav {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 30px;
            background: rgba(244, 233, 213, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 1000;
        }
        
        .nav-button {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .nav-button:hover {
            transform: translateY(-3px);
        }
        
        .nav-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #FFFCF7;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .nav-button:hover .nav-circle {
            background: #8BA690;
            border-color: #8BA690;
            transform: scale(1.1);
        }
        
        .nav-button.active .nav-circle {
            background: linear-gradient(135deg, #8BA690 0%, #9BB69E 100%);
            border-color: #8BA690;
        }
        
        .nav-icon {
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .nav-button:hover .nav-icon,
        .nav-button.active .nav-icon {
            color: white;
        }
        
        body.dark-mode .bottom-nav {
            background: rgba(45, 45, 45, 0.95);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        body.dark-mode .nav-circle {
            background: #404040;
        }
        
        body.dark-mode .nav-icon {
            color: #e0e0e0;
        }
        
        .side-nav {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100vh;
            background: #F4E9D5;
            box-shadow: 2px 0 15px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            z-index: 1001;
            overflow-y: auto;
        }
        
        .side-nav.open {
            left: 0;
        }
        
        .side-nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: linear-gradient(135deg, #8BA690 0%, #9BB69E 100%);
            color: white;
        }
        
        .side-nav-header h3 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }
        
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .side-nav-content {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 20px;
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #333;
        }
        
        .nav-item:hover {
            background: #f0f4ff;
            color: #667eea;
        }
        
        .nav-item-icon {
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .nav-divider {
            height: 1px;
            background: #eee;
            margin: 10px 20px;
        }
        
        .nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .nav-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        body.dark-mode .side-nav {
            background: #2d2d2d;
        }
        
        body.dark-mode .side-nav-header {
            border-bottom-color: #555;
        }
        
        body.dark-mode .nav-item {
            color: #e0e0e0;
        }
        
        body.dark-mode .nav-item:hover {
            background: #404040;
            color: #667eea;
        }
        
        body.dark-mode .nav-divider {
            background: #555;
        }
        
        @media (max-width: 480px) {
            .dashboard-container {
                padding: 10px;
            }
            
            .dashboard-header {
                padding: 12px;
                margin-bottom: 20px;
            }
            
            .left-section {
                gap: 8px;
            }
            
            .home-title {
                font-size: 1.3rem;
            }
            
            .header-controls {
                position: static;
                margin-top: 10px;
                justify-content: center;
                gap: 15px;
            }
            
            .toggle-container {
                flex-direction: row;
                gap: 8px;
                align-items: center;
            }
            
            .toggle-switch {
                width: 40px;
                height: 22px;
            }
            
            .toggle-slider {
                width: 16px;
                height: 16px;
                top: 2px;
                left: 2px;
            }
            
            .toggle-switch.active .toggle-slider {
                transform: translateX(18px);
            }
            
            .toggle-label {
                font-size: 0.8rem;
            }
            
            .profile-circle {
                width: 32px;
                height: 32px;
            }
            
            .profile-initial {
                font-size: 0.9rem;
            }
            
            .routine-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .routine-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .physical-activities-item .activities-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }
            
            .physical-activities-item .activity-circle {
                width: 50px;
                height: 50px;
            }
            
            .physical-activities-item .activity-icon {
                font-size: 1.3rem;
            }
            
            .physical-activities-item .activity-label {
                font-size: 0.7rem;
            }
            
            .sleep-hours-only {
                font-size: 2.5rem;
            }
            
            .sleep-hours-label {
                font-size: 0.9rem;
            }
            
            .med-symptoms-stats {
                flex-direction: column;
                gap: 15px;
            }
            
            .med-symptoms-stat-item {
                padding: 12px;
            }
            
            .med-symptoms-stat-icon {
                font-size: 1.5rem;
            }
            
            .med-symptoms-stat-value {
                font-size: 1.2rem;
            }
            
            .routine-info h2 {
                font-size: 1.4rem;
            }
            
            .routine-info p {
                font-size: 0.85rem;
            }
            
            .score-circle {
                width: 100px;
                height: 100px;
            }
            
            .circle-progress {
                width: 100px;
                height: 100px;
            }
            
            .circle-inner {
                width: 75px;
                height: 75px;
            }
            
            .score-number {
                font-size: 1.5rem;
            }
            
            .routine-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .routine-item {
                padding: 15px;
            }
            
            .routine-item-title span:last-child {
                font-size: 0.9rem;
            }
            
            .routine-content {
                font-size: 0.85rem;
                padding: 10px;
                min-height: 70px;
            }
            
            .section-title {
                font-size: 1rem;
            }
            
            
            .bottom-nav {
                bottom: 10px;
                gap: 15px;
                padding: 10px 15px;
            }
            
            .nav-circle {
                width: 40px;
                height: 40px;
            }
            
            .nav-icon {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 768px) and (min-width: 481px) {
            .dashboard-header {
                padding: 15px;
            }
            
            .left-section {
                gap: 10px;
            }
            
            .home-title {
                font-size: 1.5rem;
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
        }
        
        @media (max-width: 480px) {
            .dashboard-header {
                padding: 10px 12px;
            }
            
            .header-controls {
                top: 18px;
                right: 10px;
                gap: 6px;
            }
            
            .home-title {
                font-size: 1.3rem;
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
            
            .routine-info h2 {
                font-size: 1.4rem;
            }
            
            .routine-info p {
                font-size: 0.85rem;
            }
            
            .section-title {
                font-size: 0.9rem;
            }
            
            .routine-item-title span:last-child {
                font-size: 0.85rem;
            }
            
            .routine-content {
                font-size: 0.8rem;
                padding: 10px;
            }
        }
            
            .routine-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .routine-grid {
                grid-template-columns: 1fr;
            }
            
            .score-circle {
                width: 100px;
                height: 100px;
            }
            
            .circle-progress {
                width: 100px;
                height: 100px;
            }
            
            .circle-inner {
                width: 75px;
                height: 75px;
            }
            
            .score-number {
                font-size: 1.5rem;
            }
            
            .bottom-nav {
                bottom: 15px;
                gap: 20px;
                padding: 12px 20px;
            }
            
            .nav-circle {
                width: 45px;
                height: 45px;
            }
            
            .nav-icon {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header -->
        <div class="dashboard-header">
            <div class="left-section">
                <div class="hamburger-menu" onclick="toggleMenu()" title="Menu">
                    <div class="dash"></div>
                    <div class="dash"></div>
                    <div class="dash"></div>
                </div>
                <h1 class="home-title">🏠 HOME</h1>
            </div>
            <div class="header-controls">
                <div class="toggle-container">
                    <span class="toggle-label">Self</span>
                    <div class="toggle-switch" onclick="toggleChildMode()">
                        <div class="toggle-slider"></div>
                    </div>
                    <span class="toggle-label">Child</span>
                </div>
                <div class="profile-circle" onclick="navigateToProfileEdit()" title="Edit Profile">
                    <span class="profile-initial"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Main Routine Card -->
        <div class="routine-card">
            <!-- Header with Score Circle -->
            <div class="routine-header">
                <div class="routine-info">
                    <h2 id="routineTitle">Your Routine</h2>
                    <p id="routineSubtitle">Last assessed: <?php echo $formatted_date; ?></p>
                    <div class="mode-indicator" id="modeIndicator" style="display: none;">
                        <span class="mode-badge">👶 Tracking for Child</span>
                    </div>
                </div>
                <div class="score-section">
                    <div class="score-circle" onclick="viewDietRecommendations()" style="cursor: pointer;" title="Click to view diet recommendations">
                        <div class="circle-progress" id="scoreCircle" style="--progress-deg: <?php echo ($latest_score / 100) * 360; ?>deg">
                            <div class="circle-inner">
                                <div class="score-number"><?php echo $latest_score; ?></div>
                                <div class="score-label">score</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Routine Entry Section -->
            <div class="routine-entry-section">
                <div class="section-title">
                    <span>📝</span>
                    <span>ROUTINE ENTRY</span>
                </div>
                
                <div class="routine-grid">
                    <!-- Physical Activities Section -->
                    <div class="routine-item physical-activities-item">
                        <div class="routine-item-header">
                            <div class="routine-item-title">
                                <span class="routine-item-icon">🏃</span>
                                <span>Physical Activities</span>
                            </div>
                        </div>
                        <div class="activities-grid" id="dashboardActivities">
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
                    </div>
                    
                    <!-- Sleep Section -->
                    <div class="routine-item sleep-item">
                        <div class="routine-item-header">
                            <div class="routine-item-title">
                                <span class="routine-item-icon">🌙</span>
                                <span>Sleep</span>
                            </div>
                        </div>
                        <div class="sleep-record-container">
                            <button class="sleep-record-btn" onclick="openSleepModal()">
                                <span class="sleep-icon">🌙</span>
                                <span>Record your sleep</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Med Management Section -->
                    <div class="routine-item med-management-item">
                        <div class="routine-item-header">
                            <div class="routine-item-title">
                                <span class="routine-item-icon">💊</span>
                                <span>Med Management</span>
                            </div>
                        </div>
                        <div class="med-schedule">
                            <div class="med-time-slot">
                                <label class="med-checkbox-label">
                                    <input type="checkbox" class="med-checkbox" id="morningMed" onchange="updateMedCheckboxStates()">
                                    <span class="med-checkmark"></span>
                                    <div class="med-time-info">
                                        <span class="med-time-icon">🌅</span>
                                        <span class="med-time-text">Morning</span>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="med-time-slot">
                                <label class="med-checkbox-label">
                                    <input type="checkbox" class="med-checkbox" id="afternoonMed" onchange="updateMedCheckboxStates()">
                                    <span class="med-checkmark"></span>
                                    <div class="med-time-info">
                                        <span class="med-time-icon">☀️</span>
                                        <span class="med-time-text">Afternoon</span>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="med-time-slot">
                                <label class="med-checkbox-label">
                                    <input type="checkbox" class="med-checkbox" id="eveningMed" onchange="updateMedCheckboxStates()">
                                    <span class="med-checkmark"></span>
                                    <div class="med-time-info">
                                        <span class="med-time-icon">🌙</span>
                                        <span class="med-time-text">Evening</span>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="med-save-container">
                                <button class="med-save-btn" onclick="saveMedSchedule()">
                                    <span class="save-icon">💾</span>
                                    <span>Save Schedule</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Graph Section -->
        <div class="routine-card">
            <div class="graph-header">
                <h2 class="graph-title">Your Progress Overview</h2>
                <div class="time-toggles">
                    <div class="time-toggle active" data-period="week">Week</div>
                    <div class="time-toggle" data-period="month">Month</div>
                </div>
            </div>
            <div class="chart-container" onclick="viewProgressTracking()" style="cursor: pointer;" title="Click to view detailed progress tracking">
                <?php if ($latest_score == 0): ?>
                    <div class="no-data">
                        <p>📊 No data available yet</p>
                        <p>Start logging your daily activities to track your progress!</p>
                    </div>
                <?php else: ?>
                    <canvas id="progressChart"></canvas>
                <?php endif; ?>
            </div>
            
        </div>
        
        <!-- Bottom Navigation -->
        <div class="bottom-nav">
            <div class="nav-button active" onclick="navigateToHome()" title="Home">
                <div class="nav-circle">
                    <span class="nav-icon">🏠</span>
                </div>
            </div>
            <div class="nav-button" onclick="navigateToLeaderboard()" title="Leaderboard">
                <div class="nav-circle">
                    <span class="nav-icon">🏆</span>
                </div>
            </div>
            <div class="nav-button" onclick="navigateToProgress()" title="Progress">
                <div class="nav-circle">
                    <span class="nav-icon">📊</span>
                </div>
            </div>
            <div class="nav-button" onclick="navigateToProfile()" title="Profile">
                <div class="nav-circle">
                    <span class="nav-icon">👤</span>
                </div>
            </div>
        </div>
        
        <!-- Side Navigation Menu -->
        <div class="side-nav" id="sideNav">
            <div class="side-nav-header">
                <h3>Menu</h3>
                <button class="close-btn" onclick="closeSideNav()">×</button>
            </div>
            <div class="side-nav-content">
                <div class="nav-section">
                    <div class="nav-item" onclick="navigateToDocuments()">
                        <span class="nav-item-icon">📄</span>
                        <span>Documents</span>
                    </div>
                    <div class="nav-item" onclick="navigateToReports()">
                        <span class="nav-item-icon">📊</span>
                        <span>Health Reports</span>
                    </div>
                    <div class="nav-item" onclick="navigateToInfo()">
                        <span class="nav-item-icon">ℹ️</span>
                        <span>Info</span>
                    </div>
                    <div class="nav-item" onclick="navigateToPreferences()">
                        <span class="nav-item-icon">⚙️</span>
                        <span>Edit Preferences</span>
                    </div>
                </div>
                <div class="nav-divider"></div>
                <div class="nav-section">
                    <div class="nav-item" onclick="navigateToSettings()">
                        <span class="nav-item-icon">🔧</span>
                        <span>Settings</span>
                    </div>
                    <div class="nav-item" onclick="navigateToHelp()">
                        <span class="nav-item-icon">❓</span>
                        <span>Help & Support</span>
                    </div>
                    <div class="nav-item" onclick="logout()">
                        <span class="nav-item-icon">🚪</span>
                        <span>Logout</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overlay for side nav -->
        <div class="nav-overlay" id="navOverlay" onclick="closeSideNav()"></div>
        
        <!-- Sleep Recording Modal -->
        <div class="sleep-modal" id="sleepModal">
            <div class="sleep-modal-content">
                <div class="sleep-modal-header">
                    <h3>Record sleep</h3>
                    <button class="close-btn" onclick="closeSleepModal()">×</button>
                </div>
                
                <div class="sleep-tracker">
                    <div class="sleep-circle-container">
                        <div class="sleep-circle">
                            <div class="sleep-arc" id="sleepArc"></div>
                            <div class="sleep-center">
                                <div class="sleep-icon-center">🌙</div>
                                <div class="sleep-time-display">
                                    <div class="sleep-label">Time asleep</div>
                                    <div class="sleep-duration" id="sleepDuration">8h 00m</div>
                                </div>
                                <div class="wake-icon-center">☀️</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sleep-time-inputs">
                        <div class="time-input-group">
                            <input type="time" class="time-input" id="bedTimeInput" value="22:00">
                            <label>Went to bed</label>
                        </div>
                        <div class="time-input-group">
                            <input type="time" class="time-input" id="wakeTimeInput" value="06:00">
                            <label>Woke up</label>
                        </div>
                    </div>
                    
                    <button class="ok-btn" onclick="saveSleepRecord()">OK</button>
                </div>
            </div>
        </div>
        
    </div>
    
    <script>
        // Load routine entries on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadRoutineEntries();
            animateScoreCircle();
            initializeChart();
            initializeTimeToggles();
            loadParentalModePreference();
        });
        
        let currentPeriod = 'week';
        let chart = null;
        
        // Navigate to diet recommendations page
        function viewDietRecommendations() {
            const score = <?php echo $latest_score; ?>;
            window.location.href = `diet_recommendations.php?score=${score}`;
        }
        
        // Navigate to progress tracking page
        function viewProgressTracking() {
            window.location.href = 'progress_tracking.php';
        }
        
        // Initialize Activity Chart
        function initializeActivityChart() {
            const activityCtx = document.getElementById('activityChart');
            if (activityCtx) {
                const activityData = generateActivityData();
                
                new Chart(activityCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['M', 'T', 'W', 'T', 'F', 'S', 'S'],
                        datasets: [{
                            label: 'Activity Level',
                            data: activityData,
                            backgroundColor: [
                                '#667eea', '#667eea', '#667eea', '#667eea', 
                                '#667eea', '#667eea', '#667eea'
                            ],
                            borderColor: '#667eea',
                            borderWidth: 1,
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 120,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#666'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#666'
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Generate activity data
        function generateActivityData() {
            const data = [];
            for (let i = 0; i < 7; i++) {
                data.push(Math.floor(Math.random() * 100) + 20); // 20-120 range
            }
            return data;
        }
        
        // Navigate to leaderboard page
        function viewLeaderboard() {
            window.location.href = 'leaderboard.php';
        }
        
        
        // Toggle parent mode
        function toggleParentMode() {
            const toggleSwitch = document.querySelector('.toggle-switch');
            const modeIndicator = document.getElementById('modeIndicator');
            const routineTitle = document.getElementById('routineTitle');
            const routineSubtitle = document.getElementById('routineSubtitle');
            
            toggleSwitch.classList.toggle('active');
            
            const isParentMode = toggleSwitch.classList.contains('active');
            
            if (isParentMode) {
                // Switch to parent mode
                modeIndicator.style.display = 'block';
                routineTitle.textContent = "Child's Routine";
                routineSubtitle.textContent = "Last assessed: <?php echo $child_date; ?>";
                
                // Update score circle for child
                updateScoreCircle(<?php echo $child_score; ?>);
                
                // Update all placeholders to child mode
                updatePlaceholdersForChild();
            } else {
                // Switch back to personal mode
                modeIndicator.style.display = 'none';
                routineTitle.textContent = "Your Routine";
                routineSubtitle.textContent = "Last assessed: <?php echo $adult_date; ?>";
                
                // Update score circle for adult
                updateScoreCircle(<?php echo $adult_score; ?>);
                
                
                // Update all placeholders to personal mode
                updatePlaceholdersForSelf();
            }
            
            // Save preference to localStorage
            localStorage.setItem('parentMode', isParentMode);
            
            // Load appropriate data
            loadRoutineEntries();
        }
        
        // Toggle profile dropdown/menu
        function toggleProfile() {
            // Add profile menu functionality here
            alert('Profile menu - coming soon!');
        }
        
        // Toggle hamburger menu
        function toggleMenu() {
            const hamburger = document.querySelector('.hamburger-menu');
            const sideNav = document.getElementById('sideNav');
            const overlay = document.getElementById('navOverlay');
            
            hamburger.classList.toggle('active');
            sideNav.classList.add('open');
            overlay.classList.add('active');
        }
        
        // Close side navigation
        function closeSideNav() {
            const hamburger = document.querySelector('.hamburger-menu');
            const sideNav = document.getElementById('sideNav');
            const overlay = document.getElementById('navOverlay');
            
            hamburger.classList.remove('active');
            sideNav.classList.remove('open');
            overlay.classList.remove('active');
        }
        
        // Bottom navigation functions
        function navigateToHome() {
            // Already on home page, just update active state
            updateActiveNavButton(0);
        }
        
        function navigateToLeaderboard() {
            // Navigate to leaderboard page
            window.location.href = 'leaderboard.php';
        }
        
        function navigateToProgress() {
            // Navigate to progress tracking page
            window.location.href = 'progress_tracking.php';
        }
        
        function navigateToProfile() {
            // Navigate to user profile view page
            window.location.href = 'user_profile.php';
        }
        
        function navigateToProfileEdit() {
            // Navigate to profile editing page
            console.log('Navigating to profile edit page...');
            window.location.href = 'profile.php';
        }
        
        // Update active navigation button
        function updateActiveNavButton(activeIndex) {
            const navButtons = document.querySelectorAll('.nav-button');
            navButtons.forEach((button, index) => {
                if (index === activeIndex) {
                    button.classList.add('active');
                } else {
                    button.classList.remove('active');
                }
            });
        }
        
        // Toggle child/parent mode
        function toggleChildMode() {
            const toggleSwitch = document.querySelector('.toggle-switch');
            const routineTitle = document.getElementById('routineTitle');
            
            toggleSwitch.classList.toggle('active');
            
            if (toggleSwitch.classList.contains('active')) {
                // Child mode
                routineTitle.textContent = "Your Child's Routine";
                localStorage.setItem('parentalMode', 'true');
            } else {
                // Self mode
                routineTitle.textContent = "Your Routine";
                localStorage.setItem('parentalMode', 'false');
            }
        }
        
        // Load parental mode preference on page load
        function loadParentalModePreference() {
            const isParentalMode = localStorage.getItem('parentalMode') === 'true';
            const toggleSwitch = document.querySelector('.toggle-switch');
            const routineTitle = document.getElementById('routineTitle');
            
            if (isParentalMode) {
                toggleSwitch.classList.add('active');
                routineTitle.textContent = "Your Child's Routine";
            }
        }

        // Side navigation menu functions
        function navigateToDocuments() {
            closeSideNav();
            window.location.href = 'documents.php';
        }
        
        function navigateToReports() {
            closeSideNav();
            window.location.href = 'diet_recommendations.php?score=<?php echo $latest_score; ?>';
        }
        
        function navigateToInfo() {
            closeSideNav();
            window.location.href = 'info.php';
        }
        
        function navigateToPreferences() {
            closeSideNav();
            alert('Edit Preferences - coming soon!');
        }
        
        function navigateToSettings() {
            closeSideNav();
            alert('Settings page - coming soon!');
        }
        
        function navigateToHelp() {
            closeSideNav();
            alert('Help & Support - coming soon!');
        }
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // Load parent mode preference on page load
        function loadParentModePreference() {
            const isParentMode = localStorage.getItem('parentMode') === 'true';
            if (isParentMode) {
                const toggleSwitch = document.querySelector('.toggle-switch');
                toggleSwitch.classList.add('active');
                
                const modeIndicator = document.getElementById('modeIndicator');
                const routineTitle = document.getElementById('routineTitle');
                const routineSubtitle = document.getElementById('routineSubtitle');
                
                modeIndicator.style.display = 'block';
                routineTitle.textContent = "Child's Routine";
                routineSubtitle.textContent = "Last assessed: <?php echo $child_date; ?>";
                
                // Update score circle for child
                updateScoreCircle(<?php echo $child_score; ?>);
                
                updatePlaceholdersForChild();
            }
        }
        
        // Update placeholders for child mode
        function updatePlaceholdersForChild() {
            const textareas = document.querySelectorAll('.routine-content');
            textareas.forEach(textarea => {
                const id = textarea.id;
                if (id === 'diet-content') {
                    textarea.placeholder = "Add your child's meals, snacks, and dietary preferences...";
                } else if (id === 'activities-content') {
                    textarea.placeholder = "Add your child's exercise routines, sports, or physical activities...";
                } else if (id === 'sleep-content') {
                    textarea.placeholder = "Add your child's sleep schedule, bedtime routine, or sleep goals...";
                } else if (id === 'medication-content') {
                    textarea.placeholder = "Add your child's medications, supplements, or medical reminders...";
                }
            });
        }
        
        // Update placeholders for self mode
        function updatePlaceholdersForSelf() {
            const textareas = document.querySelectorAll('.routine-content');
            textareas.forEach(textarea => {
                const id = textarea.id;
                if (id === 'diet-content') {
                    textarea.placeholder = "Add your meals, snacks, and dietary preferences...";
                } else if (id === 'activities-content') {
                    textarea.placeholder = "Add your exercise routines, sports, or physical activities...";
                } else if (id === 'sleep-content') {
                    textarea.placeholder = "Add your sleep schedule, bedtime routine, or sleep goals...";
                } else if (id === 'medication-content') {
                    textarea.placeholder = "Add your medications, supplements, or medical reminders...";
                }
            });
        }
        
        // Update score circle with new score
        function updateScoreCircle(score) {
            const scoreElement = document.querySelector('.score-number');
            const scoreCircle = document.getElementById('scoreCircle');
            
            if (scoreElement) {
                scoreElement.textContent = score;
            }
            
            if (scoreCircle && score > 0) {
                // Set color based on score
                let color = '#4CAF50'; // Green for good scores
                if (score < 50) {
                    color = '#f44336'; // Red for low scores
                } else if (score < 70) {
                    color = '#ff9800'; // Orange for medium scores
                }
                
                const progressDeg = (score / 100) * 360;
                scoreCircle.style.background = `conic-gradient(${color} 0deg, ${color} ${progressDeg}deg, #e0e0e0 ${progressDeg}deg, #e0e0e0 360deg)`;
            }
        }
        
        // Animate score circle on load
        function animateScoreCircle() {
            const scoreCircle = document.getElementById('scoreCircle');
            const targetScore = <?php echo $latest_score; ?>;
            
            if (targetScore > 0) {
                // Set color based on score
                let color = '#4CAF50'; // Green for good scores
                if (targetScore < 50) {
                    color = '#f44336'; // Red for low scores
                } else if (targetScore < 70) {
                    color = '#ff9800'; // Orange for medium scores
                }
                
                const progressDeg = (targetScore / 100) * 360;
                scoreCircle.style.background = `conic-gradient(${color} 0deg, ${color} ${progressDeg}deg, #e0e0e0 ${progressDeg}deg, #e0e0e0 360deg)`;
            }
        }
        
        // Save routine entry to localStorage
        function saveRoutineEntry(type, content) {
            const isParentMode = document.querySelector('.toggle-switch').classList.contains('active');
            const storageKey = isParentMode ? 'childRoutineEntries' : 'routineEntries';
            
            const entries = JSON.parse(localStorage.getItem(storageKey) || '{}');
            entries[type] = content;
            localStorage.setItem(storageKey, JSON.stringify(entries));
            
            // Also save to database via AJAX (optional)
            fetch('save_routine.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    content: content,
                    isParentMode: isParentMode
                })
            }).catch(error => {
                console.log('Could not save to database:', error);
            });
        }
        
        // Load routine entries from localStorage
        function loadRoutineEntries() {
            const types = ['diet', 'activities', 'sleep', 'medication'];
            const isParentMode = document.querySelector('.toggle-switch').classList.contains('active');
            const storageKey = isParentMode ? 'childRoutineEntries' : 'routineEntries';
            
            const entries = JSON.parse(localStorage.getItem(storageKey) || '{}');
            
            types.forEach(type => {
                const textarea = document.getElementById(type + '-content');
                if (textarea) {
                    if (entries[type]) {
                        textarea.value = entries[type];
                        textarea.classList.remove('empty');
                    } else {
                        textarea.value = '';
                        textarea.classList.add('empty');
                    }
                }
            });
        }
        
        // Add routine entry template
        function addRoutineEntry(type) {
            const textarea = document.getElementById(`${type}-content`);
            if (textarea) {
                textarea.focus();
                textarea.classList.remove('empty');
                
                // Add a template based on type
                const templates = {
                    diet: '• Breakfast: \n• Lunch: \n• Dinner: \n• Snacks: ',
                    activities: '• Morning: \n• Afternoon: \n• Evening: ',
                    sleep: '• Bedtime: \n• Wake time: \n• Sleep goal: ',
                    medication: '• Morning: \n• Afternoon: \n• Evening: \n• Notes: '
                };
                
                if (!textarea.value.trim()) {
                    textarea.value = templates[type] || '';
                    saveRoutineEntry(type, textarea.value);
                }
            }
        }
        
        // Clear routine entry
        function clearRoutineEntry(type) {
            const textarea = document.getElementById(`${type}-content`);
            if (textarea) {
                textarea.value = '';
                textarea.classList.add('empty');
                saveRoutineEntry(type, '');
            }
        }
        
        // Handle textarea focus/blur for styling
        document.querySelectorAll('.routine-content').forEach(textarea => {
            textarea.addEventListener('focus', function() {
                this.classList.remove('empty');
            });
            
            textarea.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('empty');
                }
            });
            
            textarea.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.classList.remove('empty');
                } else {
                    this.classList.add('empty');
                }
            });
        });
        
        // Initialize chart
        function initializeChart() {
            <?php if ($latest_score > 0): ?>
            const ctx = document.getElementById('progressChart');
            if (ctx) {
                const sampleData = generateSampleData();
                
                chart = new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: sampleData.labels,
                        datasets: [{
                            label: 'Wellness Score',
                            data: sampleData.data,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#667eea',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 10,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#666',
                                    stepSize: 1
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#666'
                                }
                            }
                        }
                    }
                });
            }
            <?php endif; ?>
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
                    updateChart();
                });
            });
        }
        
        // Update chart data
        function updateChart() {
            if (chart) {
                const newData = generateSampleData();
                chart.data.labels = newData.labels;
                chart.data.datasets[0].data = newData.data;
                chart.update();
            }
        }
        
        // Generate sample data
        function generateSampleData() {
            const baseScore = <?php echo $latest_score; ?>;
            let labels, data;
            
            if (currentPeriod === 'week') {
                labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                data = labels.map(() => Math.max(0, Math.min(100, baseScore + (Math.random() - 0.5) * 20)));
            } else {
                labels = [];
                data = [];
                for (let i = 29; i >= 0; i--) {
                    const date = new Date();
                    date.setDate(date.getDate() - i);
                    labels.push(date.getDate() + '/' + (date.getMonth() + 1));
                    data.push(Math.max(0, Math.min(100, baseScore + (Math.random() - 0.5) * 25)));
                }
            }
            
            return { labels, data };
        }
        
        // Physical Activities functionality
        function initializePhysicalActivities() {
            const activityItems = document.querySelectorAll('#dashboardActivities .activity-item');
            
            // Load saved activities from localStorage
            const savedActivities = JSON.parse(localStorage.getItem('selectedActivities') || '[]');
            
            // Apply saved selections
            activityItems.forEach(item => {
                if (savedActivities.includes(item.dataset.value)) {
                    item.classList.add('selected');
                }
            });
            
            // Add click handlers
            activityItems.forEach(item => {
                item.addEventListener('click', function() {
                    this.classList.toggle('selected');
                    saveSelectedActivities();
                });
            });
        }
        
        function saveSelectedActivities() {
            const selectedItems = document.querySelectorAll('#dashboardActivities .activity-item.selected');
            const selectedActivities = Array.from(selectedItems).map(item => item.dataset.value);
            localStorage.setItem('selectedActivities', JSON.stringify(selectedActivities));
            
            // Optional: Send to server to save in database
            // You can add AJAX call here if needed
        }
        
        // Initialize physical activities when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializePhysicalActivities();
        });
        
        // Sleep Modal Functions
        function openSleepModal() {
            document.getElementById('sleepModal').classList.add('active');
            updateSleepDisplay();
        }
        
        function closeSleepModal() {
            document.getElementById('sleepModal').classList.remove('active');
        }
        
        function updateSleepDisplay() {
            const bedTime = document.getElementById('bedTimeInput').value;
            const wakeTime = document.getElementById('wakeTimeInput').value;
            
            if (!bedTime || !wakeTime) return;
            
            // Calculate sleep duration
            const bedHour = parseInt(bedTime.split(':')[0]);
            const bedMinute = parseInt(bedTime.split(':')[1]);
            const wakeHour = parseInt(wakeTime.split(':')[0]);
            const wakeMinute = parseInt(wakeTime.split(':')[1]);
            
            let sleepMinutes = (wakeHour * 60 + wakeMinute) - (bedHour * 60 + bedMinute);
            if (sleepMinutes < 0) sleepMinutes += 24 * 60; // Handle overnight sleep
            
            const sleepHours = Math.floor(sleepMinutes / 60);
            const remainingMinutes = sleepMinutes % 60;
            
            document.getElementById('sleepDuration').textContent = `${sleepHours}h ${remainingMinutes.toString().padStart(2, '0')}m`;
            
            // Update the arc
            const arcDegrees = (sleepMinutes / (24 * 60)) * 360;
            document.getElementById('sleepArc').style.background = 
                `conic-gradient(from 270deg, #48bb78 0deg, #48bb78 ${arcDegrees}deg, transparent ${arcDegrees}deg)`;
        }
        
        function saveSleepRecord() {
            const bedTime = document.getElementById('bedTimeInput').value;
            const wakeTime = document.getElementById('wakeTimeInput').value;
            const duration = document.getElementById('sleepDuration').textContent;
            
            if (!bedTime || !wakeTime) {
                alert('Please set both bed time and wake time');
                return;
            }
            
            // Save to localStorage
            const sleepData = {
                bedTime: bedTime,
                wakeTime: wakeTime,
                duration: duration,
                date: new Date().toDateString()
            };
            
            localStorage.setItem('lastSleepRecord', JSON.stringify(sleepData));
            
            // Close modal
            closeSleepModal();
            
            // Show success message
            alert('Sleep record saved successfully!');
        }
        
        // Time input handlers
        document.addEventListener('DOMContentLoaded', function() {
            const bedTimeInput = document.getElementById('bedTimeInput');
            const wakeTimeInput = document.getElementById('wakeTimeInput');
            
            if (bedTimeInput && wakeTimeInput) {
                bedTimeInput.addEventListener('change', updateSleepDisplay);
                wakeTimeInput.addEventListener('change', updateSleepDisplay);
                
                // Load saved sleep data if available
                const savedSleep = localStorage.getItem('lastSleepRecord');
                if (savedSleep) {
                    const sleepData = JSON.parse(savedSleep);
                    bedTimeInput.value = sleepData.bedTime;
                    wakeTimeInput.value = sleepData.wakeTime;
                }
            }
        });
        
        // Initialize Sleep Cycle Chart
        function initializeSleepCycleChart() {
            const sleepCtx = document.getElementById('sleepCycleChart');
            if (sleepCtx) {
                const sleepData = generateSleepCycleData();
                
                new Chart(sleepCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: sleepData.labels,
                        datasets: [{
                            label: 'Sleep Stages',
                            data: sleepData.data,
                            borderColor: '#4a5568',
                            backgroundColor: 'rgba(74, 85, 104, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#4a5568',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Sleep Cycle Pattern',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                color: '#2d3748'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 4,
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#666',
                                    stepSize: 1,
                                    callback: function(value) {
                                        const stages = ['Awake', 'Light', 'Deep', 'REM'];
                                        return stages[value] || '';
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.1)'
                                },
                                ticks: {
                                    color: '#666'
                                }
                            }
                        }
                    }
                });
            }
        }
        
        // Generate random sleep cycle data
        function generateSleepCycleData() {
            const userId = <?php echo $_SESSION['user_id'] ?? 1; ?>;
            
            // Generate random sleep cycle data
            const labels = [];
            const data = [];
            
            // Create 8-hour sleep cycle (22:00 to 06:00)
            for (let i = 0; i <= 16; i++) { // 30-minute intervals
                const hour = Math.floor(i / 2) + 22;
                const minute = (i % 2) * 30;
                const displayHour = hour > 24 ? hour - 24 : hour;
                labels.push(`${displayHour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`);
                
                // Generate completely random sleep stages
                let stage;
                const randomValue = Math.random();
                
                if (randomValue < 0.25) {
                    stage = 0; // Awake
                } else if (randomValue < 0.5) {
                    stage = 1; // Light sleep
                } else if (randomValue < 0.75) {
                    stage = 2; // Deep sleep
                } else {
                    stage = 3; // REM sleep
                }
                
                // Add some smoothing to avoid too erratic changes
                if (i > 0 && Math.abs(stage - data[i-1]) > 2) {
                    stage = data[i-1] + (Math.random() > 0.5 ? 1 : -1);
                    stage = Math.max(0, Math.min(3, stage));
                }
                
                data.push(stage);
            }
            
            return { labels, data };
        }
        
        // Med Management Functions
        function saveMedSchedule() {
            const morningChecked = document.getElementById('morningMed').checked;
            const afternoonChecked = document.getElementById('afternoonMed').checked;
            const eveningChecked = document.getElementById('eveningMed').checked;
            
            const medSchedule = {
                morning: morningChecked,
                afternoon: afternoonChecked,
                evening: eveningChecked,
                date: new Date().toDateString(),
                savedAt: new Date().toLocaleTimeString()
            };
            
            // Save to localStorage
            localStorage.setItem('medSchedule', JSON.stringify(medSchedule));
            
            // Update visual states
            updateMedCheckboxStates();
            
            // Show save feedback
            showSaveConfirmation();
        }
        
        function showSaveConfirmation() {
            const saveBtn = document.querySelector('.med-save-btn');
            const originalText = saveBtn.innerHTML;
            
            // Show success state
            saveBtn.innerHTML = '<span class="save-icon">✅</span><span>Saved!</span>';
            saveBtn.style.background = 'linear-gradient(135deg, #48bb78, #38a169)';
            
            // Reset after 2 seconds
            setTimeout(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
            }, 2000);
        }
        
        function updateMedCheckboxStates() {
            const checkboxes = document.querySelectorAll('.med-checkbox');
            checkboxes.forEach(checkbox => {
                const label = checkbox.closest('.med-checkbox-label');
                if (checkbox.checked) {
                    label.classList.add('checked');
                } else {
                    label.classList.remove('checked');
                }
            });
        }
        
        function loadMedSchedule() {
            const savedSchedule = localStorage.getItem('medSchedule');
            const today = new Date().toDateString();
            
            if (savedSchedule) {
                const schedule = JSON.parse(savedSchedule);
                
                // Only load if it's from today, otherwise reset
                if (schedule.date === today) {
                    document.getElementById('morningMed').checked = schedule.morning;
                    document.getElementById('afternoonMed').checked = schedule.afternoon;
                    document.getElementById('eveningMed').checked = schedule.evening;
                } else {
                    // Reset for new day and clear old data
                    resetMedSchedule();
                }
            } else {
                // No saved data, start fresh
                resetMedSchedule();
            }
            updateMedCheckboxStates();
        }
        
        function resetMedSchedule() {
            // Reset all checkboxes
            document.getElementById('morningMed').checked = false;
            document.getElementById('afternoonMed').checked = false;
            document.getElementById('eveningMed').checked = false;
            
            // Clear old localStorage data
            localStorage.removeItem('medSchedule');
            
            // Update visual states
            updateMedCheckboxStates();
        }
        
        function checkDailyReset() {
            const savedSchedule = localStorage.getItem('medSchedule');
            const today = new Date().toDateString();
            
            if (savedSchedule) {
                const schedule = JSON.parse(savedSchedule);
                
                // If the saved date is not today, reset everything
                if (schedule.date !== today) {
                    resetMedSchedule();
                    console.log('Med schedule reset for new day:', today);
                }
            }
        }
        
        // Initialize med management when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadMedSchedule();
            
            // Check for daily reset every 5 minutes
            setInterval(checkDailyReset, 5 * 60 * 1000); // 5 minutes in milliseconds
        });
        
        // Also check when page becomes visible (user switches back to tab)
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                checkDailyReset();
            }
        });
        
    </script>
</body>
</html>
