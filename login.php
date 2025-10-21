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
                header("Location: dashboard-admin.php");
            } elseif ($db_role === "sales" || $db_role === "Accountant") {
                header("Location: dashboard.php");
            } else {
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>1 Garage - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 50%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 68, 68, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 215, 0, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { 
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.3;
            }
            25% { 
                transform: translate(20px, -20px) rotate(90deg);
                opacity: 0.5;
            }
            50% { 
                transform: translate(-10px, -40px) rotate(180deg);
                opacity: 0.3;
            }
            75% { 
                transform: translate(-20px, -20px) rotate(270deg);
                opacity: 0.5;
            }
        }

        .login-container {
            background: rgba(30, 30, 30, 0.95);
            border-radius: 20px;
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255, 215, 0, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .logo-box {
            display: inline-block;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            border: 4px solid #FFD700;
            border-radius: 12px;
            padding: 20px 40px;
            position: relative;
            box-shadow: 
                0 10px 30px rgba(255, 215, 0, 0.3),
                inset 0 1px 0 rgba(255, 215, 0, 0.3);
        }

        .logo-text {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .logo-number {
            font-size: 60px;
            font-weight: 900;
            color: #FF4444;
            text-shadow: 
                0 0 20px #FF4444,
                0 0 40px #FF0000,
                3px 3px 0 #000;
            letter-spacing: -3px;
        }

        .logo-garage {
            font-size: 48px;
            font-weight: 900;
            color: #FFFFFF;
            text-shadow: 
                0 0 20px #fff,
                3px 3px 0 #000;
            letter-spacing: 4px;
        }

        .logo-tagline {
            color: #FFD700;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-top: 8px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .login-title {
            color: #FFFFFF;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .login-subtitle {
            color: #999;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px;
            background: rgba(60, 70, 90, 0.5);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 12px;
            color: #FFFFFF;
            font-size: 16px;
            outline: none;
        }

        .form-input::placeholder {
            color: #666;
        }

        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .login-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #6366f1 0%, #ec4899 100%);
            border: none;
            border-radius: 12px;
            color: #FFFFFF;
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            box-shadow: 0 8px 20px rgba(236, 72, 153, 0.4);
            margin-top: 10px;
            transition: all 0.3s ease;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(236, 72, 153, 0.6);
        }

        .error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid rgba(255, 68, 68, 0.3);
            color: #FF4444;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 600;
        }

        .forgot-password {
            text-align: center;
            margin-top: 25px;
        }

        .forgot-link {
            color: #6366f1;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #ec4899;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 30px 0;
            color: #666;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,215,0,0.3), transparent);
        }

        .divider span {
            padding: 0 15px;
        }

        .additional-info {
            text-align: center;
            margin-top: 25px;
            color: #999;
            font-size: 13px;
        }

        .additional-info a {
            color: #FFD700;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .additional-info a:hover {
            color: #FF4444;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 40px 25px;
            }

            .logo-number {
                font-size: 48px;
            }

            .logo-garage {
                font-size: 38px;
            }

            .login-title {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="login-container">
        <div class="logo-container">
            <div class="logo-box">
                <div class="logo-text">
                    <span class="logo-number">1</span>
                    <span class="logo-garage">GARAGE</span>
                </div>
                <div class="logo-tagline">Auto Parts & Service</div>
            </div>
        </div>

        <div class="login-header">
            <h1 class="login-title">User Login</h1>
            <p class="login-subtitle">Welcome back! Please login to your account</p>
        </div>

        <!-- ✅ Error message (hidden if empty) -->
        <?php if (!empty($error)) echo "<div class='error'>" . htmlspecialchars($error) . "</div>"; ?>

        <form method="POST" action="login.php">
            <div class="form-group">
                <label class="form-label">Username</label>
                <div class="input-wrapper">
                    <input type="text" name="username" class="form-input" placeholder="Enter your username" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
            </div>

            <button type="submit" name="login" class="login-button">Login</button>
        </form>

        <div class="forgot-password">
            <a href="forget_password.php" class="forgot-link">Forgot Password?</a>
        </div>

        <div class="divider">
            <span>Secure Login</span>
        </div>

        <div class="additional-info">
            Don't have an account? <a href="#">Sign Up</a>
        </div>
    </div>

    <script>
        const particlesContainer = document.getElementById('particles');
        
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'particle';
            
            const size = Math.random() * 3 + 1;
            particle.style.width = size + 'px';
            particle.style.height = size + 'px';
            
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            
            const duration = Math.random() * 4 + 4;
            const delay = Math.random() * 3;
            
            particle.style.animation = `float ${duration}s ease-in-out ${delay}s infinite`;
            
            particlesContainer.appendChild(particle);
        }

        for (let i = 0; i < 25; i++) {
            createParticle();
        }

        document.querySelector('.login-button').addEventListener('click', function(e) {
            // Let the form submit naturally, don't prevent default
            this.textContent = 'Logging In...';
            setTimeout(() => {
                this.textContent = 'Login';
            }, 2000);
        });
    </script>
</body>
</html>

