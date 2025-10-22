<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) {
    header("Location: login.php");
    exit();
}

include "db.php";
include "sidebar-cashier.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message for Sales - Cashier</title>
    <link rel="stylesheet" href="css/cashier.css">
</head>
<body>

    <div class="main-content">
        <div class="topbar">
            <h1>Message for Sales</h1>
        </div>

        <!-- Message for Sales -->
        <section>
            <h2>Message for Sales</h2>
            <form action="send-message.php" method="POST">
                <textarea name="message" rows="3" cols="60" placeholder="Type message to Sales..." required></textarea><br>
                <button type="submit">Send</button>
            </form>
        </section>
    </div>

</body>
</html>