
<?php
session_start();
require_once "connection.php";

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Fetch logged-in user's submitted reports
$user_id = $_SESSION["user_id"];

$sql = "SELECT hazard_id, hazard_type, road_name, description,
        latitude, longitude, severity, status, created_by
        FROM hazards
        WHERE detected_by = '$user_id'
        ORDER BY hazard_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report History | NavSafe AI</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f7f4;
            margin: 0;
            padding: 30px 15px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            color: #176b45;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #176b45;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f7f3;
        }

        .status {
            font-weight: bold;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 18px;
            background: #176b45;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #777;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>My Report History</h1>

    <p>View your submitted road hazard reports.</p>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>

        <table>
            <tr>
                <th>ID</th>
                <th>Hazard Type</th>
                <th>Description</th>
                <th>Severity</th>
                <th>Status</th>
                <th>Road/Location</th>
            </tr>

            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                <tr>
                    <td><?php echo $row["hazard_id"]; ?></td>

                    <td>
                        <?php echo htmlspecialchars($row["hazard_type"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["description"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["severity"]); ?>
                    </td>

                    <td class="status">
                        <?php echo htmlspecialchars($row["status"]); ?>
                    </td>

                    <td>
                        <?php
                        echo htmlspecialchars($row["latitude"] . ", " . $row["longitude"]);
                        ?>
                    </td>
                    <td>
                       <?php echo htmlspecialchars($row["road_name"] ?? "Not specified"); ?>
                     </td>
                </tr>

            <?php endwhile; ?>

        </table>

    <?php else: ?>

        <div class="empty">
            <h3>No reports found!</h3>
            <p>You have not submitted any hazard reports yet.</p>
        </div>

    <?php endif; ?>

    <a class="back" href="dashboard.php">Back to Dashboard</a>

</div>

</body>
</html>
