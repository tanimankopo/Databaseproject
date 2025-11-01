<?php
session_start();
require 'db.php'; // database connection

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Select from usermanagement table
    $stmt = $conn->prepare("SELECT userID, username, password, role, fullName, status 
                            FROM usermanagement 
                            WHERE username = ? AND status = 'Active' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_username, $db_password, $db_role, $db_fullName, $db_status);
        $stmt->fetch();

        // ✅ Plain-text password check (for school project only)
        if ($password === $db_password) {
            session_regenerate_id(true);

            // Store user session data
            $_SESSION['user_id']  = $user_id;
            $_SESSION['username'] = $db_username;
            $_SESSION['role']     = $db_role;
            $_SESSION['fullName'] = $db_fullName;

            // ✅ Redirect based on role
            if ($db_role === "Admin") {
                header("Location: admin-dashboard.php");
            } 
            elseif ($db_role === "sales" ) {
                header("Location: sales-dashboard.php");  
            }
              elseif ($db_role === "Cashier")
                header("location: -cashier-dashboard.php");
             else {
                $error = "Unknown role assigned.";
            }
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>User Login</title>
    <link rel="stylesheet" type="text/css" href="css/login.css">
</head>
<body>
    <div class="login-container">
        
        <h2>User Login</h2>

        <!-- ✅ Error message (hidden if empty) -->
        <?php if (!empty($error)) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>

        <form method="POST" action="login.php">
            <label>Username:</label>
            <input type="text" name="username" required autofocus>

            <label>Password:</label>
            <input type="password" name="password" required>

            <button type="submit" name="login">Login</button>
        </form>

        <!-- ✅ Forgot Password link -->
        <div class="forgot-link">
            <a href="forget_password.php">Forgot Password?</a>
        </div>
    </div>
</body>
</html>

