<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Insert Supplier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_supplier'])) {
    $supplierName  = $_POST['supplierName'];
    $contactPerson = $_POST['contactPerson'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];
    $status        = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO supplier (supplierName, contactPerson, contactNumber, email, address, status) 
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $supplierName, $contactPerson, $contactNumber, $email, $address, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: supplier-admin.php");
    exit();
}

// ✅ Delete Supplier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_supplier'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM supplier WHERE supplierID = ?");
    $stmt->bind_param("i", $deleteID);

    if ($stmt->execute()) {
        echo "<script>alert('🗑️ Supplier deleted successfully!'); window.location='supplier-admin.php';</script>";
    } else {
        echo "<script>alert('❌ Error deleting supplier.'); window.location='supplier-admin.php';</script>";
    }

    $stmt->close();
}

// ✅ Update Supplier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_supplier'])) {
    $updateID      = intval($_POST['updateID']);
    $supplierName  = $_POST['updateName'];
    $contactPerson = $_POST['updateContact'];
    $contactNumber = $_POST['updateNumber'];
    $email         = $_POST['updateEmail'];
    $address       = $_POST['updateAddress'];
    $status        = $_POST['updateStatus'];

    // Check if supplier exists
    $check = $conn->prepare("SELECT supplierID FROM supplier WHERE supplierID = ?");
    $check->bind_param("i", $updateID);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        echo "<script>alert('❌ Supplier ID does not exist!'); window.location='supplier-admin.php';</script>";
        $check->close();
        exit;
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE supplier 
                            SET supplierName=?, contactPerson=?, contactNumber=?, email=?, address=?, status=? 
                            WHERE supplierID=?");
    $stmt->bind_param("ssssssi", $supplierName, $contactPerson, $contactNumber, $email, $address, $status, $updateID);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('✅ Supplier updated successfully!'); window.location='supplier-admin.php';</script>";
    exit();
}

// ✅ Get statistics
$totalSuppliersResult = $conn->query("SELECT COUNT(*) as total FROM supplier");
$totalSuppliers = $totalSuppliersResult->fetch_assoc()['total'];

$activeSuppliersResult = $conn->query("SELECT COUNT(*) as active FROM supplier WHERE status = 'Active'");
$activeSuppliers = $activeSuppliersResult->fetch_assoc()['active'];

$inactiveSuppliersResult = $conn->query("SELECT COUNT(*) as inactive FROM supplier WHERE status = 'Inactive'");
$inactiveSuppliers = $inactiveSuppliersResult->fetch_assoc()['inactive'];

// ✅ Fetch suppliers
$result = $conn->query("SELECT * FROM supplier ORDER BY supplierID ASC");
if (!$result) {
    die("❌ Error fetching suppliers: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers Management - 1-GARAGE</title>
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

        .suppliers-section {
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

        .suppliers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .supplier-card {
            background: #2d303e;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #3b3f51;
            transition: all 0.3s;
        }

        .supplier-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }

        .supplier-header {
            display: flex;
            align-items: start;
            gap: 15px;
            margin-bottom: 15px;
        }

        .supplier-logo {
            width: 60px;
            height: 60px;
            background: #3b3f51;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .supplier-info {
            flex: 1;
        }

        .supplier-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .supplier-type {
            font-size: 12px;
            color: #b0b3ba;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge.active {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .badge.inactive {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
        }

        .supplier-details {
            margin: 15px 0;
        }

        .detail-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
            color: #b0b3ba;
        }

        .detail-row span:first-child {
            width: 20px;
        }

        .supplier-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #3b3f51;
            border-bottom: 1px solid #3b3f51;
        }

        .stat-item {
            text-align: center;
        }

        .stat-item-label {
            font-size: 11px;
            color: #b0b3ba;
            margin-bottom: 4px;
        }

        .stat-item-value {
            font-size: 18px;
            font-weight: 600;
        }

        .supplier-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            flex: 1;
            padding: 8px;
            background: #3b3f51;
            border: none;
            border-radius: 6px;
            color: #e4e7eb;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: #4a5061;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
        }

        .star {
            color: #ffa726;
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
                <h1><span>🏭</span> Suppliers Management</h1>
                <div class="header-actions">
                    <button class="btn btn-secondary">
                        <span>📥</span> Import
                    </button>
                    <button class="btn btn-secondary">
                        <span>📤</span> Export
                    </button>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <span>+</span> Add Supplier
                    </button>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Suppliers</h3>
                    <div class="stat-value"><?= $totalSuppliers ?></div>
                    <div class="stat-trend">Registered suppliers</div>
                </div>
                <div class="stat-card">
                    <h3>Active Suppliers</h3>
                    <div class="stat-value"><?= $activeSuppliers ?></div>
                    <div class="stat-trend">Currently active</div>
                </div>
                <div class="stat-card">
                    <h3>Inactive Suppliers</h3>
                    <div class="stat-value"><?= $inactiveSuppliers ?></div>
                    <div class="stat-trend">Not active</div>
                </div>
                <div class="stat-card">
                    <h3>Success Rate</h3>
                    <div class="stat-value"><?= $totalSuppliers > 0 ? round(($activeSuppliers / $totalSuppliers) * 100) : 0 ?>%</div>
                    <div class="stat-trend">Active rate</div>
                </div>
            </div>

            <div class="suppliers-section">
                <div class="section-header">
                    <h2>Supplier Directory</h2>
                </div>

                <div class="search-filter">
                    <input type="text" class="search-box" placeholder="Search suppliers by name, email, or contact..." id="searchBox">
                    <select class="filter-select" id="statusFilter">
                        <option>All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                    <select class="filter-select">
                        <option>Sort: Name A-Z</option>
                        <option>Sort: Status</option>
                        <option>Sort: ID</option>
                    </select>
                </div>

                <div class="suppliers-grid">
                    <?php while($row = $result->fetch_assoc()): 
                        // Generate supplier logo based on name
                        $logo = '🏭';
                        $name = strtolower($row['supplierName']);
                        if (strpos($name, 'oil') !== false || strpos($name, 'fluid') !== false) $logo = '🛢️';
                        elseif (strpos($name, 'electrical') !== false || strpos($name, 'battery') !== false) $logo = '🔋';
                        elseif (strpos($name, 'filter') !== false || strpos($name, 'air') !== false) $logo = '🌬️';
                        elseif (strpos($name, 'engine') !== false || strpos($name, 'motor') !== false) $logo = '⚡';
                        elseif (strpos($name, 'brake') !== false || strpos($name, 'suspension') !== false) $logo = '🔩';
                        else $logo = '🔧';
                        
                        // Determine supplier type
                        $supplierType = 'General Auto Parts';
                        if (strpos($name, 'oil') !== false || strpos($name, 'fluid') !== false) $supplierType = 'Lubricants & Fluids';
                        elseif (strpos($name, 'electrical') !== false || strpos($name, 'battery') !== false) $supplierType = 'Electrical Systems';
                        elseif (strpos($name, 'filter') !== false || strpos($name, 'air') !== false) $supplierType = 'Filters & Air Systems';
                        elseif (strpos($name, 'engine') !== false || strpos($name, 'motor') !== false) $supplierType = 'Engine Components';
                        elseif (strpos($name, 'brake') !== false || strpos($name, 'suspension') !== false) $supplierType = 'Suspension & Drivetrain';
                        
                        // Generate random stats for demo
                        $products = rand(15, 50);
                        $orders = rand(5, 40);
                        $leadTime = rand(2, 10);
                        $rating = round(rand(35, 50) / 10, 1);
                    ?>
                    <div class="supplier-card">
                        <div class="supplier-header">
                            <div class="supplier-logo"><?= $logo ?></div>
                            <div class="supplier-info">
                                <div class="supplier-name"><?= htmlspecialchars($row['supplierName']) ?></div>
                                <div class="supplier-type"><?= $supplierType ?></div>
                            </div>
                            <span class="badge <?= strtolower($row['status']) ?>"><?= $row['status'] ?></span>
                        </div>

                        <div class="supplier-details">
                            <div class="detail-row">
                                <span>📧</span>
                                <span><?= htmlspecialchars($row['email']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span>📞</span>
                                <span><?= htmlspecialchars($row['contactNumber']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span>👤</span>
                                <span><?= htmlspecialchars($row['contactPerson']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span>📍</span>
                                <span><?= htmlspecialchars($row['address']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span>⭐</span>
                                <div class="rating">
                                    <span><?= $rating ?></span>
                                    <span class="star"><?= str_repeat('★', floor($rating)) ?><?= str_repeat('☆', 5 - floor($rating)) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="supplier-stats">
                            <div class="stat-item">
                                <div class="stat-item-label">Products</div>
                                <div class="stat-item-value"><?= $products ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-item-label">Orders</div>
                                <div class="stat-item-value"><?= $orders ?></div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-item-label">Lead Time</div>
                                <div class="stat-item-value"><?= $leadTime ?>d</div>
                            </div>
                        </div>

                        <div class="supplier-actions">
                            <button class="action-btn" onclick="openUpdateModal(<?= $row['supplierID'] ?>)">📝 Edit</button>
                            <button class="action-btn" onclick="openDeleteModal(<?= $row['supplierID'] ?>)">🗑️ Delete</button>
                            <button class="action-btn">👁️ View</button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal" id="addModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Supplier</h2>
                <button class="close-btn" onclick="closeAddModal()">&times;</button>
            </div>
            <form method="POST" action="supplier-admin.php">
                <div class="form-group">
                    <label>Supplier Name:</label>
                    <input type="text" name="supplierName" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>Contact Person:</label>
                    <input type="text" name="contactPerson" class="form-input">
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
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" class="form-input">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_supplier" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Supplier Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Supplier</h2>
                <button class="close-btn" onclick="closeUpdateModal()">&times;</button>
            </div>
            <form method="POST" action="supplier-admin.php">
                <div class="form-group">
                    <label>Supplier ID (to update):</label>
                    <input type="number" name="updateID" id="updateID" class="form-input" required>
                </div>
                <div class="form-group">
                    <label>New Supplier Name:</label>
                    <input type="text" name="updateName" class="form-input">
                </div>
                <div class="form-group">
                    <label>New Contact Person:</label>
                    <input type="text" name="updateContact" class="form-input">
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
                <div class="form-group">
                    <label>New Status:</label>
                    <select name="updateStatus" class="form-input">
                        <option value="">-- Select Status --</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeUpdateModal()">Cancel</button>
                    <button type="submit" name="update_supplier" class="btn btn-primary">Update Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Supplier Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Delete Supplier</h2>
                <button class="close-btn" onclick="closeDeleteModal()">&times;</button>
            </div>
            <form method="POST" action="supplier-admin.php">
                <div class="form-group">
                    <label>Enter Supplier ID to Delete:</label>
                    <input type="number" name="deleteID" id="deleteID" class="form-input" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                    <button type="submit" name="delete_supplier" class="btn btn-primary" style="background: #ff6b6b;">Delete Supplier</button>
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

        function openUpdateModal(supplierID) {
            document.getElementById('updateID').value = supplierID;
            document.getElementById('updateModal').classList.add('active');
        }

        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('active');
        }

        function openDeleteModal(supplierID) {
            document.getElementById('deleteID').value = supplierID;
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
            const cards = document.querySelectorAll('.supplier-card');
            
            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Status filter functionality
        document.getElementById('statusFilter').addEventListener('change', function() {
            const filter = this.value;
            const cards = document.querySelectorAll('.supplier-card');
            
            cards.forEach(card => {
                const badge = card.querySelector('.badge');
                if (!badge) return;
                
                const status = badge.textContent.toLowerCase();
                let show = true;
                
                if (filter === 'Active' && !status.includes('active')) show = false;
                if (filter === 'Inactive' && !status.includes('inactive')) show = false;
                
                card.style.display = show ? '' : 'none';
            });
        });
    </script>
</body>
</html>