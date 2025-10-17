<?php
include('connection.php');

// ✅ Ensure PHP uses same timezone as your DB
date_default_timezone_set('Asia/Manila'); 

$error = "";
$correct = "";
$token = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password']; // no hashing for now

    // Verify token and expiry (check against current PHP time)
    $stmt = $conn->prepare("SELECT * FROM usermanagement WHERE reset_token = ? AND token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update password + clear token
        $stmt2 = $conn->prepare("UPDATE usermanagement 
                                SET password=?, reset_token=NULL, token_expiry=NULL 
                                WHERE reset_token=?");
        $stmt2->bind_param("ss", $newPassword, $token);

        if ($stmt2->execute()) {
            $correct = "✅ Password updated successfully! <a href='login.php'>Click here to log in</a>.";
        } else {
            $error = "❌ Error updating password: " . $conn->error;
        }
    } else {
        $error = "❌ Invalid or expired token.";
    }

    $stmt->close();
    $conn->close();
} else if (isset($_GET['token'])) {
    $token = $_GET['token'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="login-container">
        <h2>Reset Password</h2>
        <form method="POST" action="reset_password.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="new_password">Enter new password:</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
            <button type="submit">Reset Password</button>
        </form>

        <?php if (!empty($correct)) echo "<p style='color:green; text-align:center;'>$correct</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

        <p style="text-align:center; margin-top:15px;">
            <a href="login.php">⬅ Back to Login</a>
        </p>
    </div>
</body>
</html>

