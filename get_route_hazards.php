
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
$route_id = (int) ($_GET["route_id"] ?? 0);

if ($route_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid route ID."
    ]);
    exit;
}

// Check route ownership
$check_sql = "SELECT route_id FROM routes
              WHERE route_id = ? AND user_id = ?";

$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "ii", $route_id, $user_id);
mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Route not found."
    ]);
    exit;
}

mysqli_stmt_close($check_stmt);

// Get ALL approved hazards with valid coordinates
$sql = "SELECT hazard_id, hazard_type, road_name,
               description, latitude, longitude,
               severity, status
        FROM hazards
        WHERE status = 'approved'
        AND latitude IS NOT NULL
        AND longitude IS NOT NULL
        AND latitude != 0
        AND longitude != 0";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Hazard query failed."
    ]);
    exit;
}

$hazards = [];

while ($row = mysqli_fetch_assoc($result)) {
    $hazards[] = $row;
}

echo json_encode([
    "success" => true,
    "hazards" => $hazards,
    "total_hazards" => count($hazards)
]);

?>
