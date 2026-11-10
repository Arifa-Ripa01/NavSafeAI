
<?php

require_once "connection.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $hazard_type = trim($_POST["hazard_type"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $latitude = trim($_POST["latitude"] ?? "");
    $longitude = trim($_POST["longitude"] ?? "");
    $severity = trim($_POST["severity"] ?? "");

    if (
        $hazard_type === "" ||
        $description === "" ||
        $severity === ""
    ) {
        $message = "Please fill in all required fields.";
    } else {

        $status = "pending";
        $detected_by = "user";
        $created_by = date("Y-m-d H:i:s");

        $sql = "INSERT INTO hazards
                (hazard_type, description, latitude,
                 longitude, severity, status,
                 detected_by, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ssssssss",
            $hazard_type,
            $description,
            $latitude,
            $longitude,
            $severity,
            $status,
            $detected_by,
            $created_by
        );

        if (mysqli_stmt_execute($stmt)) {
            $message = "Hazard report submitted successfully!";
        } else {
            $message = "Error: " . mysqli_stmt_error($stmt);
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Hazard Report</title>
</head>
<body>

    <h2>NavSafe AI - Report a Road Hazard</h2>

    <p>
        <?php echo htmlspecialchars($message); ?>
    </p>

    <form method="POST" action="">
        <label>Hazard Type:</label>
        <select name="hazard_type" required>
            <option value="">Select hazard</option>
            <option value="Pothole">Pothole</option>
            <option value="Broken Road">Broken Road</option>
            <option value="Fallen Tree">Fallen Tree</option>
            <option value="Construction">Construction</option>
            <option value="Flooding">Flooding</option>
        </select>

        <br><br>

        <label>Description:</label>
        <textarea name="description" required></textarea>

        <br><br>

        <label>Latitude (optional):</label>
        <input type="number" step="any" name="latitude">

        <br><br>

        <label>Longitude (optional):</label>
        <input type="number" step="any" name="longitude">

        <br><br>

        <label>Severity:</label>
        <select name="severity" required>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
        </select>

        <br><br>

        <button type="submit">Submit Report</button>
    </form>

</body>
</html>
