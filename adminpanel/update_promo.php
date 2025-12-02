<?php
include "db.php";
session_start();

$id = $_POST['id'];
$code = $_POST['code'];
$discount = $_POST['discount_percent'];
$status = $_POST['status']; // "active" or "inactive"

$stmt = $conn->prepare("UPDATE promo_codes SET code = ?, discount_percent = ?, status = ? WHERE id = ?");
$stmt->bind_param("sdss", $code, $discount, $status, $id); // Fixed: use 's' for string status
if ($stmt->execute()) {
    $_SESSION['success'] = "Promo code updated.";
} else {
    $_SESSION['error'] = "Failed to update promo code.";
}
header("Location: promos.php");
exit();
