<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Insert User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['create_user'])) {
    $username    = trim($_POST['username']);
    $password    = $_POST['password'];
    $role        = $_POST['role'];
    $fullName    = trim($_POST['fullName']);
    $email       = trim($_POST['email']);
    $status      = $_POST['status'];
    $dateCreated = date("Y-m-d H:i:s");

    $sql = "INSERT INTO usermanagement 
            (username, password, role, fullName, email, status, dateCreated) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $username, $password, $role, $fullName, $email, $status, $dateCreated);

    if ($stmt->execute()) {
        echo "<script>alert('✅ User account created successfully!');</script>";
    } else {
        echo "<script>alert('❌ Error creating account: " . $conn->error . "');</script>";
    }
    $stmt->close();
}

// ✅ Fetch users by role (AJAX)
if (isset($_GET['role'])) {
    $role = $_GET['role'];
    $stmt = $conn->prepare("SELECT username, role, fullName, email, status, dateCreated FROM usermanagement WHERE role=?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table class='user-table'>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Date Created</th>
                </tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr>
                    <td>{$row['username']}</td>
                    <td>{$row['role']}</td>
                    <td>{$row['fullName']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['status']}</td>
                    <td>{$row['dateCreated']}</td>
                  </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No users found for role: <strong>$role</strong></p>";
    }
    exit; // Important for AJAX response
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" type="text/css" href="css/Usermanagement.css">
    <style>
        .form-section { display: none;  padding: 15px; background: #716d6dff; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1);}
        .form-section.active { display: block; }
        .user-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .user-table th, .user-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .user-table th { background-color: #2f3542; color: white; }

        .user-management-container { display:flex; gap:20px; margin-top:20px; }
        .user-table-section { flex:2; }
        .delete-user-section { flex:1; background:#fff; padding:15px; border-radius:8px; box-shadow:0 0 8px rgba(0,0,0,0.1); }
        .delete-user-section input { width:100%; padding:6px; margin-top:4px; margin-bottom:10px; border:1px solid #ccc; border-radius:4px; }
        .delete-user-section button { cursor:pointer; background:#e74c3c; color:#fff; border:none; padding:8px 12px; border-radius:5px; }
    </style>
</head>
<body>

    <?php
            include("admin-sidebar.php")
    ?>
    
<div class="main-content">
    <header class="topbar">
        <h1>🧾 User Management</h1>
    </header>

<section id="createForm" class="form-section active">
    <h3>➕ Create User</h3>
    <form method="POST" id="createUserForm">
        <label>Username:</label>
        <input type="text" name="username" required>
        
        <label>Password:</label>
        <input type="password" id="password" name="password" required>
        
        <label>Confirm Password:</label>
        <input type="password" id="confirm_pass" name="confirm_pass" required>
        
        <div id="confirmError" style="color: red; margin-top: 5px; display: none; font-weight: bold;">
            ❌ Passwords do not match!
        </div>

        <div id="message" style="margin: 10px 0; padding: 10px; border: 1px solid #ccc; background-color: #f9f9f9; display: none;">
            <p style="margin: 3px 0; font-size: 0.9em;">Password must contain:</p>
            <p id="letter" class="invalid" style="margin: 3px 0; color: #ff6666;">• At least <b>one lowercase letter</b></p>
            <p id="capital" class="invalid" style="margin: 3px 0; color: #ff6666;">• At least <b>one capital letter</b></p>
            <p id="number" class="invalid" style="margin: 3px 0; color: #ff6666;">• At least <b>one number</b></p>
            <p id="length" class="invalid" style="margin: 3px 0; color: #ff6666;">• Be at least <b>8 characters</b></p>
        </div>
        
        <label>Role:</label>
        <select name="role" required>
            <option value="">-- Select Role --</option>
            <option value="Admin">Admin</option>
            <option value="Sales">Sales</option>
            <option value="Accountant">Accountant</option>
        </select>
        <label>Full Name:</label>
        <input type="text" name="fullName" required>
        <label>Email:</label>
        <input type="email" name="email" required>
        <label>Status:</label>
        <select name="status" required>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
        <label>Date Created:</label>
        <input type="text" name="dateCreated" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
        <button type="submit" name="create_user">Create User</button>
    </form>
</section>

<div class="user-management-container">
    <div class="user-table-section">
        <h3>👥 View Users</h3>
        <select onchange="filterUsers(this.value)">
            <option value="">-- Select Role --</option>
            <option value="Admin">Admin</option>
            <option value="Sales">Sales</option>
            <option value="Accountant">Accountant</option>
        </select>
        <div id="userList"></div>
    </div>

    <div class="delete-user-section">
        <h3>❌ Delete User</h3>
        <form method="POST" action="delete_user.php">
            <label>Username to Delete:</label>
            <input type="text" name="delete_username" required>
            <button type="submit" name="delete_user">Delete</button>
        </form>
    </div>
</div>

<script>
    
    // Existing filterUsers function
    function filterUsers(role) {
        if(role === "") {
            document.getElementById("userList").innerHTML = "";
            return;
        }
        fetch("<?php echo $_SERVER['PHP_SELF']; ?>?role=" + role)
            .then(response => response.text())
            .then(data => {
                document.getElementById("userList").innerHTML = data;
            });
    }

    // New Password Validation Logic
    const passwordField = document.getElementById("password");
    const confirmField = document.getElementById("confirm_pass");
    const confirmError = document.getElementById("confirmError");
    const form = document.getElementById("createUserForm");

    // Requirement elements
    const letter = document.getElementById("letter");
    const capital = document.getElementById("capital");
    const number = document.getElementById("number");
    const length = document.getElementById("length");

    // Helper to switch validation status classes
    function checkRequirement(element, regex, value) {
        if (regex.test(value)) {
            element.className = "valid";
            element.style.color = "#00b300"; // Green for valid
        } else {
            element.className = "invalid";
            element.style.color = "#ff6666"; // Red for invalid
        }
    }

    // Show requirements when password field is focused
    passwordField.onfocus = function () {
        document.getElementById("message").style.display = "block";
    };

    // Hide requirements when password field loses focus (optional, but in the old design)
    passwordField.onblur = function () {
        document.getElementById("message").style.display = "none";
    };

    // Real-time validation as the user types
    passwordField.onkeyup = function () {
        const value = passwordField.value;
        
        // 1. Validate requirements
        checkRequirement(letter, /[a-z]/, value);
        checkRequirement(capital, /[A-Z]/, value);
        checkRequirement(number, /\d/, value);
        checkRequirement(length, value.length >= 8, true); // For length, the value is the boolean result
        
        // 2. Check if passwords match (in case user types in password first, then confirm)
        if (confirmField.value !== "") {
             confirmError.style.display = (value === confirmField.value) ? "none" : "block";
        }
    };
    
    // Check for password match in the confirm field
    confirmField.onkeyup = function() {
        confirmError.style.display = (passwordField.value === confirmField.value) ? "none" : "block";
    };


    // Final validation upon form submission
    form.onsubmit = function (event) {
        const password = passwordField.value;
        const confirm = confirmField.value;

        // 1. Check all password requirements are met
        const hasLetter = /[a-z]/.test(password);
        const hasCapital = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const isLongEnough = password.length >= 8;
        const isSecure = hasLetter && hasCapital && hasNumber && isLongEnough;

        if (!isSecure) {
            event.preventDefault();
            document.getElementById("message").style.display = "block"; // Show requirements
            alert("Password does not meet the required conditions!");
            return; // Stop submission
        }

        // 2. Check for password match
        if (password !== confirm) {
            event.preventDefault();
            confirmError.style.display = "block";
            alert("Passwords do not match!");
        } else {
            confirmError.style.display = "none";
            // If the form reaches this point and is not prevented, it will submit.
        }
        
    
    };
</script>