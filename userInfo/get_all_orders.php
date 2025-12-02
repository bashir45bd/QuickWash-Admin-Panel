<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Optional for development
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Connect to database
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

// Read raw POST body for JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (isset($input['user_id'])) {
    $user_id = $input['user_id'];

    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    echo json_encode(["success" => true, "orders" => $orders]);
} else {
    echo json_encode(["success" => false, "message" => "user_id is required"]);
}

$conn->close();
?>
