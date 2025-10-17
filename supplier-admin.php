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

    header("Location: supplier.php");
    exit();
}

// ✅ Delete Supplier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_supplier'])) {
    $deleteID = intval($_POST['deleteID']);

    $stmt = $conn->prepare("DELETE FROM supplier WHERE supplierID = ?");
    $stmt->bind_param("i", $deleteID);

    if ($stmt->execute()) {
        echo "<script>alert('🗑️ Supplier deleted successfully!'); window.location='supplier.php';</script>";
    } else {
        echo "<script>alert('❌ Error deleting supplier.'); window.location='supplier.php';</script>";
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
        echo "<script>alert('❌ Supplier ID does not exist!'); window.location='supplier.php';</script>";
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

    echo "<script>alert('✅ Supplier updated successfully!'); window.location='supplier.php';</script>";
    exit();
}

// ✅ Fetch suppliers (oldest first)
$result = $conn->query("SELECT * FROM supplier ORDER BY supplierID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers</title>
    <link rel="stylesheet" href="supplier.css">
</head>
<body>

    <?php
            include("sidebar-admin.php")
    ?>

    <!-- Main Content -->
    <div class="main-content">
        <header class="topbar">
            <h1>🏭 Suppliers</h1>
            <div class="settings-menu">
                <button class="settings-btn">&#9776;</button>
                <div class="settings-dropdown">
                    <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add Supplier</button>
                    <button class="delete-btn" onclick="document.getElementById('deleteModal').style.display='flex'">🗑 Delete Supplier</button>
                    <button class="update-btn" onclick="document.getElementById('updateModal').style.display='flex'">✏ Update Supplier</button>
                </div>
            </div>
        </header>

        <!-- Supplier Table -->
        <table class="supplier-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Contact Number</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['supplierID']; ?></td>
                    <td><?= htmlspecialchars($row['supplierName']); ?></td>
                    <td><?= htmlspecialchars($row['contactPerson']); ?></td>
                    <td><?= htmlspecialchars($row['contactNumber']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['address']); ?></td>
                    <td><?= $row['status']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Supplier Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h3>Add New Supplier</h3>
            <form method="POST" action="supplier.php">
                <label>Supplier Name:</label>
                <input type="text" name="supplierName" required><br>

                <label>Contact Person:</label>
                <input type="text" name="contactPerson"><br>

                <label>Contact Number:</label>
                <input type="text" name="contactNumber"><br>

                <label>Email:</label>
                <input type="email" name="email"><br>

                <label>Address:</label>
                <input type="text" name="address"><br>

                <label>Status:</label>
                <select name="status">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select><br>

                <button type="submit" name="add_supplier">Save</button>
                <button type="button" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Supplier Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <h3>Delete Supplier</h3>
            <form method="POST" action="supplier.php">
                <label>Enter Supplier ID to Delete:</label>
                <input type="number" name="deleteID" required><br>
                <button type="submit" name="delete_supplier">Delete</button>
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Update Supplier Modal -->
    <div class="modal" id="updateModal">
        <div class="modal-content">
            <h3>Update Supplier</h3>
            <form method="POST" action="supplier.php">
                <label>Supplier ID (to update):</label>
                <input type="number" name="updateID" required><br>

                <label>New Supplier Name:</label>
                <input type="text" name="updateName"><br>

                <label>New Contact Person:</label>
                <input type="text" name="updateContact"><br>

                <label>New Contact Number:</label>
                <input type="text" name="updateNumber"><br>

                <label>New Email:</label>
                <input type="email" name="updateEmail"><br>

                <label>New Address:</label>
                <input type="text" name="updateAddress"><br>

                <label>New Status:</label>
                <select name="updateStatus">
                    <option value="">-- Select Status --</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select><br>

                <button type="submit" name="update_supplier">Update</button>
                <button type="button" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle settings dropdown
        document.querySelector(".settings-btn").addEventListener("click", function() {
            document.querySelector(".settings-menu").classList.toggle("show");
        });
    </script>

</body>
</html>
