
<?php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get notification message
$message = $_SESSION["flash_message"] ?? "";
$type = $_SESSION["flash_type"] ?? "success";

// Remove message after displaying
unset($_SESSION["flash_message"]);
unset($_SESSION["flash_type"]);

?>

<?php if ($message !== ""): ?>

    <div class="notification <?php echo htmlspecialchars($type); ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>

<?php endif; ?>

<style>
.notification {
    padding: 14px 18px;
    margin: 15px auto;
    border-radius: 7px;
    text-align: center;
    font-family: Arial, sans-serif;
    font-weight: bold;
    max-width: 600px;
}

.notification.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.notification.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.notification.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
</style>
