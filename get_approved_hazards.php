
<?php
require_once "connection.php";

header("Content-Type: application/json");

$sql = "SELECT hazard_id, hazard_type, road_name, description,
               latitude, longitude, severity, status
        FROM hazards
        WHERE status = 'approved'
        AND latitude IS NOT NULL
        AND longitude IS NOT NULL
        AND NOT (latitude = 0 AND longitude = 0)
        ORDER BY hazard_id DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Unable to load approved hazards."
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
