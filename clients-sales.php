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

    $stmt = $conn->prepare("INSERT INTO clientinfo (clientName, contactNumber, email, address, registeredDate)
                            VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $clientName, $contactNumber, $email, $address);
    $stmt->execute();
    $stmt->close();

    header("Location: clients.php");
    exit();
}

// ✅ Delete Client (AJAX)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_client'])) {
    $deleteID = intval($_POST['deleteID']);
    $stmt = $conn->prepare("DELETE FROM clientinfo WHERE clientID = ?");
    $stmt->bind_param("i", $deleteID);

    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    $stmt->close();
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

    echo "<script>alert('✅ Client updated successfully!'); window.location='clients.php';</script>";
    exit();
}

// ✅ Fetch clients
$result = $conn->query("SELECT * FROM clientinfo ORDER BY clientID ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
    <link rel="stylesheet" href="css/client.css">
</head>
<body>

<?php include("sidebar-sales.php") ?>

<div class="main-content">
    <header class="topbar">
        <h1>👥 Clients</h1>
        <div class="settings-menu">
            <button class="settings-btn">&#9776;</button>
            <div class="settings-dropdown">
                <button class="add-btn" onclick="document.getElementById('modal').style.display='flex'">+ Add Client</button>
            </div>
        </div>
    </header>

    <!-- Clients Table -->
    <table class="supplier-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Client Name</th>
                <th>Contact Number</th>
                <th>Email</th>
                <th>Address</th>
                <th>Registered Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['clientID']; ?></td>
                <td><?= htmlspecialchars($row['clientName']); ?></td>
                <td><?= htmlspecialchars($row['contactNumber']); ?></td>
                <td><?= htmlspecialchars($row['email']); ?></td>
                <td><?= htmlspecialchars($row['address']); ?></td>
                <td><?= $row['registeredDate']; ?></td>
                <td>
                    <div class="action">
                        <button class="update-btn"
                            onclick="openUpdateModal(
                                '<?= $row['clientID']; ?>',
                                '<?= htmlspecialchars($row['clientName']); ?>',
                                '<?= htmlspecialchars($row['contactNumber']); ?>',
                                '<?= htmlspecialchars($row['email']); ?>',
                                '<?= htmlspecialchars($row['address']); ?>'
                            )">Update</button>

                        <button class="delete-btn" onclick="confirmDelete(<?= $row['clientID']; ?>, event)">Delete</button>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- Add Client Modal -->
<div class="modal" id="modal">
    <div class="modal-content">
        <h3>Add New Client</h3>
        <form method="POST" action="clients.php">
            <label>Client Name:</label>
            <input type="text" name="clientName" required><br>

            <label>Contact Number:</label>
            <input type="text" name="contactNumber"><br>

            <label>Email:</label>
            <input type="email" name="email"><br>

            <label>Address:</label>
            <input type="text" name="address"><br>

            <button type="submit" name="add_client">Save</button>
            <button type="button" onclick="document.getElementById('modal').style.display='none'">Cancel</button>
        </form>
    </div>
</div>

<!-- Update Client Modal -->
<div class="modal" id="updateModal">
    <div class="modal-content">
        <h3>Update Client</h3>
        <form method="POST" action="clients.php">
            <input type="hidden" name="updateID" id="updateID">

            <label>Client Name:</label>
            <input type="text" name="updateName" id="updateName" required><br>

            <label>Contact Number:</label>
            <input type="text" name="updateNumber" id="updateNumber"><br>

            <label>Email:</label>
            <input type="email" name="updateEmail" id="updateEmail"><br>

            <label>Address:</label>
            <input type="text" name="updateAddress" id="updateAddress"><br>

            <div style="margin-top:10px;">
                <button type="submit" name="update_client">Update</button>
                <button type="button" onclick="document.getElementById('updateModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
// ✅ Toggle settings dropdown
document.querySelector(".settings-btn").addEventListener("click", function() {
    document.querySelector(".settings-menu").classList.toggle("show");
});

// ✅ Confirm delete
function confirmDelete(clientID, event) {
    if (!confirm("Are you sure you want to delete this client?")) return;

    fetch('clients.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            delete_client: '1',
            deleteID: clientID
        })
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "success") {
            alert("🗑️ Client deleted successfully!");
            const row = event.target.closest('tr');
            row.style.opacity = '0';
            setTimeout(() => row.remove(), 300);
        } else {
            alert("❌ Error deleting client.");
        }
    })
    .catch(error => {
        alert("⚠️ Error connecting to server.");
        console.error(error);
    });
}

// ✅ Open Update Modal
function openUpdateModal(id, name, number, email, address) {
    document.getElementById('updateModal').style.display = 'flex';
    document.getElementById('updateID').value = id;
    document.getElementById('updateName').value = name;
    document.getElementById('updateNumber').value = number;
    document.getElementById('updateEmail').value = email;
    document.getElementById('updateAddress').value = address;
}
</script>

</body>
</html>
