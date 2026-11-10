
<?php
session_start();
require_once "connection.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Get user information and reward points
$stmt = mysqli_prepare(
    $conn,
    "SELECT name, email, phone, reward_points
     FROM users WHERE user_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);

mysqli_stmt_close($stmt);

if (!$user) {
    die("User not found!");
}

$reward_points = (int)$user["reward_points"];

// Badge system
if ($reward_points >= 200) {
    $badge = "NavSafe Champion";
    $badge_icon = "💎";
    $next_badge = "Maximum Badge Achieved!";
    $progress = 100;
    $remaining = 0;

} elseif ($reward_points >= 100) {
    $badge = "Road Guardian";
    $badge_icon = "🏆";
    $next_badge = "NavSafe Champion";
    $progress = (($reward_points - 100) / 100) * 100;
    $remaining = 200 - $reward_points;

} elseif ($reward_points >= 50) {
    $badge = "Safety Scout";
    $badge_icon = "🥇";
    $next_badge = "Road Guardian";
    $progress = (($reward_points - 50) / 50) * 100;
    $remaining = 100 - $reward_points;

} elseif ($reward_points >= 20) {
    $badge = "Road Watcher";
    $badge_icon = "🥈";
    $next_badge = "Safety Scout";
    $progress = (($reward_points - 20) / 30) * 100;
    $remaining = 50 - $reward_points;

} else {
    $badge = "New Reporter";
    $badge_icon = "🥉";
    $next_badge = "Road Watcher";
    $progress = ($reward_points / 20) * 100;
    $remaining = 20 - $reward_points;
}

$progress = min(100, max(0, $progress));

// Get user's submitted reports
$stmt = mysqli_prepare(
    $conn,
    "SELECT h.hazard_type, h.status, hr.report_at
     FROM hazard_report hr
     INNER JOIN hazards h
     ON hr.hazard_id = h.hazard_id
     WHERE hr.user_id = ?
     ORDER BY hr.report_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$report_result = mysqli_stmt_get_result($stmt);

$reports = [];

while ($row = mysqli_fetch_assoc($report_result)) {
    $reports[] = $row;
}

mysqli_stmt_close($stmt);

$report_count = count($reports);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | NavSafe AI</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f7f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 25px auto;
        }

        h1, h2 {
            color: #176b45;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .stat-card h2 {
            font-size: 30px;
            margin: 10px 0;
        }

        .actions a {
            display: inline-block;
            background: #176b45;
            color: white;
            padding: 12px 18px;
            margin: 7px;
            text-decoration: none;
            border-radius: 6px;
        }

        .actions a:hover {
            background: #105536;
        }

        .empty {
            text-align: center;
            color: #777;
            padding: 20px;
        }

        .points {
            color: #d99b00;
        }

        .report-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .badge-card {
            text-align: center;
            background: #fffdf3;
            border: 1px solid #eadca5;
        }

        .badge-icon {
            font-size: 55px;
            margin: 10px;
        }

        .badge-name {
            font-size: 24px;
            color: #9b7410;
            font-weight: bold;
        }

        .progress-bg {
            width: 100%;
            height: 18px;
            background: #e5e5e5;
            border-radius: 20px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: #d5a72d;
            border-radius: 20px;
        }

        .progress-text {
            color: #666;
            text-align: center;
        }
    </style>
</head>

<body>

<?php include "notification.php"; ?>

<div class="container">

    <h1>Welcome, <?php echo htmlspecialchars($user["name"]); ?>!</h1>

    <p>Your NavSafe AI Dashboard</p>

    <!-- User Information -->
    <div class="card">
        <h2>My Profile</h2>

        <p><strong>Name:</strong>
            <?php echo htmlspecialchars($user["name"]); ?>
        </p>

        <p><strong>Email:</strong>
            <?php echo htmlspecialchars($user["email"]); ?>
        </p>

        <p><strong>Phone:</strong>
            <?php echo htmlspecialchars($user["phone"] ?? "Not provided"); ?>
        </p>
    </div>

    <!-- Statistics -->
    <div class="stats">

        <div class="stat-card">
            <h3>My Submitted Reports</h3>
            <h2><?php echo $report_count; ?></h2>
            <p>Total Reports</p>
        </div>

        <div class="stat-card">
            <h3>My Reward Points</h3>
            <h2 class="points">
                <?php echo $reward_points; ?> ⭐
            </h2>
            <p>Points Earned</p>
        </div>

    </div>

    <!-- Badge Section -->
    <div class="card badge-card">

        <h2>My Achievement Badge</h2>

        <div class="badge-icon">
            <?php echo $badge_icon; ?>
        </div>

        <div class="badge-name">
            <?php echo htmlspecialchars($badge); ?>
        </div>

        <p>Current Badge</p>

        <h3>Next Badge: <?php echo htmlspecialchars($next_badge); ?></h3>

        <?php if ($remaining > 0): ?>

            <div class="progress-bg">
                <div class="progress-fill"
                     style="width: <?php echo $progress; ?>%;">
                </div>
            </div>

            <p class="progress-text">
                <?php echo $remaining; ?> more points needed
                to unlock the next badge.
            </p>

        <?php else: ?>

            <p>Congratulations! You have achieved the highest badge!</p>

        <?php endif; ?>

    </div>

    <!-- My Submitted Reports -->
    <div class="card">

        <h2>My Submitted Reports</h2>

        <?php if ($report_count > 0): ?>

            <?php foreach ($reports as $report): ?>

                <div class="report-item">

                    <strong>
                        <?php echo htmlspecialchars($report["hazard_type"]); ?>
                    </strong>

                    <p>
                        Status:
                        <?php echo htmlspecialchars($report["status"]); ?>
                    </p>

                    <p>
                        Report Date:
                        <?php echo htmlspecialchars($report["report_at"]); ?>
                    </p>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="empty">
                <h3>No Reports Yet!</h3>
                <p>Your submitted reports will appear here.</p>
            </div>

        <?php endif; ?>

        <div class="actions">
            <a href="report_history.php">View Report History</a>
        </div>

    </div>

    <!-- Quick Actions -->
    <div class="card">

        <h2>Quick Actions</h2>

        <div class="actions">
            <a href="report_hazard.php">Report a Hazard</a>
            <a href="view_hazards.php">View Hazards</a>
            <a href="route.php">Smart Route Navigation 🗺️</a>
            <a href="report_history.php">View Report History</a>
            <a href="reward_history.php">My Reward History ⭐</a>
            <a href="map.php">View Hazard Map</a>
            <a href="index.php">Back to Homepage</a>
            <a href="logout.php">Logout</a>
        </div>

    </div>

</div>

</body>
</html>
