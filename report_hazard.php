
<?php
session_start();
require_once "connection.php";

$message = "";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = (int) $_SESSION["user_id"];

    $hazard_type = trim($_POST["hazard_type"] ?? "");
    $road_name = trim($_POST["road_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $latitude = trim($_POST["latitude"] ?? "");
    $longitude = trim($_POST["longitude"] ?? "");
    $severity = trim($_POST["severity"] ?? "");

    if (
        $hazard_type === "" ||
        $road_name === "" ||
        $description === "" ||
        $severity === "" ||
        $latitude === "" ||
        $longitude === ""
    ) {

        $message = "Please fill in all required fields and select a map location.";

    } elseif (
        !is_numeric($latitude) ||
        !is_numeric($longitude) ||
        $latitude < -90 || $latitude > 90 ||
        $longitude < -180 || $longitude > 180
    ) {

        $message = "Please select a valid location on the map.";

    } else {

        $status = "pending";
        $detected_by = "user";
        $created_by = date("Y-m-d H:i:s");
        $image_url = "";

        mysqli_begin_transaction($conn);

        $sql = "INSERT INTO hazards
        (hazard_type, road_name, description, latitude, longitude,
        severity, status, detected_by, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {

            mysqli_stmt_bind_param(
                $stmt,
                "sssssssss",
                $hazard_type,
                $road_name,
                $description,
                $latitude,
                $longitude,
                $severity,
                $status,
                $detected_by,
                $created_by
            );

            if (mysqli_stmt_execute($stmt)) {

                $hazard_id = mysqli_insert_id($conn);

                $report_sql = "INSERT INTO hazard_report
                (user_id, hazard_id, image_url, report_description, report_at)
                VALUES (?, ?, ?, ?, NOW())";

                $report_stmt = mysqli_prepare($conn, $report_sql);

                if ($report_stmt) {

                    mysqli_stmt_bind_param(
                        $report_stmt,
                        "iiss",
                        $user_id,
                        $hazard_id,
                        $image_url,
                        $description
                    );

                    if (mysqli_stmt_execute($report_stmt)) {

                        mysqli_commit($conn);
                        $message = "Hazard report submitted successfully!";

                    } else {

                        mysqli_rollback($conn);
                        $message = "Report saving failed: " .
                            mysqli_stmt_error($report_stmt);
                    }

                    mysqli_stmt_close($report_stmt);

                } else {

                    mysqli_rollback($conn);
                    $message = "Report query preparation failed.";
                }

            } else {

                mysqli_rollback($conn);
                $message = "Hazard saving failed: " .
                    mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);

        } else {

            mysqli_rollback($conn);
            $message = "Hazard query preparation failed.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report a Hazard - NavSafe AI</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f7f8;
            margin: 0;
            padding: 25px;
        }

        .container {
            max-width: 750px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px #00000015;
        }

        h2 {
            color: #123b50;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 11px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        textarea {
            height: 100px;
            resize: vertical;
        }

        #map {
            height: 350px;
            width: 100%;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }

        .location-info {
            background: #eaf5f5;
            padding: 12px;
            margin-top: 10px;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 13px;
            margin-top: 20px;
            background: #087f8c;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }

        button:hover {
            background: #066873;
        }

        .message {
            text-align: center;
            color: #087f8c;
            font-weight: bold;
        }

        .back {
            display: block;
            text-align: center;
            margin-top: 18px;
            color: #123b50;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>NavSafe AI - Report a Road Hazard</h2>

    <?php if ($message !== ""): ?>
        <p class="message">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

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

        <label>Road / Location Name:</label>

        <div style="display: flex; gap: 8px; align-items: center;">

    <input
        type="text"
        name="road_name"
        id="road_name"
        placeholder="Example: Pragati Sarani, Kuril, Dhaka"
        required
    >

    <button
        type="button"
        onclick="findLocation()"
        style="width: 150px; margin-top: 7px; flex-shrink: 0;"
    >
        Find Location
    </button>

</div>

<p id="search_message" style="color: #087f8c;"></p>

        <label>Description:</label>

        <textarea
            name="description"
            placeholder="Describe the road problem..."
            required
        ></textarea>

        <label>Select Location on Map:</label>

        <p>Click on the map to select the exact hazard location.</p>

        <div id="map"></div>

        <div class="location-info">

            <p>
                Latitude:
                <span id="latitude_text">Not selected</span>
            </p>

            <p>
                Longitude:
                <span id="longitude_text">Not selected</span>
            </p>

        </div>

        <input
            type="hidden"
            name="latitude"
            id="latitude"
            required
        >

        <input
            type="hidden"
            name="longitude"
            id="longitude"
            required
        >

        <label>Severity:</label>

        <select name="severity" required>
            <option value="">Select severity</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
            <option value="Critical">Critical</option>
        </select>

        <button type="submit">Submit Report</button>

    </form>

    <a href="dashboard.php" class="back">
        Back to Dashboard
    </a>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    // Create map centered on Dhaka
    const map = L.map('map').setView([23.8103, 90.4125], 12);

    // Load OpenStreetMap
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);

    let selectedMarker = null;
    // Search road name and automatically mark location
async function findLocation() {

    const roadName = document.getElementById("road_name").value.trim();
    const searchMessage = document.getElementById("search_message");

    if (roadName === "") {
        searchMessage.textContent = "Please enter a road name first.";
        return;
    }

    searchMessage.textContent = "Searching location...";

    try {

        const url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=bd&q="
            + encodeURIComponent(roadName + ", Bangladesh");

        const response = await fetch(url);
        const data = await response.json();

        if (data.length === 0) {
            searchMessage.textContent = "Location not found. Try a nearby landmark or area name.";
            return;
        }

        const lat = parseFloat(data[0].lat);
        const lng = parseFloat(data[0].lon);

        // Remove previous marker
        if (selectedMarker) {
            map.removeLayer(selectedMarker);
        }

        // Add new marker
        selectedMarker = L.marker([lat, lng]).addTo(map);

        selectedMarker.bindPopup(
            "Selected Location: " + roadName
        ).openPopup();

        // Zoom to searched location
        map.setView([lat, lng], 16);

        // Update coordinates
        document.getElementById("latitude").value = lat.toFixed(6);
        document.getElementById("longitude").value = lng.toFixed(6);

        document.getElementById("latitude_text").textContent = lat.toFixed(6);
        document.getElementById("longitude_text").textContent = lng.toFixed(6);

        searchMessage.textContent = "Location found successfully!";

    } catch (error) {

        searchMessage.textContent = "Unable to search location. Please try again.";

        console.error(error);
    }
}

    // Select location by clicking the map
    map.on('click', function(e) {

        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);

        // Remove previous marker
        if (selectedMarker) {
            map.removeLayer(selectedMarker);
        }

        // Add new marker
        selectedMarker = L.marker([lat, lng]).addTo(map);

        selectedMarker.bindPopup(
            "Selected Hazard Location"
        ).openPopup();

        // Put coordinates into form
        document.getElementById("latitude").value = lat;
        document.getElementById("longitude").value = lng;

        document.getElementById("latitude_text").textContent = lat;
        document.getElementById("longitude_text").textContent = lng;

    });

</script>

</body>
</html>
