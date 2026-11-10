
<?php
session_start();
require_once "connection.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    die("Access denied!");
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request!");
}

$hazard_id = intval($_POST["hazard_id"] ?? 0);
$action = $_POST["action"] ?? "";

if ($hazard_id <= 0 || !in_array($action, ["approve", "reject"])) {
    die("Invalid data!");
}

$status = ($action === "approve") ? "approved" : "rejected";

$conn->begin_transaction();

try {
    // Find the pending report and its user
    $stmt = $conn->prepare(
        "SELECT user_id FROM hazard_report WHERE hazard_id = ? LIMIT 1"
    );
    $stmt->bind_param("i", $hazard_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $report = $result->fetch_assoc();
    $stmt->close();

    if (!$report) {
        throw new Exception("Report not found!");
    }

    // Update pending hazard
    $stmt = $conn->prepare(
        "UPDATE hazards SET status = ? 
         WHERE hazard_id = ? AND status = 'pending'"
    );
    $stmt->bind_param("si", $status, $hazard_id);
    $stmt->execute();

    if ($stmt->affected_rows !== 1) {
        throw new Exception("Report already processed!");
    }
    $stmt->close();

    // Give 10 points only for approved reports
    if ($action === "approve") {
        $user_id = (int)$report["user_id"];
        $points = 10;

        $stmt = $conn->prepare(
            "INSERT INTO reward_history (user_id, hazard_id, points)
             VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iii", $user_id, $hazard_id, $points);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE users SET reward_points = reward_points + ?
             WHERE user_id = ?"
        );
        $stmt->bind_param("ii", $points, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    $_SESSION["flash_message"] = "Report " . $status . " successfully!";
    $_SESSION["flash_type"] = "success";

} catch (Throwable $e) {
    $conn->rollback();

    $_SESSION["flash_message"] = "Database Error: " . $e->getMessage();
    $_SESSION["flash_type"] = "error";
}

header("Location: admin_reports.php");
exit();
?>
