
<?php
session_start();
require_once "connection.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    if ($name === "" || $email === "" || $password === "") {

        $message = "Please fill in all required fields.";

    } else {

        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($check, "s", $email);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "This email is already registered.";

        } else {

            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $role = "user";

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password, phone, role)
                 VALUES (?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $name,
                $email,
                $hashed_password,
                $phone,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {

                $_SESSION["flash_message"] =
                    "Registration successful! Please login.";

                $_SESSION["flash_type"] = "success";

                mysqli_stmt_close($stmt);
                mysqli_stmt_close($check);

                header("Location: login.php");
                exit;

            } else {

                $message = "Registration failed. Please try again.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($check);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | NavSafe AI</title>
</head>

<body>

    <?php include "notification.php"; ?>

    <h1>NavSafe AI - Create Account</h1>

    <?php if ($message !== ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Full Name *</label><br>
        <input type="text" name="name" required><br><br>

        <label>Email *</label><br>
        <input type="email" name="email" required><br><br>

        <label>Phone</label><br>
        <input type="text" name="phone"><br><br>

        <label>Password *</label><br>
        <input type="password" name="password" required minlength="6"><br><br>

        <button type="submit">Create Account</button>

    </form>

    <p>Already have an account?
        <a href="login.php">Login</a>
    </p>

    <p><a href="index.php">Back to Home</a></p>

</body>
</html>
