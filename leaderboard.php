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

// Get leaderboard data
$community_leaders = [];
$family_leaders = [];
$user_stats = ['rank' => 0, 'score' => 0, 'streak' => 0, 'points_gained' => 0];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db !== null) {
        // Get community leaderboard (top 10 users by latest assessment score)
        $community_query = "SELECT u.username, ua.score, ua.assessment_date,
                           COALESCE(daily_streak.streak, 0) as streak,
                           COALESCE(recent_points.points, 0) as recent_points
                           FROM users u
                           LEFT JOIN user_assessments ua ON u.id = ua.user_id
                           LEFT JOIN (
                               SELECT user_id, COUNT(*) as streak
                               FROM user_daily_logs 
                               WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                               GROUP BY user_id
                           ) daily_streak ON u.id = daily_streak.user_id
                           LEFT JOIN (
                               SELECT user_id, SUM(diet_score + mood_score + (activity_minutes/10)) as points
                               FROM user_daily_logs 
                               WHERE log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                               GROUP BY user_id
                           ) recent_points ON u.id = recent_points.user_id
                           WHERE ua.assessment_date = (
                               SELECT MAX(assessment_date) 
                               FROM user_assessments ua2 
                               WHERE ua2.user_id = u.id
                           )
                           ORDER BY ua.score DESC, daily_streak.streak DESC
                           LIMIT 10";
        
        $stmt = $db->prepare($community_query);
        $stmt->execute();
        $community_leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get current user's stats
        $user_query = "SELECT 
                       (SELECT score FROM user_assessments WHERE user_id = :user_id ORDER BY assessment_date DESC LIMIT 1) as score,
                       (SELECT COUNT(*) FROM user_daily_logs WHERE user_id = :user_id AND log_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as streak,
                       (SELECT SUM(diet_score + mood_score + (activity_minutes/10)) FROM user_daily_logs WHERE user_id = :user_id AND log_date >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)) as points_gained";
        
        $stmt = $db->prepare($user_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user_stats['score'] = $user_data['score'] ?? 0;
            $user_stats['streak'] = $user_data['streak'] ?? 0;
            $user_stats['points_gained'] = $user_data['points_gained'] ?? 0;
        }
        
        // Calculate user's rank
        $rank_query = "SELECT COUNT(*) + 1 as rank FROM user_assessments ua1
                       JOIN users u ON ua1.user_id = u.id
                       WHERE ua1.score > :user_score
                       AND ua1.assessment_date = (
                           SELECT MAX(assessment_date) 
                           FROM user_assessments ua2 
                           WHERE ua2.user_id = ua1.user_id
                       )";
        
        $stmt = $db->prepare($rank_query);
        $stmt->bindParam(':user_score', $user_stats['score']);
        $stmt->execute();
        $rank_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $user_stats['rank'] = $rank_data['rank'] ?? 1;
        
        // For demo purposes, create some family data
        $family_leaders = [
            ['name' => 'Mom', 'score' => 85, 'streak' => 12, 'relation' => 'Mother'],
            ['name' => 'Dad', 'score' => 78, 'streak' => 8, 'relation' => 'Father'],
            ['name' => 'Sister', 'score' => 72, 'streak' => 15, 'relation' => 'Sister'],
            ['name' => 'You', 'score' => $user_stats['score'], 'streak' => $user_stats['streak'], 'relation' => 'Self']
        ];
        
        // Sort family by score
        usort($family_leaders, function($a, $b) {
            return $b['score'] - $a['score'];
        });
    }
} catch (Exception $e) {
    error_log("Leaderboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HealthFirst - Leaderboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .leaderboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .leaderboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            margin: 0;
        }
        
        .back-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .back-btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .leaderboard-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-btn:hover:not(.active) {
            background: #f0f4ff;
        }
        
        .leaderboard-content {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .leader-item {
            display: flex;
            align-items: center;
            padding: 20px;
            margin-bottom: 15px;
            background: #f8f9ff;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .leader-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .leader-item.current-user {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border: 2px solid #667eea;
        }
        
        .rank-badge {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 20px;
        }
        
        .rank-1 { background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%); color: white; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0 0%, #A9A9A9 100%); color: white; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32 0%, #B8860B 100%); color: white; }
        .rank-other { background: #e0e0e0; color: #666; }
        
        .leader-info {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .leader-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }
        
        .leader-details h3 {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .leader-details p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .leader-stats {
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-item .value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            display: block;
        }
        
        .stat-item .label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .progress-bar {
            width: 100px;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }
        
        .streak-indicator {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .streak-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e0e0e0;
        }
        
        .streak-dot.active {
            background: #4CAF50;
        }
        
        .streak-heart {
            color: #e74c3c;
            font-size: 1.2rem;
            margin-left: 5px;
        }
        
        .points-gained {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #FFA726 0%, #FF9800 100%);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .lightning-icon {
            font-size: 1.1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        @media (max-width: 480px) {
            .leaderboard-container {
                padding: 10px;
            }
            
            .leaderboard-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                padding: 12px;
                margin-bottom: 15px;
            }
            
            .header-title {
                order: 2;
            }
            
            .back-btn {
                order: 1;
                align-self: flex-start;
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            
            .header-title h1 {
                font-size: 1.3rem;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-icon {
                font-size: 2rem;
                margin-bottom: 8px;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .stat-label {
                font-size: 0.8rem;
            }
            
            .leaderboard-tabs {
                gap: 5px;
                margin-bottom: 15px;
            }
            
            .tab-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .leaderboard-content {
                padding: 15px;
            }
            
            .leader-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
                padding: 15px;
                margin-bottom: 10px;
            }
            
            .rank-badge {
                width: 35px;
                height: 35px;
                font-size: 1rem;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .leader-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .leader-avatar {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .leader-details h3 {
                font-size: 1rem;
            }
            
            .leader-details p {
                font-size: 0.8rem;
            }
            
            .leader-stats {
                justify-content: center;
                gap: 15px;
                flex-wrap: wrap;
            }
            
            .stat-item .value {
                font-size: 1rem;
            }
            
            .stat-item .label {
                font-size: 0.7rem;
            }
            
            .progress-bar {
                width: 80px;
            }
            
            .points-gained {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            .streak-indicator {
                gap: 3px;
            }
            
            .streak-dot {
                width: 10px;
                height: 10px;
            }
        }
        
        @media (max-width: 768px) and (min-width: 481px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .leader-item {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .leader-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="leaderboard-container">
        <!-- Header -->
        <div class="leaderboard-header">
            <a href="dashboard.php" class="back-btn">
                <span>←</span> Back
            </a>
            <div class="header-title">
                <h1>🏆 Leaderboard</h1>
            </div>
        </div>
        
        <!-- Stats Overview -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="stat-icon">🏅</div>
                <div class="stat-value">#<?php echo $user_stats['rank']; ?></div>
                <div class="stat-label">Your Rank</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo $user_stats['score']; ?></div>
                <div class="stat-label">Health Score</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?php echo $user_stats['streak']; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div class="stat-value">+<?php echo $user_stats['points_gained']; ?></div>
                <div class="stat-label">Points Today</div>
            </div>
        </div>
        
        <!-- Leaderboard Tabs -->
        <div class="leaderboard-tabs">
            <button class="tab-btn active" onclick="showTab('community')">🌍 Community</button>
            <button class="tab-btn" onclick="showTab('family')">👨‍👩‍👧‍👦 Family</button>
        </div>
        
        <!-- Community Leaderboard -->
        <div class="leaderboard-content" id="community-tab">
            <h2 style="margin-bottom: 25px; color: #333;">Community Leaders</h2>
            
            <?php if (empty($community_leaders)): ?>
                <div class="empty-state">
                    <h3>No community data yet</h3>
                    <p>Complete your health assessment to join the leaderboard!</p>
                </div>
            <?php else: ?>
                <?php foreach ($community_leaders as $index => $leader): ?>
                    <div class="leader-item <?php echo $leader['username'] === $username ? 'current-user' : ''; ?>">
                        <div class="rank-badge <?php 
                            if ($index === 0) echo 'rank-1';
                            elseif ($index === 1) echo 'rank-2';
                            elseif ($index === 2) echo 'rank-3';
                            else echo 'rank-other';
                        ?>">
                            <?php echo $index + 1; ?>
                        </div>
                        
                        <div class="leader-info">
                            <div class="leader-avatar">
                                <?php echo strtoupper(substr($leader['username'], 0, 1)); ?>
                            </div>
                            <div class="leader-details">
                                <h3><?php echo htmlspecialchars($leader['username']); ?></h3>
                                <p>Last active: <?php echo date('M j', strtotime($leader['assessment_date'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="leader-stats">
                            <div class="stat-item">
                                <span class="value"><?php echo $leader['score']; ?></span>
                                <span class="label">Score</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $leader['score']; ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="stat-item">
                                <div class="streak-indicator">
                                    <?php for ($i = 0; $i < 7; $i++): ?>
                                        <div class="streak-dot <?php echo $i < $leader['streak'] ? 'active' : ''; ?>"></div>
                                    <?php endfor; ?>
                                    <span class="streak-heart">❤️</span>
                                </div>
                                <span class="label"><?php echo $leader['streak']; ?> day streak</span>
                            </div>
                            
                            <?php if ($leader['recent_points'] > 0): ?>
                                <div class="points-gained">
                                    <span class="lightning-icon">⚡</span>
                                    +<?php echo $leader['recent_points']; ?>XP
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Family Leaderboard -->
        <div class="leaderboard-content" id="family-tab" style="display: none;">
            <h2 style="margin-bottom: 25px; color: #333;">Family Leaders</h2>
            
            <?php foreach ($family_leaders as $index => $member): ?>
                <div class="leader-item <?php echo $member['name'] === 'You' ? 'current-user' : ''; ?>">
                    <div class="rank-badge <?php 
                        if ($index === 0) echo 'rank-1';
                        elseif ($index === 1) echo 'rank-2';
                        elseif ($index === 2) echo 'rank-3';
                        else echo 'rank-other';
                    ?>">
                        <?php echo $index + 1; ?>
                    </div>
                    
                    <div class="leader-info">
                        <div class="leader-avatar">
                            <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                        </div>
                        <div class="leader-details">
                            <h3><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p><?php echo $member['relation']; ?></p>
                        </div>
                    </div>
                    
                    <div class="leader-stats">
                        <div class="stat-item">
                            <span class="value"><?php echo $member['score']; ?></span>
                            <span class="label">Score</span>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $member['score']; ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="streak-indicator">
                                <?php for ($i = 0; $i < 7; $i++): ?>
                                    <div class="streak-dot <?php echo $i < $member['streak'] ? 'active' : ''; ?>"></div>
                                <?php endfor; ?>
                                <span class="streak-heart">❤️</span>
                            </div>
                            <span class="label"><?php echo $member['streak']; ?> day streak</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.getElementById('community-tab').style.display = 'none';
            document.getElementById('family-tab').style.display = 'none';
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').style.display = 'block';
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>
