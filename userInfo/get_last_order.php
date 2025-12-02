<?php
header('Content-Type: application/json');
include("db.php");

// Get POST data (JSON)
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? '';

if (empty($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user_id']);
    exit();
}

// Escape for safety
$user_id = $conn->real_escape_string($user_id);

$sql = "SELECT * FROM orders WHERE user_id='$user_id' ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $order = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'order' => $order]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No order found']);
}
?>
