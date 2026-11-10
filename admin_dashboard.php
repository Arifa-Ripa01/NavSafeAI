
<?php
session_start();
require_once "connection.php";

// Check whether user is logged in as admin
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Total users
$user_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$user_data = mysqli_fetch_assoc($user_query);

// Total hazards
$hazard_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM hazards");
$hazard_data = mysqli_fetch_assoc($hazard_query);

// Pending hazards
$pending_query = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM hazards WHERE status = 'pending'"
);
$pending_data = mysqli_fetch_assoc($pending_query);

// Recent hazard reports
$reports_query = mysqli_query(
    $conn,
    "SELECT hazard_id, hazard_type, road_name, description, severity, status
     FROM hazards
     ORDER BY hazard_id DESC"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - NavSafe AI</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 25px;
        }

        h1 {
            color: #174477;
        }

        .cards {
            display: flex;
            gap: 20px;
            margin: 25px 0;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            flex: 1;
            text-align: center;
            box-shadow: 0 2px 8px #ddd;
        }

        .number {
            font-size: 30px;
            font-weight: bold;
            color: #16845b;
        }

        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #174477;
            color: white;
        }

        a {
            color: #174477;
        }
    </style>
</head>

<body>

<h1>NavSafe AI - Admin Dashboard</h1>

<p>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</p>

<div class="cards">

    <div class="card">
        <h3>Total Users</h3>
        <div class="number">
            <?php echo $user_data["total"]; ?>
        </div>
    </div>

    <div class="card">
        <h3>Total Hazards</h3>
        <div class="number">
            <?php echo $hazard_data["total"]; ?>
        </div>
    </div>

    <div class="card">
        <h3>Pending Hazards</h3>
        <div class="number">
            <?php echo $pending_data["total"]; ?>
        </div>
    </div>

</div>

<h2>Recent Hazard Reports</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Hazard Type</th>
        <th>Road / Location</th>
        <th>Description</th>
        <th>Severity</th>
        <th>Status</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($reports_query)): ?>

    <tr>
        <td><?php echo $row["hazard_id"]; ?></td>
        <td><?php echo htmlspecialchars($row["hazard_type"]); ?></td>
        <td><?php echo htmlspecialchars($row["road_name"] ?? "Not specified"); ?></td>
        <td><?php echo htmlspecialchars($row["description"]); ?></td>
        <td><?php echo htmlspecialchars($row["severity"]); ?></td>
        <td><?php echo htmlspecialchars($row["status"]); ?></td>
    </tr>

    <?php endwhile; ?>

</table>

<br><br>

<h2>Admin Quick Actions</h2>

<div class="cards">

    <div class="card">
        <h3>Manage Reports</h3>
        <p>Review pending hazard reports and approve or reject them.</p>
        <a href="admin_reports.php">Open Reports</a>
    </div>

    <div class="card">
        <h3>View All Hazards</h3>
        <p>See all reported road hazards.</p>
        <a href="view_hazards.php">View Hazards</a>
    </div>

</div>

<br>

<a href="index.php">Back to Homepage</a> |
<a href="logout.php">Logout</a>
</body>
</html>
