
<?php
session_start();
require_once "connection.php";

// Fetch approved hazard locations
$sql = "SELECT hazard_id, hazard_type, road_name, description,
        latitude, longitude, severity, status
        FROM hazards
        WHERE status = 'approved'
        AND latitude IS NOT NULL
        AND longitude IS NOT NULL";

$result = mysqli_query($conn, $sql);

$hazards = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {

        if (
            is_numeric($row["latitude"]) &&
            is_numeric($row["longitude"]) &&
            $row["latitude"] >= -90 &&
            $row["latitude"] <= 90 &&
            $row["longitude"] >= -180 &&
            $row["longitude"] <= 180
        ) {
            $hazards[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Hazard Map | NavSafe AI</title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #f3f7f4;
        }

        header {
            background: #176b45;
            color: white;
            padding: 18px;
            text-align: center;
        }

        .container {
            max-width: 1100px;
            margin: 25px auto;
            padding: 15px;
        }

        #map {
            height: 500px;
            width: 100%;
            border-radius: 10px;
            border: 2px solid #ddd;
        }

        .info {
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            background: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
        }

        .high {
            background: #e53935;
        }

        .medium {
            background: #fb8c00;
        }

        .low {
    background: #1e88e5;
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

        .hazard-icon {
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.5);
        }

        .no-hazards {
            background: #fff3cd;
            padding: 12px;
            border-radius: 6px;
            color: #856404;
        }
    </style>
</head>

<body>

<header>
    <h1>NavSafe AI - Hazard Map</h1>
    <p>Explore approved road hazards</p>
</header>

<div class="container">

    <div class="info">
        <h3>Approved Hazards: <?php echo count($hazards); ?></h3>
        <p>Click on a colored marker to view hazard details.</p>

        <?php if (count($hazards) == 0): ?>
            <div class="no-hazards">
                No approved hazards are available on the map yet.
            </div>
        <?php endif; ?>
    </div>

    <div class="legend">
        <div class="legend-item">
            <span class="legend-dot high"></span>
            <b>High Severity</b>
        </div>

        <div class="legend-item">
            <span class="legend-dot medium"></span>
            <b>Medium Severity</b>
        </div>

        <div class="legend-item">
            <span class="legend-dot low"></span>
            <b>Low Severity</b>
        </div>
    </div>

    <div id="map"></div>

    <a href="dashboard.php" class="back">
        Back to Dashboard
    </a>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    // Load hazard data
    const hazards = <?php
        echo json_encode(
            $hazards,
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_QUOT |
            JSON_HEX_AMP
        );
    ?>;

    // Create map
    const map = L.map('map').setView([23.8103, 90.4125], 12);

    // OpenStreetMap
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            attribution: '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    // Escape HTML
    function escapeHTML(value) {
        return String(value ?? "").replace(/[&<>"']/g, function(char) {
            return {
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;",
                '"': "&quot;",
                "'": "&#039;"
            }[char];
        });
    }


    // Select marker color based on severity
    function getMarkerColor(severity) {

        const level = String(severity).toLowerCase();

        if (level === "high") {
            return "#e53935";
        }

        if (level === "medium") {
            return "#fb8c00";
        }

        return "#1e88e5";
    }


    // Create markers
    const markers = [];

    hazards.forEach(function(hazard) {

        const color = getMarkerColor(hazard.severity);

        // Custom colored marker
        const hazardIcon = L.divIcon({
            className: "",
            html: `
                <div class="hazard-icon"
                     style="
                        width: 18px;
                        height: 18px;
                        background: ${color};
                     ">
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12],
            popupAnchor: [0, -12]
        });


        const marker = L.marker(
            [
                Number(hazard.latitude),
                Number(hazard.longitude)
            ],
            {
                icon: hazardIcon
            }
        ).addTo(map);


        // Hazard information
        const details = `
            <div style="min-width:180px">

                <h3 style="color:#176b45">
                    Road Hazard
                </h3>

                <b>Road Name:</b>
                ${escapeHTML(hazard.road_name)}<br><br>

                <b>Hazard Type:</b>
                ${escapeHTML(hazard.hazard_type)}<br><br>

                <b>Description:</b>
                ${escapeHTML(hazard.description)}<br><br>

                <b>Severity:</b>
                ${escapeHTML(hazard.severity)}<br><br>

                <b>Status:</b>
                ${escapeHTML(hazard.status)}

            </div>
        `;

        marker.bindPopup(details);

        markers.push(marker);

    });


    // Show all markers on map
    if (markers.length > 0) {

        const group = L.featureGroup(markers);

        map.fitBounds(
            group.getBounds(),
            {
                padding: [40, 40],
                maxZoom: 15
            }
        );
    }

</script>

</body>
</html>
