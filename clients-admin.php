<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Insert Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_client'])) {
    $clientName    = $_POST['clientName'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO clientinfo (clientName, contactNumber, email, address) 
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $clientName, $contactNumber, $email, $address);
    $stmt->execute();
    $stmt->close();

    header("Location: clients-admin.php");
    exit();
}

// ✅ Delete Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_client'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM clientinfo WHERE clientID = ?");
    $stmt->bind_param("i", $deleteID);
    $stmt->execute();
    $stmt->close();

    header("Location: clients-admin.php");
    exit();
}

// ✅ Update Client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_client'])) {
    $updateID      = intval($_POST['updateID']);
    $clientName    = $_POST['updateName'];
    $contactNumber = $_POST['updateNumber'];
    $email         = $_POST['updateEmail'];
    $address       = $_POST['updateAddress'];

    $stmt = $conn->prepare("UPDATE clientinfo 
                            SET clientName=?, contactNumber=?, email=?, address=? 
                            WHERE clientID=?");
    $stmt->bind_param("ssssi", $clientName, $contactNumber, $email, $address, $updateID);
    $stmt->execute();
    $stmt->close();

    header("Location: clients-admin.php");
    exit();
}

// ✅ Get statistics
$totalClientsResult = $conn->query("SELECT COUNT(*) as total FROM clientinfo");
$totalClients = $totalClientsResult->fetch_assoc()['total'];

$vipClientsResult = $conn->query("SELECT COUNT(*) as vip FROM clientinfo WHERE clientName LIKE '%VIP%' OR clientName LIKE '%vip%'");
$vipClients = $vipClientsResult->fetch_assoc()['vip'];

$newClientsResult = $conn->query("SELECT COUNT(*) as new FROM clientinfo WHERE registeredDate >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$newClients = $newClientsResult->fetch_assoc()['new'];

// ✅ Fetch clients
$result = $conn->query("SELECT * FROM clientinfo ORDER BY clientID ASC");
if (!$result) {
    die("❌ Error fetching clients: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients Management - 1-GARAGE</title>
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

        .clients-section {
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

        .client-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .client-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #5c9eff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
        }

        .client-details {
            flex: 1;
        }

        .client-name {
            font-weight: 600;
            margin-bottom: 2px;
        }

        .client-email {
            font-size: 12px;
            color: #b0b3ba;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.vip {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .badge.regular {
            background: rgba(92, 158, 255, 0.2);
            color: #5c9eff;
        }

        .badge.new {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
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
        }

        .close-btn {
            background: none;
            border: none;
            color: #b0b3ba;
            font-size: 24px;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #b0b3ba;
            font-size: 14px;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            background: #2d303e;
            border: 1px solid #3b3f51;
            border-radius: 8px;
            color: #e4e7eb;
        }

        textarea.form-input {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <?php include("sidebar-admin.php"); ?>

        <main class="main-content">
            <div class="header">
                <h1><span>👥</span> Clients Management</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary">
                        <span>📥</span> Import
                    </button>
                    <button class="btn btn-secondary">
                        <span>📤</span> Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <span>+</span> Add Client
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Clients</h3>
                    <div class="stat-value"><?= $totalClients ?></div>
                    <div class="stat-trend">Registered clients</div>
                </div>
                <div class="stat-card">
                    <h3>VIP Clients</h3>
                    <div class="stat-value"><?= $vipClients ?></div>
                    <div class="stat-trend">High-value clients</div>
                </div>
                <div class="stat-card">
                    <h3>New This Month</h3>
                    <div class="stat-value"><?= $newClients ?></div>
                    <div class="stat-trend">Recent registrations</div>
                </div>
                <div class="stat-card">
                    <h3>Active Clients</h3>
                    <div class="stat-value"><?= $totalClients ?></div>
                    <div class="stat-trend">All clients active</div>
                </div>
            </div>

            <div class="clients-section">
                <div class="section-header">
                    <h2>Client Directory</h2>
                </div>

                <div class="search-filter">
                    <input type="text" class="search-box" placeholder="Search clients by name, email, or phone..." id="searchBox">
                    <select class="filter-select" id="typeFilter">
                        <option>All Types</option>
                        <option>VIP</option>
                        <option>Regular</option>
                        <option>New</option>
                    </select>
                    <select class="filter-select">
                        <option>Sort: Name A-Z</option>
                        <option>Sort: Recent Activity</option>
                        <option>Sort: Total Purchases</option>
                        <option>Sort: Join Date</option>
                    </select>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Address</th>
                                <th>Type</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): 
                                // Generate initials from client name
                                $initials = '';
                                $nameParts = explode(' ', $row['clientName']);
                                foreach($nameParts as $part) {
                                    if (!empty($part)) {
                                        $initials .= strtoupper(substr($part, 0, 1));
                                    }
                                }
                                $initials = substr($initials, 0, 2); // Take first 2 initials
                                
                                // Determine client type (you can modify this logic)
                                $clientType = 'regular';
                                $typeText = 'Regular';
                                if (stripos($row['clientName'], 'VIP') !== false) {
                                    $clientType = 'vip';
                                    $typeText = 'VIP';
                                } elseif (strtotime($row['registeredDate']) > strtotime('-30 days')) {
                                    $clientType = 'new';
                                    $typeText = 'New';
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="client-info">
                                        <div class="client-avatar"><?= $initials ?></div>
                                        <div class="client-details">
                                            <div class="client-name"><?= htmlspecialchars($row['clientName']) ?></div>
                                            <div class="client-email">ID: <?= $row['clientID'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['contactNumber']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['address']) ?></td>
                                <td><span class="badge <?= $clientType ?>"><?= $typeText ?></span></td>
                                <td><?= date('Y-m-d', strtotime($row['registeredDate'])) ?></td>
                                <td>
                                    <button class="action-btn" onclick="openUpdateModal(<?= $row['clientID'] ?>)">📝 Edit</button>
                                    <button class="action-btn" onclick="openDeleteModal(<?= $row['clientID'] ?>)">🗑️ Delete</button>
                                    <button class="action-btn">👁️ View</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Client Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Client</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="clients-admin.php">
                <div class="form-group">
                    <label>Client Name:</label>
                    <input type="text" name="clientName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Contact Number:</label>
                    <input type="text" name="contactNumber" class="form-input">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" class="form-input">
                </div>
                <div class="form-group">
                    <label>Address:</label>
                    <input type="text" name="address" class="form-input">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_client" class="btn btn-primary">Add Client</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Client Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Client</h2>
                <button class="close-btn" onclick="closeUpdateModal()">&times;</button>
            </div>
            <form method="POST" action="clients-admin.php">
                <div class="form-group">
                    <label>Client ID (to update):</label>
                    <input type="number" name="updateID" id="updateID" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>New Client Name:</label>
                    <input type="text" name="updateName" class="form-input">
                </div>
                <div class="form-group">
                    <label>New Contact Number:</label>
                    <input type="text" name="updateNumber" class="form-input">
                </div>
                <div class="form-group">
                    <label>New Email:</label>
                    <input type="email" name="updateEmail" class="form-input">
                </div>
                <div class="form-group">
                    <label>New Address:</label>
                    <input type="text" name="updateAddress" class="form-input">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                    <button type="submit" name="update_client" class="btn btn-primary">Update Client</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Client Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Client</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <form method="POST" action="clients-admin.php">
                <div class="form-group">
                    <label>Enter Client ID to Delete:</label>
                    <input type="number" name="deleteID" id="deleteID" class="form-input" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_client" class="btn btn-primary" style="background: #ff6b6b;">Delete Client</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openUpdateModal(clientID) {
            document.getElementById('updateID').value = clientID;
            document.getElementById('updateModal').classList.add('active');
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('active');
        }

        function openDeleteModal(clientID) {
            document.getElementById('deleteID').value = clientID;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const updateModal = document.getElementById('updateModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == updateModal) {
                closeUpdateModal();
            }
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        }

        // Search functionality
        document.getElementById('searchBox').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Type filter functionality
        document.getElementById('typeFilter').addEventListener('change', function() {
            const filter = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const badge = row.querySelector('.badge');
                if (!badge) return;
                
                const type = badge.textContent.toLowerCase();
                let show = true;
                
                if (filter === 'VIP' && !type.includes('vip')) show = false;
                if (filter === 'Regular' && !type.includes('regular')) show = false;
                if (filter === 'New' && !type.includes('new')) show = false;
                
                row.style.display = show ? '' : 'none';
            });
        });
    </script>
</body>
</html>