<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Check if ID is provided
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Prepare statement for security
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Redirect with success message
        header("Location: orders.php?deleted=1");
        exit();
    } else {
        // Redirect with error message
        header("Location: orders.php?error=Failed to delete order");
        exit();
    }
} else {
    // Invalid request
    header("Location: orders.php?error=Invalid request");
    exit();
}
?>
