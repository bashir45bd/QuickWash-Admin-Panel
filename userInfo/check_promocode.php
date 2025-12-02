<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "laundry");

// Get input
$data = json_decode(file_get_contents("php://input"), true);
$code = isset($data['code']) ? trim($data['code']) : '';

if ($code == '') {
    echo json_encode(["status" => "fail", "message" => "No promo code provided"]);
    exit;
}

// Check in DB
$stmt = $conn->prepare("SELECT discount_percent FROM promo_codes WHERE code = ? AND status = 'active'");
$stmt->bind_param("s", $code);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "status" => "success",
        "discount_percent" => $row['discount_percent']
    ]);
} else {
    echo json_encode([
        "status" => "fail",
        "message" => "Invalid or expired promo code"
    ]);
}
?>
