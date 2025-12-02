<?php
header('Content-Type: application/json');

$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if (
    empty($data['name']) ||
    empty($data['phone']) ||
    empty($data['email']) ||
    empty($data['address']) ||
    empty($data['total_price']) ||
    empty($data['user_id']) ||
    empty($data['items'])
) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

$name = $conn->real_escape_string($data['name']);
$phone = $conn->real_escape_string($data['phone']);
$email = $conn->real_escape_string($data['email']);
$address = $conn->real_escape_string($data['address']);
$user_id = intval($data['user_id']);
$service_id = intval($data['service_id'] ?? 0);
$total_price = floatval($data['total_price']);
$status = 'Pending';
$payment_status = 'Cash On Delivery';
$promo_code = $conn->real_escape_string($data['promo_code'] ?? '');
$pickup_time = $conn->real_escape_string($data['pickup_time'] ?? '');
$delivery_time = $conn->real_escape_string($data['delivery_time'] ?? '');
$created_at = date('Y-m-d H:i:s');
$items = $data['items'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO orders 
        (user_id, name, phone, email, address, service_id, amount, noti_status, status, created_at, promo_code, pickup_time, delivery_time)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
    $stmt->bind_param(
        "issssidssssss",
        $user_id,
        $name,
        $phone,
        $email,
        $address,
        $service_id,
        $total_price,
        $status,
        $payment_status,
        $created_at,
        $promo_code,
        $pickup_time,
        $delivery_time
    );

    if (!$stmt->execute()) {
        throw new Exception("Order insert failed: " . $stmt->error);
    }

    $order_id = $stmt->insert_id;
    $stmt->close();

    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, item_price, quantity) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $item_name = $conn->real_escape_string($item['name']);
        $item_price = floatval($item['price']);
        $item_qty = intval($item['quantity']);
        $itemStmt->bind_param("isdi", $order_id, $item_name, $item_price, $item_qty);
        $itemStmt->execute();
    }
    $itemStmt->close();

    echo json_encode(["status" => "success", "order_id" => $order_id]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn->close();
?>
