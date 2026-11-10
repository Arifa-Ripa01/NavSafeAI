
<?php
session_start();
require_once "connection.php";

// Check user login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION["user_id"];

// Get reward history
$stmt = mysqli_prepare(
    $conn,
    "SELECT rh.points, rh.awarded_at, h.hazard_type, h.hazard_id
     FROM reward_history rh
     INNER JOIN hazards h ON rh.hazard_id = h.hazard_id
     WHERE rh.user_id = ?
     ORDER BY rh.awarded_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$rewards = [];
$total_points = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $rewards[] = $row;
    $total_points += (int)$row["points"];
}

mysqli_stmt_close($stmt);

// Badge calculation
if ($total_points >= 200) {
    $badge = "NavSafe Champion";
    $badge_icon = "💎";
    $next_badge = "Maximum Badge Achieved!";
    $remaining = 0;
    $progress = 100;

} elseif ($total_points >= 100) {
    $badge = "Road Guardian";
    $badge_icon = "🏆";
    $next_badge = "NavSafe Champion";
    $remaining = 200 - $total_points;
    $progress = (($total_points - 100) / 100) * 100;

} elseif ($total_points >= 50) {
    $badge = "Safety Scout";
    $badge_icon = "🥇";
    $next_badge = "Road Guardian";
    $remaining = 100 - $total_points;
    $progress = (($total_points - 50) / 50) * 100;

} elseif ($total_points >= 20) {
    $badge = "Road Watcher";
    $badge_icon = "🥈";
    $next_badge = "Safety Scout";
    $remaining = 50 - $total_points;
    $progress = (($total_points - 20) / 30) * 100;

} else {
    $badge = "New Reporter";
    $badge_icon = "🥉";
    $next_badge = "Road Watcher";
    $remaining = 20 - $total_points;
    $progress = ($total_points / 20) * 100;
}

$progress = min(100, max(0, $progress));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reward History | NavSafe AI</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f7f5;
            margin: 0;
            padding: 25px;
        }

        .container {
            max-width: 950px;
            margin: 30px auto;
        }

        h1 {
            color: #176b45;
            text-align: center;
        }

        .summary, .badge-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.07);
        }

        .summary h2 {
            color: #d99b00;
            font-size: 32px;
        }

        .badge-icon {
            font-size: 45px;
        }

        .badge-card h2 {
            color: #176b45;
            margin: 10px 0;
        }

        .progress-bg {
            background: #e5e5e5;
            height: 14px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
        }

        .progress-fill {
            background: #176b45;
            height: 100%;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }

        th, td {
            padding: 14px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #176b45;
            color: white;
        }

        .points {
            color: #16845b;
            font-weight: bold;
        }

        .empty {
            background: white;
            padding: 35px;
            text-align: center;
            border-radius: 10px;
            color: #777;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 18px;
            background: #176b45;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            .container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }
        }
    </style>
</head>

<body>

<?php include "notification.php"; ?>

<div class="container">

    <h1>My Reward History ⭐</h1>

    <div class="summary">
        <h3>Total Earned Points</h3>
        <h2><?php echo $total_points; ?> ⭐</h2>
        <p>Thank you for contributing to road safety!</p>
    </div>

    <div class="badge-card">

        <div class="badge-icon">
            <?php echo $badge_icon; ?>
        </div>

        <h2><?php echo htmlspecialchars($badge); ?></h2>

        <?php if ($remaining > 0): ?>

            <p>
                Earn <?php echo $remaining; ?> more points
                to unlock <?php echo htmlspecialchars($next_badge); ?>.
            </p>

            <div class="progress-bg">
                <div class="progress-fill"
                     style="width: <?php echo $progress; ?>%;">
                </div>
            </div>

        <?php else: ?>

            <p>You have achieved the highest badge!</p>

        <?php endif; ?>

    </div>

    <h2>Reward Transactions</h2>

    <?php if (count($rewards) > 0): ?>

        <table>
            <tr>
                <th>Hazard ID</th>
                <th>Hazard Type</th>
                <th>Points Earned</th>
                <th>Award Date</th>
            </tr>

            <?php foreach ($rewards as $reward): ?>

                <tr>
                    <td>
                        <?php echo (int)$reward["hazard_id"]; ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reward["hazard_type"]); ?>
                    </td>

                    <td class="points">
                        +<?php echo (int)$reward["points"]; ?> ⭐
                    </td>

                    <td>
                        <?php echo htmlspecialchars($reward["awarded_at"]); ?>
                    </td>
                </tr>

            <?php endforeach; ?>

        </table>

    <?php else: ?>

        <div class="empty">
            <h3>No Rewards Yet!</h3>
            <p>Submit a hazard report and earn points after admin approval.</p>
        </div>

    <?php endif; ?>

    <a href="dashboard.php" class="back">Back to Dashboard</a>

</div>

</body>
</html>
