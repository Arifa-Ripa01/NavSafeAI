
<?php
require_once "connection.php";

$sql = "SELECT hazard_id, hazard_type, description,
               latitude, longitude, severity, status
        FROM hazards
        ORDER BY hazard_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NavSafe AI - Hazard List</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f6f8;
            padding: 25px;
        }

        h1 {
            color: #123b50;
        }

        .container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #123b50;
            color: white;
        }

        tr:nth-child(even) {
            background: #f2f6f8;
        }

        .button {
            display: inline-block;
            background: #087f8c;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <h1>NavSafe AI</h1>
    <p>Reported Road Hazards</p>

    <div class="container">

        <a class="button" href="submit_report.php">
            + Report New Hazard
        </a>

        <table>
            <tr>
                <th>ID</th>
                <th>Hazard Type</th>
                <th>Description</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Severity</th>
                <th>Status</th>
            </tr>

            <?php if (mysqli_num_rows($result) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$row["hazard_id"]) ?></td>
                        <td><?= htmlspecialchars($row["hazard_type"]) ?></td>
                        <td><?= htmlspecialchars($row["description"]) ?></td>
                        <td><?= htmlspecialchars((string)$row["latitude"]) ?></td>
                        <td><?= htmlspecialchars((string)$row["longitude"]) ?></td>
                        <td><?= htmlspecialchars($row["severity"]) ?></td>
                        <td><?= htmlspecialchars($row["status"]) ?></td>
                    </tr>
                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="7">No hazards reported yet.</td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

</body>
</html>

<?php
mysqli_free_result($result);
mysqli_close($conn);
?>
