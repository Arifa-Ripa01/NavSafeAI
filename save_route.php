
<?php
session_start();
require_once "connection.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "message" => "Please login first."
    ]);
    exit;
}

$user_id = (int) $_SESSION["user_id"];

$data = json_decode(file_get_contents("php://input"), true);

if (
    !$data ||
    empty($data["route_coordinates"]) ||
    !isset(
        $data["start_latitude"],
        $data["start_longitude"],
        $data["destination_latitude"],
        $data["destination_longitude"],
        $data["distance"],
        $data["estimated_time"]
    )
) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid route data."
    ]);
    exit;
}

$start_lat = (float) $data["start_latitude"];
$start_lon = (float) $data["start_longitude"];
$dest_lat = (float) $data["destination_latitude"];
$dest_lon = (float) $data["destination_longitude"];
$distance = (float) $data["distance"];
$estimated_time = (int) $data["estimated_time"];

$route_coordinates = $data["route_coordinates"];

if (count($route_coordinates) < 2) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid route coordinates."
    ]);
    exit;
}

// Save route
$sql = "INSERT INTO routes
(user_id, start_latitude, start_longitude,
 destination_latitude, destination_longitude,
 distance, estimated_time)
VALUES (?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param(
    $stmt,
    "idddddi",
    $user_id,
    $start_lat,
    $start_lon,
    $dest_lat,
    $dest_lon,
    $distance,
    $estimated_time
);

if (!mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "success" => false,
        "message" => mysqli_stmt_error($stmt)
    ]);
    mysqli_stmt_close($stmt);
    exit;
}

$route_id = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);


// Calculate distance between hazard and route segment
function pointToSegmentDistance($lat, $lon, $lat1, $lon1, $lat2, $lon2) {

    $earth_radius = 6371000;
    $reference_lat = deg2rad($lat);

    $x = $earth_radius * deg2rad($lon) * cos($reference_lat);
    $y = $earth_radius * deg2rad($lat);

    $x1 = $earth_radius * deg2rad($lon1) * cos($reference_lat);
    $y1 = $earth_radius * deg2rad($lat1);

    $x2 = $earth_radius * deg2rad($lon2) * cos($reference_lat);
    $y2 = $earth_radius * deg2rad($lat2);

    $dx = $x2 - $x1;
    $dy = $y2 - $y1;

    $length = ($dx * $dx) + ($dy * $dy);

    if ($length == 0) {
        return sqrt(
            pow($x - $x1, 2) +
            pow($y - $y1, 2)
        );
    }

    $t = (($x - $x1) * $dx + ($y - $y1) * $dy) / $length;

    $t = max(0, min(1, $t));

    $closest_x = $x1 + ($t * $dx);
    $closest_y = $y1 + ($t * $dy);

    return sqrt(
        pow($x - $closest_x, 2) +
        pow($y - $closest_y, 2)
    );
}


// Get approved hazards
$hazard_sql = "SELECT hazard_id, latitude, longitude
               FROM hazards
               WHERE status = 'approved'
               AND latitude IS NOT NULL
               AND longitude IS NOT NULL
               AND latitude != 0
               AND longitude != 0";

$hazard_result = mysqli_query($conn, $hazard_sql);

if (!$hazard_result) {
    echo json_encode([
        "success" => false,
        "message" => "Could not fetch hazards: " . mysqli_error($conn)
    ]);
    exit;
}

$total_hazards = mysqli_num_rows($hazard_result);
$matched_hazards = 0;
$insert_errors = [];

$nearest_distance = null;


// Check hazards against route
while ($hazard = mysqli_fetch_assoc($hazard_result)) {

    $hazard_lat = (float) $hazard["latitude"];
    $hazard_lon = (float) $hazard["longitude"];

    $minimum_distance = PHP_FLOAT_MAX;

    for ($i = 0; $i < count($route_coordinates) - 1; $i++) {

        $point1 = $route_coordinates[$i];
        $point2 = $route_coordinates[$i + 1];

        // OSRM coordinates format: [longitude, latitude]
        $meters = pointToSegmentDistance(
            $hazard_lat,
            $hazard_lon,
            (float) $point1[1],
            (float) $point1[0],
            (float) $point2[1],
            (float) $point2[0]
        );

        if ($meters < $minimum_distance) {
            $minimum_distance = $meters;
        }
    }

    if (
        $nearest_distance === null ||
        $minimum_distance < $nearest_distance
    ) {
        $nearest_distance = $minimum_distance;
    }

    // Save hazard if it is within 500 meters
    if ($minimum_distance <= 500) {

        $hazard_id = (int) $hazard["hazard_id"];

        $insert_sql = "INSERT IGNORE INTO route_hazards
                       (route_id, hazard_id)
                       VALUES (?, ?)";

        $insert_stmt = mysqli_prepare($conn, $insert_sql);

        if (!$insert_stmt) {
            $insert_errors[] = mysqli_error($conn);
            continue;
        }

        mysqli_stmt_bind_param(
            $insert_stmt,
            "ii",
            $route_id,
            $hazard_id
        );

        if (mysqli_stmt_execute($insert_stmt)) {

            $matched_hazards += mysqli_stmt_affected_rows($insert_stmt);

        } else {

            $insert_errors[] = mysqli_stmt_error($insert_stmt);

        }

        mysqli_stmt_close($insert_stmt);
    }
}


// Send response
echo json_encode([
    "success" => true,
    "message" => "Route saved successfully.",
    "route_id" => $route_id,
    "total_approved_hazards" => $total_hazards,
    "matched_hazards" => $matched_hazards,
    "nearest_hazard_distance_meters" => $nearest_distance !== null
        ? round($nearest_distance, 2)
        : null,
    "insert_errors" => $insert_errors
]);

?>
