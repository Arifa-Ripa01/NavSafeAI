
<?php
session_start();
require_once "connection.php";

// Admin access check
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit;
}

// Fetch pending reports
$sql = "SELECT
            h.hazard_id,
            h.hazard_type,
            h.road_name,
            h.description,
            h.latitude,
            h.longitude,
            h.severity,
            h.status,
            hr.user_id
        FROM hazards h
        LEFT JOIN hazard_report hr
        ON h.hazard_id = hr.hazard_id
        WHERE h.status = 'pending'
        ORDER BY h.hazard_id DESC";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Reports - NavSafe AI</title>
    <link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 25px;
        }

        h1 {
            color: #174477;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            margin-top: 25px;
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

        button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        .approve {
            background: #16845b;
        }

        .reject {
            background: #d93636;
        }
        .view-map {
    background: #087f8c;
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    color: white;
    cursor: pointer;
    margin-bottom: 8px;
}

.map-container {
    display: none;
    width: 280px;
    height: 200px;
    margin-top: 8px;
    border: 1px solid #ccc;
    border-radius: 6px;
}

.map-container.active {
    display: block;
}
    </style>
</head>

<body>

<?php include "notification.php"; ?>

<h1>Pending Hazard Reports</h1>

<table>
    <tr>
        <th>ID</th>
        <th>User ID</th>
        <th>Hazard</th>
        <th>Road / Location Name</th>
        <th>Description</th>
        <th>Severity</th>
        <th>Coordinates</th>
        <th>Action</th>
    </tr>

    <?php if ($result && mysqli_num_rows($result) > 0): ?>

        <?php while ($row = mysqli_fetch_assoc($result)): ?>

        <tr>
            <td><?php echo $row["hazard_id"]; ?></td>

            <td><?php echo htmlspecialchars($row["user_id"] ?? "N/A"); ?></td>

            <td><?php echo htmlspecialchars($row["hazard_type"]); ?></td>

            <td>
                <?php echo htmlspecialchars($row["road_name"] ?? "Not specified"); ?>
            </td>

            <td><?php echo htmlspecialchars($row["description"]); ?></td>

            <td><?php echo htmlspecialchars($row["severity"]); ?></td>

            <td>
    <?php echo htmlspecialchars(
        $row["latitude"] . ", " . $row["longitude"]
    ); ?>

    <?php if (is_numeric($row["latitude"]) &&
              is_numeric($row["longitude"]) &&
              $row["latitude"] >= -90 &&
              $row["latitude"] <= 90 &&
              $row["longitude"] >= -180 &&
              $row["longitude"] <= 180): ?>

        <button
            type="button"
            class="view-map"
            onclick="showMap(<?php echo (int)$row['hazard_id']; ?>)"
        >
            View Map
        </button>

        <div
            id="map-<?php echo (int)$row['hazard_id']; ?>"
            class="map-container"
            data-lat="<?php echo htmlspecialchars($row['latitude']); ?>"
            data-lng="<?php echo htmlspecialchars($row['longitude']); ?>"
            data-road="<?php echo htmlspecialchars($row['road_name'] ?? ''); ?>"
        ></div>

    <?php endif; ?>
</td>

            <td>
                <form method="POST" action="update_hazard_status.php">
                    <input type="hidden" name="hazard_id"
                           value="<?php echo $row["hazard_id"]; ?>">

                    <button class="approve" name="action"
                            value="approve">Approve</button>

                    <button class="reject" name="action"
                            value="reject">Reject</button>
                </form>
            </td>
        </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="8">No pending reports found.</td>
        </tr>

    <?php endif; ?>

</table>

<p>
    <a href="admin_dashboard.php">Back to Admin Dashboard</a>
</p>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

const reportMaps = {};

function showMap(hazardId) {

    const mapDiv = document.getElementById("map-" + hazardId);

    // Show or hide map
    if (mapDiv.classList.contains("active")) {
        mapDiv.classList.remove("active");
        return;
    }

    mapDiv.classList.add("active");

    // Get location coordinates
    const lat = parseFloat(mapDiv.dataset.lat);
    const lng = parseFloat(mapDiv.dataset.lng);
    const road = mapDiv.dataset.road;

    // Create map only once
    if (!reportMaps[hazardId]) {

        const map = L.map(mapDiv).setView([lat, lng], 16);

        L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            {
                attribution: '&copy; OpenStreetMap contributors'
            }
        ).addTo(map);

        L.marker([lat, lng])
            .addTo(map)
            .bindPopup("Hazard: " + road)
            .openPopup();

        reportMaps[hazardId] = map;

    } else {

        reportMaps[hazardId].invalidateSize();
        reportMaps[hazardId].setView([lat, lng], 16);

    }

    setTimeout(function() {
        reportMaps[hazardId].invalidateSize();
    }, 100);

}

</script>
</body>
</html>
