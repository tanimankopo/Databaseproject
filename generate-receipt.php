<?php
session_start();

// ✅ Protect page
if (!isset($_SESSION['username']) || ($_SESSION['role'] !== "Cashier")) {
    header("Location: login.php");
    exit();
}

include "db.php";

// Include FPDF library (download from http://www.fpdf.org/ and place in a folder, e.g., 'fpdf/')
require('fpdf/fpdf.php');

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $customerName = htmlspecialchars(trim($_POST['customerName']));
    $totalAmount = floatval($_POST['totalAmount']);
    $paymentType = htmlspecialchars(trim($_POST['paymentType']));

    if (empty($customerName) || $totalAmount <= 0 || !in_array($paymentType, ['cash', 'onsite'])) {
        die("Invalid input data.");
    }

    // Insert into payments table
    $stmt = $conn->prepare("INSERT INTO payments (customerName, totalAmount, paymentType, dateCreated) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sds", $customerName, $totalAmount, $paymentType);
    if (!$stmt->execute()) {
        die("Error saving payment: " . $stmt->error);
    }
    $paymentID = $stmt->insert_id; // Get the auto-generated paymentID
    $stmt->close();

    // Generate PDF Receipt
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);

    // Header
    $pdf->Cell(0, 10, '1-GARAGE Receipt', 0, 1, 'C');
    $pdf->Ln(10);

    // Receipt Details
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 10, 'Transaction ID:', 0, 0);
    $pdf->Cell(0, 10, $paymentID, 0, 1);
    $pdf->Cell(50, 10, 'Customer Name:', 0, 0);
    $pdf->Cell(0, 10, $customerName, 0, 1);
    $pdf->Cell(50, 10, 'Total Amount:', 0, 0);
    $pdf->Cell(0, 10, '₱' . number_format($totalAmount, 2), 0, 1);
    $pdf->Cell(50, 10, 'Payment Type:', 0, 0);
    $pdf->Cell(0, 10, ucfirst($paymentType), 0, 1);
    $pdf->Cell(50, 10, 'Date:', 0, 0);
    $pdf->Cell(0, 10, date('Y-m-d H:i:s'), 0, 1);

    $pdf->Ln(20);
    $pdf->Cell(0, 10, 'Thank you for your business!', 0, 1, 'C');

    // Output PDF to browser
    $pdf->Output('I', 'receipt_' . $paymentID . '.pdf');
    exit();
} else {
    // If not POST, redirect back
    header("Location: cashier-receipts.php");
    exit();
}
?>
