<?php
include "db.php";
session_start();

$code = $_POST['code'] ?? '';
$discount = $_POST['discount_percent'] ?? 0;
$status = $_POST['status'] ?? 'Active';

if ($code && $discount) {
    $stmt = $conn->prepare("INSERT INTO promo_codes (code, discount_percent, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $code, $discount, $status);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Promo code added successfully!";
    } else {
        $_SESSION['error'] = "Failed to add promo code.";
    }
} else {
    $_SESSION['error'] = "Please fill in all fields.";
}
header("Location: promos.php");
exit();
