<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

// ✅ Add Supplier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_supplier'])) {
    $supplierName  = $_POST['supplierName'];
    $contactPerson = $_POST['contactPerson'];
    $contactNumber = $_POST['contactNumber'];
    $email         = $_POST['email'];
    $address       = $_POST['address'];
    $status        = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO supplier (supplierName, contactPerson, contactNumber, email, address, status, dateAdded)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
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
        echo "success";
    } else {
        echo "error";
    }

    $stmt->close();
    exit();
}

// ✅ Update Supplier
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_supplier'])) {
    $updateID      = intval($_POST['updateID']);
    $supplierName  = $_POST['updateSupplierName'];
    $contactPerson = $_POST['updateContactPerson'];
    $contactNumber = $_POST['updateContact'];
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

    if ($stmt->execute()) {
        echo "<script>alert('✅ Supplier updated successfully!'); window.location='supplier.php';</script>";
    } else {
        echo "<script>alert('❌ Error updating supplier.'); window.location='supplier.php';</script>";
    }

    $stmt->close();
    exit();
}

// ✅ Fetch Suppliers
$result = $conn->query("SELECT * FROM supplier ORDER BY supplierID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Suppliers</title>
    <link rel="stylesheet" href="css/supplier.css">
</head>

<script>
// ✅ Delete Supplier Function
function confirmDelete(supplierID, event) {
    if (!confirm("Are you sure you want to delete this supplier?")) return;

    fetch('supplier.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            delete_supplier: '1',
            deleteID: supplierID
        })
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "success") {
            alert("🗑️ Supplier deleted successfully!");
            const row = event.target.closest('tr');
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            alert("❌ Error deleting supplier.");
            console.log("Server response:", data);
        }
    })
    .catch(error => {
        alert("⚠️ Error connecting to server.");
        console.error(error);
    });
}
</script>

<body>
<?php include("sidebar.php") ?>

<!-- ✅ Main Content -->
<div class="main-content">
    <header class="topbar">
        <h1>🏭 Suppliers</h1>
        <div class="settings-menu">
            <button class="settings-btn">&#9776;</button>
            <div class="settings-dropdown">
                <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add Supplier</button>
            </div>
        </div>
    </header>

    <!-- ✅ Supplier Table -->
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
                <th>Actions</th>
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
                <td><?= htmlspecialchars($row['status']); ?></td>
               
                <td>
                    <div class="action">
                        <button class="update-btn"
                            onclick="openUpdateModal(
                                '<?= $row['supplierID']; ?>',
                                '<?= htmlspecialchars($row['supplierName']); ?>',
                                '<?= htmlspecialchars($row['contactPerson']); ?>',
                                '<?= htmlspecialchars($row['contactNumber']); ?>',
                                '<?= htmlspecialchars($row['email']); ?>',
                                '<?= htmlspecialchars($row['address']); ?>',
                                '<?= htmlspecialchars($row['status']); ?>'
                            )">Update</button>

                        <button class="delete-btn" onclick="confirmDelete(<?= $row['supplierID']; ?>, event)">Delete</button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ✅ Add Supplier Modal -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3>Add New Supplier</h3>
        <form method="POST" action="supplier.php">
            <label>Supplier Name:</label>
            <input type="text" name="supplierName" required><br>

            <label>Contact Person:</label>
            <input type="text" name="contactPerson" required><br>

            <label>Contact Number:</label>
            <input type="text" name="contactNumber" required><br>

            <label>Email:</label>
            <input type="email" name="email" required><br>

            <label>Address:</label>
            <input type="text" name="address" required><br>

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

<!-- ✅ Update Supplier Modal -->
<div class="modal" id="updateModal">
    <div class="modal-content">
        <h3>Update Supplier</h3>
        <form method="POST" action="supplier.php">
            <input type="hidden" name="updateID" id="updateID">

            <label>Supplier Name:</label>
            <input type="text" name="updateSupplierName" id="updateSupplierName" required><br>

            <label>Contact Person:</label>
            <input type="text" name="updateContactPerson" id="updateContactPerson" required><br>

            <label>Contact Number:</label>
            <input type="text" name="updateContact" id="updateContact" required><br>

            <label>Email:</label>
            <input type="email" name="updateEmail" id="updateEmail" required><br>

            <label>Address:</label>
            <input type="text" name="updateAddress" id="updateAddress" required><br>

            <label>Status:</label>
            <select name="updateStatus" id="updateStatus">
                <option value="Active">Active</option>
                <option value="Inactive">Inactive</option>
            </select><br>

            <div style="margin-top:10px;">
                <button type="submit" name="update_supplier">Update</button>
                <button type="button" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// ✅ Toggle Settings
document.querySelector(".settings-btn").addEventListener("click", function() {
    document.querySelector(".settings-menu").classList.toggle("show");
});

// ✅ Open Update Modal with Values
function openUpdateModal(id, name, person, number, email, address, status) {
    document.getElementById('updateModal').style.display = 'flex';
    document.getElementById('updateID').value = id;
    document.getElementById('updateSupplierName').value = name;
    document.getElementById('updateContactPerson').value = person;
    document.getElementById('updateContact').value = number;
    document.getElementById('updateEmail').value = email;
    document.getElementById('updateAddress').value = address;
    document.getElementById('updateStatus').value = status;
}
</script>

</body>
</html>
