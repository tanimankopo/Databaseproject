<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/PHPMailer/src/Exception.php';

include('connection.php');

$correct = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Normalize email (remove spaces + lowercase)
    $email = trim(strtolower($_POST['email']));

    // Check if email exists in usermanagement (case-insensitive)
    $stmt = $conn->prepare("SELECT * FROM usermanagement WHERE LOWER(email) = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate token + expiry
        $token = bin2hex(random_bytes(15));
        $expiry = date("Y-m-d H:i:s", strtotime('+24 hours'));

        // Save token into DB
        $stmt2 = $conn->prepare("UPDATE usermanagement SET reset_token=?, token_expiry=? WHERE LOWER(email)=?");
        $stmt2->bind_param("sss", $token, $expiry, $email);

        if ($stmt2->execute()) {
            $resetLink = "http://localhost/databasefinal/reset_password.php?token=" . $token;

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'ascelglimer2@gmail.com';
                $mail->Password = 'gpty cwmm gmhr onxg'; // Gmail App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('ascelglimer2@gmail.com', '1Garage Support');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset - 1Garage';
                $mail->Body = "Click the link to reset your password:<br><a href='$resetLink'>$resetLink</a>";

                $mail->send();
                $correct = "✅ Password reset link has been sent to your email.";
            } catch (Exception $e) {
                $error = "❌ Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            $error = "❌ Error saving token: " . $conn->error;
        }
    } else {
        $error = "❌ No account found with that email.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/forget_password.css">
</head>
<body>
    <div class="login-container">
        <h2>Forgot Password</h2>
        <form method="POST" action="forget_password.php">
            <label for="email">Enter your email address:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
            <button type="submit">Send Reset Link</button>
        </form>

        <?php if (!empty($correct)) echo "<p style='color:green; text-align:center;'>$correct</p>"; ?>
        <?php if (!empty($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

        <p style="text-align:center; margin-top:15px;">
            <a href="login.php">⬅ Back to Login</a>
        </p>
    </div>
</body>
</html>
