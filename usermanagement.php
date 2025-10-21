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
        echo "<script>alert('✅ User account created successfully!'); window.location='usermanagement.php';</script>";
    } else {
        echo "<script>alert('❌ Error creating account: " . $conn->error . "'); window.location='usermanagement.php';</script>";
    }
    $stmt->close();
}

// ✅ Delete User
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_user'])) {
    $delete_username = trim($_POST['delete_username']);
    
    $stmt = $conn->prepare("DELETE FROM usermanagement WHERE username = ?");
    $stmt->bind_param("s", $delete_username);
    
    if ($stmt->execute()) {
        echo "<script>alert('🗑️ User deleted successfully!'); window.location='usermanagement.php';</script>";
    } else {
        echo "<script>alert('❌ Error deleting user.'); window.location='usermanagement.php';</script>";
    }
    $stmt->close();
}

// ✅ Get statistics
$totalUsersResult = $conn->query("SELECT COUNT(*) as total FROM usermanagement");
$totalUsers = $totalUsersResult->fetch_assoc()['total'];

$adminUsersResult = $conn->query("SELECT COUNT(*) as admin FROM usermanagement WHERE role = 'Admin'");
$adminUsers = $adminUsersResult->fetch_assoc()['admin'];

$salesUsersResult = $conn->query("SELECT COUNT(*) as sales FROM usermanagement WHERE role = 'Sales'");
$salesUsers = $salesUsersResult->fetch_assoc()['sales'];

$accountantUsersResult = $conn->query("SELECT COUNT(*) as accountant FROM usermanagement WHERE role = 'Accountant'");
$accountantUsers = $accountantUsersResult->fetch_assoc()['accountant'];

$activeUsersResult = $conn->query("SELECT COUNT(*) as active FROM usermanagement WHERE status = 'Active'");
$activeUsers = $activeUsersResult->fetch_assoc()['active'];

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

// ✅ Fetch all users for display
$allUsersResult = $conn->query("SELECT * FROM usermanagement ORDER BY dateCreated DESC");
if (!$allUsersResult) {
    die("❌ Error fetching users: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - 1-GARAGE</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #1a1d29;
            color: #e4e7eb;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 220px;
            background: #252836;
            padding: 20px 0;
            display: flex;
            flex-direction: column;
        }

        .logo {
            padding: 0 20px 30px;
            font-size: 20px;
            font-weight: bold;
            color: #fff;
        }

        .nav-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav-links li {
            margin: 0;
        }

        .nav-links a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #b0b3ba;
            text-decoration: none;
            transition: all 0.3s;
        }

        .nav-links a:hover {
            background: #2d303e;
            color: #fff;
        }

        .nav-links a.active {
            background: #3b3f51;
            color: #fff;
            border-left: 3px solid #5c9eff;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
        }

        .sidebar-footer button {
            width: 100%;
            padding: 12px;
            background: #ff6b6b;
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .sidebar-footer button:hover {
            background: #ff5252;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #5c9eff;
            color: #fff;
        }

        .btn-primary:hover {
            background: #4a8de8;
        }

        .btn-secondary {
            background: #3b3f51;
            color: #e4e7eb;
        }

        .btn-secondary:hover {
            background: #4a5061;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .stat-card h3 {
            color: #b0b3ba;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stat-trend {
            font-size: 12px;
            color: #4caf50;
        }

        .users-section {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-size: 18px;
        }

        .search-filter {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        .filter-select {
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
            cursor: pointer;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 12px;
            background: #2d303e;
            font-weight: 600;
            font-size: 13px;
            color: #b0b3ba;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px 12px;
            border-bottom: 1px solid #2d303e;
        }

        tr:hover {
            background: #2d303e;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #5c9eff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-username {
            font-size: 12px;
            color: #b0b3ba;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.admin {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .badge.sales {
            background: rgba(255, 167, 38, 0.2);
            color: #ffa726;
        }

        .badge.accountant {
            background: rgba(92, 158, 255, 0.2);
            color: #5c9eff;
        }

        .badge.active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .badge.inactive {
            background: rgba(158, 158, 158, 0.2);
            color: #9e9e9e;
        }

        .action-btn {
            padding: 6px 12px;
            background: #3b3f51;
            border: none;
            border-radius: 6px;
            color: #e4e7eb;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #4a5061;
        }

        .action-btn.danger {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .action-btn.danger:hover {
            background: rgba(255, 107, 107, 0.3);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #252836;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close-btn {
            background: none;
            border: none;
            color: #b0b3ba;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-btn:hover {
            color: #fff;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b0b3ba;
            font-size: 14px;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #5c9eff;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .password-requirements {
            margin-top: 8px;
            padding: 10px;
            background: #2d303e;
            border-radius: 6px;
            font-size: 12px;
            color: #b0b3ba;
        }

        .password-requirements ul {
            margin-left: 20px;
            margin-top: 5px;
        }

        .password-requirements li {
            margin-bottom: 3px;
        }

        .info-box {
            background: rgba(92, 158, 255, 0.1);
            border-left: 3px solid #5c9eff;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #b0b3ba;
        }

        .delete-section {
            background: #252836;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #2d303e;
            margin-top: 20px;
        }

        .delete-section h3 {
            color: #ff6b6b;
            margin-bottom: 15px;
        }

        .delete-form {
            display: flex;
            gap: 12px;
            align-items: end;
        }

        .delete-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .delete-form button {
            background: #ff6b6b;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .delete-form button:hover {
            background: #ff5252;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .user-table th, .user-table td {
            border: 1px solid #2d303e;
            padding: 8px;
            text-align: left;
        }

        .user-table th {
            background-color: #2d303e;
            color: #b0b3ba;
        }

        .user-table tr:hover {
            background: #2d303e;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include("sidebar-admin.php"); ?>

        <main class="main-content">
            <div class="header">
                <h1><span>👤</span> User Management</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary">
                        <span>📥</span> Import
                    </button>
                    <button class="btn btn-secondary">
                        <span>📤</span> Export
                    </button>
                    <button class="btn btn-primary" onclick="openCreateModal()">
                        <span>+</span> Create User
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Users</h3>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-trend">System users</div>
                </div>
                <div class="stat-card">
                    <h3>Administrators</h3>
                    <div class="stat-value"><?= $adminUsers ?></div>
                    <div class="stat-trend">Admin access</div>
                </div>
                <div class="stat-card">
                    <h3>Sales Staff</h3>
                    <div class="stat-value"><?= $salesUsers ?></div>
                    <div class="stat-trend">Sales team</div>
                </div>
                <div class="stat-card">
                    <h3>Active Users</h3>
                    <div class="stat-value"><?= $activeUsers ?></div>
                    <div class="stat-trend">Currently active</div>
                </div>
            </div>

            <div class="users-section">
                <div class="section-header">
                    <h2>System Users</h2>
                </div>

                <div class="search-filter">
                    <input type="text" class="search-box" placeholder="Search users by name, username, or email..." id="searchBox">
                    <select class="filter-select" id="roleFilter">
                        <option>All Roles</option>
                        <option>Admin</option>
                        <option>Sales</option>
                        <option>Accountant</option>
                    </select>
                    <select class="filter-select" id="statusFilter">
                        <option>All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Date Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $allUsersResult->fetch_assoc()): 
                                // Generate initials from full name
                                $initials = '';
                                $nameParts = explode(' ', $row['fullName']);
                                foreach($nameParts as $part) {
                                    if (!empty($part)) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                }
                                $initials = substr($initials, 0, 2); // Take first 2 initials
                                
                                // Determine role badge class
                                $roleClass = strtolower($row['role']);
                                $statusClass = strtolower($row['status']);
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar"><?= $initials ?></div>
                                        <div class="user-details">
                                            <div class="user-name"><?= htmlspecialchars($row['fullName']) ?></div>
                                            <div class="user-username">@<?= htmlspecialchars($row['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><span class="badge <?= $roleClass ?>"><?= $row['role'] ?></span></td>
                                <td><span class="badge <?= $statusClass ?>"><?= $row['status'] ?></span></td>
                                <td>
                                    <div><?= date('Y-m-d', strtotime($row['dateCreated'])) ?></div>
                                    <div style="font-size: 12px; color: #b0b3ba;"><?= date('H:i A', strtotime($row['dateCreated'])) ?></div>
                                </td>
                                <td>
                                    <button class="action-btn">📝 Edit</button>
                                    <button class="action-btn">🔒 Reset</button>
                                    <button class="action-btn danger" onclick="openDeleteModal('<?= htmlspecialchars($row['username']) ?>')">🗑️ Delete</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="delete-section">
                <h3>❌ Delete User</h3>
                <form method="POST" action="usermanagement.php" class="delete-form">
                    <div class="form-group">
                        <label>Username to Delete:</label>
                        <input type="text" name="delete_username" class="form-input" placeholder="Enter username to delete" required>
                    </div>
                    <button type="submit" name="delete_user">Delete User</button>
                </form>
            </div>
        </main>
    </div>

    <!-- Create User Modal -->
    <div class="modal" id="createModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><span>✨</span> Create New User</h2>
                <button class="close-btn" onclick="closeCreateModal()">&times;</button>
            </div>

            <div class="info-box">
                <strong>ℹ️ Note:</strong> New users will receive an email with their login credentials. They will be required to change their password on first login.
            </div>

            <form method="POST" action="usermanagement.php" id="createUserForm">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" class="form-input" placeholder="Enter username (e.g., johndoe)" required>
                </div>

                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullName" class="form-input" placeholder="Enter full name" required>
                </div>

                <div class="form-group">
                    <label>Email Address:</label>
                    <input type="email" name="email" class="form-input" placeholder="email@1garage.com" required>
                </div>

                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter secure password" required>
                    <div class="password-requirements">
                        <strong>Password Requirements:</strong>
                        <ul>
                            <li id="letter" class="invalid">• At least <b>one lowercase letter</b></li>
                            <li id="capital" class="invalid">• At least <b>one capital letter</b></li>
                            <li id="number" class="invalid">• At least <b>one number</b></li>
                            <li id="length" class="invalid">• Be at least <b>8 characters</b></li>
                        </ul>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password:</label>
                    <input type="password" id="confirm_pass" name="confirm_pass" class="form-input" placeholder="Re-enter password" required>
                    <div id="confirmError" style="color: #ff6b6b; margin-top: 5px; display: none; font-weight: bold;">
                        ❌ Passwords do not match!
                    </div>
                </div>

                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" class="form-input" required>
                        <option value="">-- Select Role --</option>
                        <option value="Admin">Admin</option>
                        <option value="Sales">Sales</option>
                        <option value="Accountant">Accountant</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" class="form-input" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
                    <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><span>🗑️</span> Delete User</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <form method="POST" action="usermanagement.php">
                <div class="form-group">
                    <label>Username to Delete:</label>
                    <input type="text" name="delete_username" id="deleteUsername" class="form-input" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_user" class="btn btn-primary" style="background: #ff6b6b;">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        function openDeleteModal(username) {
            document.getElementById('deleteUsername').value = username;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const createModal = document.getElementById('createModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == createModal) {
                closeCreateModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

        // Password validation logic
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
                element.style.color = "#4caf50"; // Green for valid
            } else {
                element.className = "invalid";
                element.style.color = "#ff6b6b"; // Red for invalid
            }
        }

        // Real-time validation as the user types
        passwordField.onkeyup = function () {
            const value = passwordField.value;
            
            // 1. Validate requirements
            checkRequirement(letter, /[a-z]/, value);
            checkRequirement(capital, /[A-Z]/, value);
            checkRequirement(number, /\d/, value);
            checkRequirement(length, value.length >= 8, true);
            
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

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Role filter functionality
        document.getElementById('roleFilter').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const roleBadge = row.querySelector('.badge.admin, .badge.sales, .badge.accountant');
                if (!roleBadge) return;
                
                const role = roleBadge.textContent.toLowerCase();
                let show = true;
                
                if (filter === 'Admin' && !role.includes('admin')) show = false;
                if (filter === 'Sales' && !role.includes('sales')) show = false;
                if (filter === 'Accountant' && !role.includes('accountant')) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        });

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusBadge = row.querySelector('.badge.active, .badge.inactive');
                if (!statusBadge) return;
                
                const status = statusBadge.textContent.toLowerCase();
                let show = true;
                
                if (filter === 'Active' && !status.includes('active')) show = false;
                if (filter === 'Inactive' && !status.includes('inactive')) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        });
    </script>
</body>
</html>