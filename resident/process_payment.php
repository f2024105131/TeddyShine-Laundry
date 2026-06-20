<?php
/**
 * resident-Process Payment - Teddy Shine Laundry Management System
 * 
 * Handles payment submission and updates invoice status
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect(BASE_URL . "/resident/my_orders.php");
}

$invoice_id = intval($_POST['invoice_id']);
$amount = floatval($_POST['amount']);
$payment_method = sanitize($_POST['payment_method']);

// Validate amount
if($amount <= 0) {
    setFlashMessage("Invalid payment amount.", "error");
    redirect(BASE_URL . "/resident/invoice.php?id=$invoice_id");
}

// Verify invoice belongs to resident
$verify_query = "SELECT i.*, o.Resident_ID, o.Order_ID 
                 FROM Invoice i 
                 JOIN Orders o ON i.Order_ID = o.Order_ID 
                 WHERE i.Invoice_ID = $invoice_id AND o.Resident_ID = {$_SESSION['resident_id']}";
$verify_result = mysqli_query($conn, $verify_query);
$invoice = mysqli_fetch_assoc($verify_result);

if(!$invoice) {
    setFlashMessage("Invalid invoice.", "error");
    redirect(BASE_URL . "/resident/my_orders.php");
}

// Check if amount exceeds due
if($amount > $invoice['Final_Amount']) {
    setFlashMessage("Payment amount cannot exceed invoice total.", "error");
    redirect(BASE_URL . "/resident/invoice.php?id=$invoice_id");
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Insert payment
    $stmt = mysqli_prepare($conn, "INSERT INTO Payments (Invoice_ID, Payment_Date, Payment_Amount, Payment_Method, Payment_Status) VALUES (?, CURDATE(), ?, ?, 'Completed')");
    mysqli_stmt_bind_param($stmt, "ids", $invoice_id, $amount, $payment_method);
    mysqli_stmt_execute($stmt);
    $payment_id = mysqli_insert_id($conn);
    
    // Insert record for payment
    $stmt = mysqli_prepare($conn, "INSERT INTO Records (Payment_ID, Record_ID, Notes) VALUES (?, 1, 'Payment received for invoice #$invoice_id')");
    mysqli_stmt_bind_param($stmt, "i", $payment_id);
    mysqli_stmt_execute($stmt);
    
    mysqli_commit($conn);
    
    // Log activity
    logActivity('payment_made', "Payment of Rs. $amount made for invoice #$invoice_id via $payment_method");
    
    setFlashMessage("Payment of Rs. " . number_format($amount, 2) . " received successfully!", "success");
    redirect(BASE_URL . "/resident/invoice.php?id=$invoice_id");
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    error_log("Payment error: " . $e->getMessage());
    setFlashMessage("Payment failed. Please try again.", "error");
    redirect(BASE_URL . "/resident/invoice.php?id=$invoice_id");
}
?>