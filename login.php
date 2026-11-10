
<?php
session_start();
require_once "connection.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = mysqli_prepare(
        $conn,
        "SELECT user_id, name, password, role
         FROM users
         WHERE email = ?"
    );

    if (!$stmt) {
        die("Database query failed.");
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user["password"])) {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["name"] = $user["name"];
        $_SESSION["role"] = $user["role"];

        // Success notification
        $_SESSION["flash_message"] = "Login successful! Welcome to NavSafe AI.";
        $_SESSION["flash_type"] = "success";

        mysqli_stmt_close($stmt);

        if ($user["role"] === "admin") {
    header("Location: admin_dashboard.php");
} else {
    header("Location: dashboard.php");
}
exit;

    } else {

        // Error notification
        $_SESSION["flash_message"] = "Incorrect email or password!";
        $_SESSION["flash_type"] = "error";

        mysqli_stmt_close($stmt);

        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | NavSafe AI</title>
</head>

<body>

    <?php include "notification.php"; ?>

    <h1>NavSafe AI - Login</h1>

    <form method="POST" action="">

        <label>Email</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Login</button>

    </form>

    <p>Don't have an account?
        <a href="register.php">Register</a>
    </p>

    <p><a href="index.php">Back to Home</a></p>

</body>
</html>
