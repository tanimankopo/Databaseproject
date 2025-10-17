<?php
session_start();

// ✅ Unset all session variables
$_SESSION = [];

// ✅ Destroy the session
session_destroy();

// ✅ Delete the session cookie (optional but safer)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// ✅ Redirect to login page
header("Location: login.php");
exit();
?>
