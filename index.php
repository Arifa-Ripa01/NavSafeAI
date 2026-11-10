
<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NavSafe AI - Smart Navigation</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f7f8;
            color: #183746;
        }

        header {
            background: #123b50;
            color: white;
            padding: 22px 8%;
        }

        header h1 {
            margin: 0;
            font-size: 30px;
        }

        header p {
            margin-bottom: 0;
        }

        nav {
            background: white;
            padding: 16px 8%;
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
            align-items: center;
            box-shadow: 0 2px 8px #00000010;
        }

        nav a {
            color: #123b50;
            text-decoration: none;
            font-weight: bold;
        }

        nav a:hover {
            color: #087f8c;
        }

        .welcome {
            margin-left: auto;
            font-weight: bold;
            color: #087f8c;
        }

        .hero {
            padding: 65px 8%;
            background: #dceff0;
        }

        .hero h2 {
            font-size: 36px;
            max-width: 650px;
        }

        .hero p {
            font-size: 17px;
            line-height: 1.7;
            max-width: 650px;
        }

        .buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 25px;
        }

        .btn {
            display: inline-block;
            padding: 13px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }

        .primary {
            background: #087f8c;
            color: white;
        }

        .secondary {
            background: white;
            color: #123b50;
            border: 1px solid #123b50;
        }

        .features {
            padding: 35px 8%;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 3px 12px #0000000c;
        }

        footer {
            text-align: center;
            padding: 22px;
            background: #123b50;
            color: white;
        }
    </style>
</head>

<body>

<header>
    <h1>NavSafe AI</h1>
    <p>Smart Navigation with Real-Time Road Hazard Detection</p>
</header>

<nav>

    <a href="index.php">Home</a>

    <a href="report_hazard.php">Report a Hazard</a>

    <a href="view_hazards.php">View Hazards</a>
    <a href="route.php">Smart Route Navigation 🗺️</a>

    <a href="map.php">Hazard Map</a>

   
<?php if (isset($_SESSION["user_id"])): ?>

    <?php if ($_SESSION["role"] === "admin"): ?>

        <a href="admin_dashboard.php">Admin Dashboard</a>

        <a href="admin_reports.php">Manage Reports</a>

    <?php else: ?>

        <a href="dashboard.php">My Dashboard</a>

        <a href="report_history.php">My Report History</a>

    <?php endif; ?>

    <span class="welcome">
        Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>
    </span>

    <a href="logout.php">Logout</a>

<?php else: ?>

    <a href="login.php">Login</a>

    <a href="register.php">Register</a>

<?php endif; ?>


</nav>

<?php include "notification.php"; ?>

<section class="hero">

    <h2>Travel Smarter. Stay Safer.</h2>

    <p>
        NavSafe AI aims to help people identify road hazards
        and make safer travel decisions. Users can report
        road problems and view submitted hazard information.
    </p>

    <div class="buttons">

        <a class="btn primary" href="report_hazard.php">
            Report a Road Hazard
        </a>

        <a class="btn secondary" href="view_hazards.php">
            View Reported Hazards
        </a>

    </div>

</section>

<section class="features">

    <h2>Our Features</h2>

    <div class="feature-grid">

        <div class="card">
            <h3>Road Hazard Reporting</h3>
            <p>
                Submit information about potholes, broken roads,
                fallen trees and other hazards.
            </p>
        </div>

        <div class="card">
            <h3>Hazard List</h3>
            <p>
                View the road hazards saved in the project database.
            </p>
        </div>

        <div class="card">
            <h3>Safer Navigation</h3>
            <p>
                Build toward a system that can support safer
                route decisions using hazard information.
            </p>
        </div>

    </div>

</section>

<footer>
    <p>NavSafe AI | Software Project Development Lab</p>
</footer>

</body>
</html>
