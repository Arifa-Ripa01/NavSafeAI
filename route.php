
<?php
session_start();
require_once "connection.php";

$isLoggedIn = isset($_SESSION["user_id"]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Route | NavSafe AI</title>

    <link rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f7f5;
            margin: 0;
            padding: 25px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            color: #176b45;
            text-align: center;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            background: #176b45;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:disabled {
            background: #888;
            cursor: not-allowed;
        }

        #map {
            height: 450px;
            margin-top: 25px;
            border-radius: 10px;
        }

        #message {
            margin-top: 15px;
            font-weight: bold;
        }

        #hazardAlert {
            display: none;
            margin-top: 20px;
            padding: 18px;
            border-radius: 8px;
            background: #f5f5f5;
        }

        #hazardAlert h2 {
            margin-top: 0;
        }

        .alert-high {
            background: #ffebee !important;
            border-left: 6px solid #d32f2f;
        }

        .alert-medium {
            background: #fff3e0 !important;
            border-left: 6px solid #f57c00;
        }

        .alert-low {
            background: #e3f2fd !important;
            border-left: 6px solid #1976d2;
        }

        .alert-safe {
            background: #e8f5e9 !important;
            border-left: 6px solid #2e7d32;
        }

        .hazard-item {
            padding: 10px;
            margin-top: 10px;
            background: white;
            border-radius: 6px;
        }

        .severity-high {
            color: #d32f2f;
            font-weight: bold;
        }

        .severity-medium {
            color: #e65100;
            font-weight: bold;
        }

        .severity-low {
            color: #1565c0;
            font-weight: bold;
        }

        .legend {
            margin-top: 15px;
            font-size: 14px;
            line-height: 2;
        }

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #176b45;
        }

        #routeComparison {
            display: none;
            margin-top: 20px;
            padding: 15px;
            background: #f1f5f3;
            border-radius: 8px;
        }

        .route-option {
            background: white;
            padding: 12px;
            margin-top: 10px;
            border-radius: 6px;
        }

        .recommended {
            border-left: 5px solid #176b45;
        }

        .other-route {
            border-left: 5px solid #888;
        }
        .route-option h3 { margin: 0 0 6px 0; display:flex; align-items:center; gap:8px; }
        .route-swatch { display:inline-block; width:14px; height:14px; border-radius:50%; }
        .route-option p { margin: 6px 0; }
        .road-list { margin: 6px 0 6px 18px; padding: 0; font-size: 14px; }
        .road-list li { margin: 3px 0; }
        .turn-info { background:#fff8e1; border-radius:6px; padding:8px 10px; font-size:14px; margin:8px 0; }
        .show-map-btn { padding: 6px 12px; font-size: 13px; margin-top: 6px; }
        .route-label-wrap { background: transparent; border: none; }
        .route-label {
            color: white; font-weight: bold; font-size: 12px; text-align: center;
            padding: 4px 8px; border-radius: 12px; border: 2px solid white;
            box-shadow: 0 1px 4px rgba(0,0,0,.4); white-space: nowrap;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Smart Navigation System</h1>

    <p>Enter your starting point and destination to find a safer route.</p>

    <form id="routeForm">

        <div class="form-group">
            <label>Starting Location</label>
            <input type="text" id="start"
                   placeholder="Example: Bashundhara R/A"
                   required>
        </div>

        <div class="form-group">
            <label>Destination</label>
            <input type="text" id="destination"
                   placeholder="Example: Jamuna Future Park"
                   required>
        </div>

        <button type="submit" id="searchButton">Find Safer Route</button>

    </form>

    <div id="message"></div>

    <div id="routeComparison">
        <h2>Safer Route Comparison</h2>
        <div id="routeDetails"></div>
    </div>

    <div id="hazardAlert">
        <h2 id="alertTitle">Road Hazard Alert</h2>
        <div id="alertDetails"></div>
    </div>

    <div id="map"></div>

    <div class="legend">
        <span style="color:green;">●</span> Recommended Safer Route
        &nbsp;&nbsp;
        <span style="color:#7b1fa2;">●</span> <span style="color:#00838f;">●</span> Alternative Routes (dashed, labeled Alt 1, Alt 2…)
        <br>
        <span style="color:red;">●</span> High Hazard
        &nbsp;&nbsp;
        <span style="color:orange;">●</span> Medium Hazard
        &nbsp;&nbsp;
        <span style="color:blue;">●</span> Low Hazard
    </div>

    <?php if ($isLoggedIn): ?>
        <a href="dashboard.php" class="back">Back to Dashboard</a>
    <?php else: ?>
        <a href="index.php" class="back">Back to Homepage</a>
    <?php endif; ?>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

var map = L.map('map').setView([23.8103, 90.4125], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap'
}).addTo(map);

var routeLayers = L.layerGroup().addTo(map);
var hazardLayer = L.layerGroup().addTo(map);
var locationLayer = L.layerGroup().addTo(map);


// Calculate distance between a hazard and a route segment
function pointToSegmentDistance(lat, lon, lat1, lon1, lat2, lon2) {

    var earthRadius = 6371000;
    var referenceLat = lat * Math.PI / 180;

    var x = earthRadius * lon * Math.PI / 180 * Math.cos(referenceLat);
    var y = earthRadius * lat * Math.PI / 180;

    var x1 = earthRadius * lon1 * Math.PI / 180 * Math.cos(referenceLat);
    var y1 = earthRadius * lat1 * Math.PI / 180;

    var x2 = earthRadius * lon2 * Math.PI / 180 * Math.cos(referenceLat);
    var y2 = earthRadius * lat2 * Math.PI / 180;

    var dx = x2 - x1;
    var dy = y2 - y1;

    var length = dx * dx + dy * dy;

    if (length === 0) {
        return Math.sqrt(
            Math.pow(x - x1, 2) +
            Math.pow(y - y1, 2)
        );
    }

    var t = ((x - x1) * dx + (y - y1) * dy) / length;

    t = Math.max(0, Math.min(1, t));

    var closestX = x1 + t * dx;
    var closestY = y1 + t * dy;

    return Math.sqrt(
        Math.pow(x - closestX, 2) +
        Math.pow(y - closestY, 2)
    );
}


// Find minimum distance of a hazard from the complete route
function getHazardDistance(hazard, coordinates) {

    var lat = parseFloat(hazard.latitude);
    var lon = parseFloat(hazard.longitude);

    if (
        !Number.isFinite(lat) ||
        !Number.isFinite(lon) ||
        lat < -90 || lat > 90 ||
        lon < -180 || lon > 180 ||
        (lat === 0 && lon === 0)
    ) {
        return null;
    }

    var minimumDistance = Infinity;

    for (var i = 0; i < coordinates.length - 1; i++) {

        var point1 = coordinates[i];
        var point2 = coordinates[i + 1];

        var distance = pointToSegmentDistance(
            lat,
            lon,
            point1[1],
            point1[0],
            point2[1],
            point2[0]
        );

        if (distance < minimumDistance) {
            minimumDistance = distance;
        }
    }

    return minimumDistance;
}


// Calculate hazard risk for one route
function calculateRouteRisk(route, hazards) {

    var nearbyHazards = [];

    var riskScore = 0;

    hazards.forEach(function(hazard) {

        var distance = getHazardDistance(
            hazard,
            route.geometry.coordinates
        );

        if (distance !== null && distance <= 500) {

            hazard.distance_from_route = distance;

            nearbyHazards.push(hazard);

            var severity = (hazard.severity || "").toLowerCase();

            if (severity === "high") {
                riskScore += 100;
            } else if (severity === "medium") {
                riskScore += 40;
            } else if (severity === "low") {
                riskScore += 10;
            } else {
                riskScore += 20;
            }
        }

    });

    return {
        riskScore: riskScore,
        nearbyHazards: nearbyHazards
    };
}


// Display Hazard Alert
function showHazardAlert(nearbyHazards) {

    var alertBox = document.getElementById("hazardAlert");
    var alertTitle = document.getElementById("alertTitle");
    var alertDetails = document.getElementById("alertDetails");

    alertBox.style.display = "block";
    alertDetails.innerHTML = "";

    if (nearbyHazards.length === 0) {

        alertBox.className = "alert-safe";
        alertTitle.textContent = "No Nearby Hazards";

        alertDetails.textContent =
            "No approved road hazards were found within 500 meters of the recommended route.";

        return;
    }

    var highCount = 0;
    var mediumCount = 0;
    var lowCount = 0;

    nearbyHazards.forEach(function(hazard) {

        var severity = (hazard.severity || "").toLowerCase();

        if (severity === "high") {
            highCount++;
        } else if (severity === "medium") {
            mediumCount++;
        } else if (severity === "low") {
            lowCount++;
        }

    });

    if (highCount > 0) {

        alertBox.className = "alert-high";
        alertTitle.textContent = "High Risk! Road Hazard Alert";

    } else if (mediumCount > 0) {

        alertBox.className = "alert-medium";
        alertTitle.textContent = "Caution! Road Hazards Ahead";

    } else {

        alertBox.className = "alert-low";
        alertTitle.textContent = "Road Hazard Information";

    }

    var summary = document.createElement("p");

    summary.textContent =
        "Nearby hazards: " + nearbyHazards.length +
        " | High: " + highCount +
        " | Medium: " + mediumCount +
        " | Low: " + lowCount;

    alertDetails.appendChild(summary);

    nearbyHazards.forEach(function(hazard) {

        var item = document.createElement("div");
        item.className = "hazard-item";

        var title = document.createElement("strong");
        title.textContent = hazard.hazard_type;

        var road = document.createElement("p");
        road.textContent =
            "Road: " + (hazard.road_name || "Not specified");

        var severity = document.createElement("p");
        severity.className =
            "severity-" + (hazard.severity || "").toLowerCase();

        severity.textContent =
            "Severity: " + (hazard.severity || "Not specified");

        var distance = document.createElement("p");
        distance.textContent =
            "Distance from route: " +
            Math.round(hazard.distance_from_route) + " meters";

        item.append(title, road, severity, distance);

        alertDetails.appendChild(item);

    });
}


var altColors = ["#7b1fa2", "#00838f", "#6d4c41", "#c2185b"];

// All steps of a route in order
function getRouteSteps(route) {
    var steps = [];
    (route.legs || []).forEach(function(leg) {
        (leg.steps || []).forEach(function(step) {
            steps.push(step);
        });
    });
    return steps;
}

// Main roads of a route in travel order (consecutive same names merged)
function getMainRoads(route, limit) {

    var merged = [];

    getRouteSteps(route).forEach(function(step) {
        var name = (step.name || "").trim();
        if (!name) { return; }

        var last = merged[merged.length - 1];

        if (last && last.name === name) {
            last.distance += step.distance;
        } else {
            merged.push({ name: name, distance: step.distance });
        }
    });

    // keep the longest roads, but show them in travel order
    var top = merged.slice().sort(function(a, b) {
        return b.distance - a.distance;
    }).slice(0, limit || 5);

    return merged.filter(function(road) {
        return top.indexOf(road) !== -1;
    });
}

// Find where an alternative route leaves the recommended route
function findRouteDifference(altRoute, selectedRoute) {

    var selCoords = selectedRoute.geometry.coordinates;
    var altCoords = altRoute.geometry.coordinates;

    // Point of the alternative route that is farthest from the recommended one
    var maxDist = -1;
    var far = altCoords[Math.floor(altCoords.length / 2)];

    for (var i = 0; i < altCoords.length; i += 4) {
        var d = getHazardDistance(
            { latitude: altCoords[i][1], longitude: altCoords[i][0] },
            selCoords
        );
        if (d !== null && d > maxDist) {
            maxDist = d;
            far = altCoords[i];
        }
    }

    // First step that goes away from the recommended route
    var steps = getRouteSteps(altRoute);
    var turn = null;

    for (var j = 1; j < steps.length; j++) {
        var loc = steps[j].maneuver.location;
        var d2 = getHazardDistance(
            { latitude: loc[1], longitude: loc[0] },
            selCoords
        );

        if (d2 !== null && d2 > 60) {
            var s = steps[j - 1];
            turn = {
                point: [s.maneuver.location[1], s.maneuver.location[0]],
                road: (s.name || "").trim() || "the next road"
            };
            break;
        }
    }

    return {
        labelPoint: [far[1], far[0]],
        turn: turn
    };
}

function roadSummary(item) {
    return item.roads.map(function(r) { return r.name; }).join(" → ") || "Unnamed roads";
}

// Highlight one route on the map
function highlightRoute(routeResults, index) {

    routeResults.forEach(function(item, i) {
        item.line.setStyle({
            weight: i === index ? 9 : (i === 0 ? 7 : 5),
            opacity: i === index ? 1 : 0.45
        });
    });

    routeResults[index].line.bringToFront();

    map.fitBounds(routeResults[index].line.getBounds(), { padding: [35, 35] });

    document.getElementById("map").scrollIntoView({ behavior: "smooth", block: "center" });
}

// Popup content for the route label on the map
function buildRoutePopup(item) {

    var box = document.createElement("div");

    var title = document.createElement("strong");
    title.textContent = item.label;

    var info = document.createElement("p");
    info.textContent =
        (item.route.distance / 1000).toFixed(2) + " km | " +
        Math.round(item.route.duration / 60) + " min | Hazards: " +
        item.nearbyHazards.length;

    var via = document.createElement("p");
    via.textContent = "Via: " + roadSummary(item);

    box.append(title, info, via);

    return box;
}

// Put labels (and turn points) on the map
function addRouteLabels(routeResults) {

    routeResults.forEach(function(item, index) {

        var pos;

        if (index === 0) {
            var coords = item.route.geometry.coordinates;
            var mid = coords[Math.floor(coords.length / 2)];
            pos = [mid[1], mid[0]];
        } else {
            pos = item.diff.labelPoint;
        }

        var icon = L.divIcon({
            className: "route-label-wrap",
            html: '<div class="route-label" style="background:' + item.color + '">' +
                  (index === 0 ? "Best" : "Alt " + index) + '</div>',
            iconSize: [60, 24],
            iconAnchor: [30, 12]
        });

        var labelMarker = L.marker(pos, { icon: icon });
        labelMarker.bindPopup(buildRoutePopup(item));
        labelMarker.on("click", function() { highlightRoute(routeResults, index); });
        routeLayers.addLayer(labelMarker);

        if (index > 0 && item.diff.turn) {

            var turnMarker = L.circleMarker(item.diff.turn.point, {
                radius: 8,
                color: item.color,
                fillColor: "#ffffff",
                fillOpacity: 1,
                weight: 4
            });

            turnMarker.bindTooltip(
                "Alt " + index + ": take " + item.diff.turn.road,
                { direction: "top" }
            );

            routeLayers.addLayer(turnMarker);
        }
    });
}

// Display comparison of available routes
function showRouteComparison(routeResults, selectedIndex) {

    var comparison = document.getElementById("routeComparison");
    var details = document.getElementById("routeDetails");

    comparison.style.display = "block";
    details.innerHTML = "";

    routeResults.forEach(function(item, index) {

        var route = item.route;

        var box = document.createElement("div");

        box.className = index === selectedIndex
            ? "route-option recommended"
            : "route-option other-route";

        box.style.borderLeftColor = item.color;

        var title = document.createElement("h3");

        var swatch = document.createElement("span");
        swatch.className = "route-swatch";
        swatch.style.background = item.color;

        title.append(swatch, document.createTextNode(item.label));

        var distance = document.createElement("p");
        distance.textContent =
            "Distance: " + (route.distance / 1000).toFixed(2) + " km";

        var time = document.createElement("p");
        time.textContent =
            "Estimated Time: " + Math.round(route.duration / 60) + " minutes";

        // Road names
        var viaTitle = document.createElement("p");
        viaTitle.innerHTML = "<strong>Go via:</strong>";

        var roadList = document.createElement("ol");
        roadList.className = "road-list";

        item.roads.forEach(function(road) {
            var li = document.createElement("li");
            li.textContent = road.name + " (" + (road.distance / 1000).toFixed(1) + " km)";
            roadList.appendChild(li);
        });

        if (item.roads.length === 0) {
            var li = document.createElement("li");
            li.textContent = "Road names not available";
            roadList.appendChild(li);
        }

        var hazardCount = document.createElement("p");
        hazardCount.textContent = "Nearby Hazards: " + item.nearbyHazards.length;

        var risk = document.createElement("p");
        risk.textContent = "Risk Score: " + item.riskScore;

        var button = document.createElement("button");
        button.type = "button";
        button.className = "show-map-btn";
        button.textContent = "Show on map";
        button.addEventListener("click", function() {
            highlightRoute(routeResults, index);
        });

        box.append(title, distance, time, viaTitle, roadList);

        if (index !== selectedIndex && item.diff && item.diff.turn) {
            var turn = document.createElement("div");
            turn.className = "turn-info";
            turn.textContent =
                "Leaves the recommended route by taking: " + item.diff.turn.road;
            box.appendChild(turn);
        }

        box.append(hazardCount, risk, button);

        details.appendChild(box);
    });
}


// Show hazard markers on the map
function showHazardMarkers(nearbyHazards) {

    nearbyHazards.forEach(function(hazard) {

        var lat = parseFloat(hazard.latitude);
        var lon = parseFloat(hazard.longitude);

        var severity = (hazard.severity || "").toLowerCase();

        var markerColor = "#1976d2";

        if (severity === "high") {
            markerColor = "#e53935";
        } else if (severity === "medium") {
            markerColor = "#fb8c00";
        }

        var marker = L.circleMarker([lat, lon], {
            radius: 9,
            color: markerColor,
            fillColor: markerColor,
            fillOpacity: 0.9,
            weight: 2
        });

        var popupContent = document.createElement("div");

        var title = document.createElement("strong");
        title.textContent = hazard.hazard_type;

        var road = document.createElement("p");
        road.textContent =
            "Road: " + (hazard.road_name || "Not specified");

        var severityText = document.createElement("p");
        severityText.textContent =
            "Severity: " + (hazard.severity || "Not specified");

        var description = document.createElement("p");
        description.textContent =
            hazard.description || "No description";

        var distance = document.createElement("p");
        distance.textContent =
            "Distance from route: " +
            Math.round(hazard.distance_from_route) + " meters";

        popupContent.append(
            title,
            road,
            severityText,
            description,
            distance
        );

        marker.bindPopup(popupContent);

        hazardLayer.addLayer(marker);

    });
}


// Main Route Search
document.getElementById("routeForm").addEventListener("submit", async function(e) {

    e.preventDefault();

    var start = document.getElementById("start").value.trim();
    var destination = document.getElementById("destination").value.trim();

    var message = document.getElementById("message");
    var searchButton = document.getElementById("searchButton");

    routeLayers.clearLayers();
    hazardLayer.clearLayers();
    locationLayer.clearLayers();

    document.getElementById("hazardAlert").style.display = "none";
    document.getElementById("routeComparison").style.display = "none";

    searchButton.disabled = true;
    message.textContent = "Searching for safer routes...";

    try {

        // Find starting location
        var startResponse = await fetch(
            "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=bd&q=" +
            encodeURIComponent(start + ", Bangladesh")
        );

        var startData = await startResponse.json();

        // Find destination
        var destResponse = await fetch(
            "https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=bd&q=" +
            encodeURIComponent(destination + ", Bangladesh")
        );

        var destData = await destResponse.json();

        if (startData.length === 0 || destData.length === 0) {
            throw new Error("Location not found. Try a more specific name.");
        }

        var startLat = parseFloat(startData[0].lat);
        var startLon = parseFloat(startData[0].lon);

        var destLat = parseFloat(destData[0].lat);
        var destLon = parseFloat(destData[0].lon);

        // Get alternative routes from OSRM
        var routeUrl =
            "https://router.project-osrm.org/route/v1/driving/" +
            startLon + "," + startLat + ";" +
            destLon + "," + destLat +
            "?alternatives=true&overview=full&geometries=geojson&steps=true";

        var routeResponse = await fetch(routeUrl);

        var routeData = await routeResponse.json();

        if (routeData.code !== "Ok" || !routeData.routes.length) {
            throw new Error("No route found.");
        }

        // Load approved hazards
        message.textContent = "Checking approved road hazards...";

        var hazardResponse = await fetch("get_approved_hazards.php");

        var hazardText = await hazardResponse.text();

        console.log("HAZARD RESPONSE:", hazardText);

        var hazardData = JSON.parse(hazardText);

        if (!hazardData.success || !Array.isArray(hazardData.hazards)) {
            throw new Error("Unable to load approved hazards.");
        }

        var hazards = hazardData.hazards;

        // Calculate risk for every available route
        var routeResults = [];

        routeData.routes.forEach(function(route) {

            var risk = calculateRouteRisk(route, hazards);

            routeResults.push({
                route: route,
                riskScore: risk.riskScore,
                nearbyHazards: risk.nearbyHazards
            });

        });

        // Select route with lowest risk score.
        // If scores are equal, choose the shorter route.
        routeResults.sort(function(a, b) {

            if (a.riskScore !== b.riskScore) {
                return a.riskScore - b.riskScore;
            }

            return a.route.distance - b.route.distance;

        });

        var selected = routeResults[0];

        // Draw every route: alternatives in colors, recommended in green
        routeResults.forEach(function(item, index) {

            var isSelected = index === 0;

            item.color = isSelected ? "#176b45" : altColors[(index - 1) % altColors.length];
            item.label = isSelected ? "Recommended Safer Route" : "Alternative Route " + index;
            item.roads = getMainRoads(item.route, 5);

            var latLngs = item.route.geometry.coordinates.map(function(point) {
                return [point[1], point[0]];
            });

            var lineOptions = {
                color: item.color,
                weight: isSelected ? 7 : 5,
                opacity: isSelected ? 0.9 : 0.7
            };

            if (!isSelected) {
                lineOptions.dashArray = "8, 8";
                item.diff = findRouteDifference(item.route, selected.route);
            }

            item.line = L.polyline(latLngs, lineOptions);

            item.line.on("click", function() {
                highlightRoute(routeResults, index);
            });

            routeLayers.addLayer(item.line);
        });

        var selectedCoordinates = selected.route.geometry.coordinates;
        var selectedLine = selected.line;

        selectedLine.bringToFront();

        // Labels + turn points for all routes
        addRouteLabels(routeResults);

        // Starting location marker
        L.marker([startLat, startLon])
            .addTo(locationLayer)
            .bindPopup("Starting Location: " + start);

        // Destination marker
        L.marker([destLat, destLon])
            .addTo(locationLayer)
            .bindPopup("Destination: " + destination);

        // Display route comparison
        showRouteComparison(routeResults, 0);

        // Display hazard alert for selected route
        showHazardAlert(selected.nearbyHazards);

        // Display hazard markers for selected route
        showHazardMarkers(selected.nearbyHazards);

        // Route information
        var distance = (selected.route.distance / 1000).toFixed(2);
        var time = Math.round(selected.route.duration / 60);

        // Save selected route for logged-in user
        if (<?php echo $isLoggedIn ? 'true' : 'false'; ?>) {

            message.textContent = "Saving recommended safer route...";

            var saveResponse = await fetch("save_route.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    start_latitude: startLat,
                    start_longitude: startLon,
                    destination_latitude: destLat,
                    destination_longitude: destLon,
                    distance: distance,
                    estimated_time: time,
                    route_coordinates: selectedCoordinates
                })
            });

            var saveText = await saveResponse.text();

            console.log("SAVE RESPONSE:", saveText);

            var saveResult = JSON.parse(saveText);

            if (!saveResult.success) {
                throw new Error(
                    "Route found but could not be saved: " +
                    saveResult.message
                );
            }
        }

        // Fit map to selected route and markers
        var bounds = selectedLine.getBounds();

        routeResults.forEach(function(item) {
            bounds.extend(item.line.getBounds());
        });

        locationLayer.eachLayer(function(layer) {
            if (typeof layer.getLatLng === "function") {
                bounds.extend(layer.getLatLng());
            }
        });

        hazardLayer.eachLayer(function(layer) {
            if (typeof layer.getLatLng === "function") {
                bounds.extend(layer.getLatLng());
            }
        });

        map.fitBounds(bounds, {
            padding: [35, 35],
            maxZoom: 15
        });

        message.textContent =
            "Recommended safer route found! " +
            "Distance: " + distance + " km | " +
            "Estimated Time: " + time + " minutes | " +
            "Nearby Hazards: " + selected.nearbyHazards.length +
            " | Available Routes: " + routeResults.length;

    } catch (error) {

        message.textContent = "Error: " + error.message;

        console.error("Route Error:", error);

    } finally {

        searchButton.disabled = false;

    }

});

</script>

</body>
</html>
