<?php
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

// Read JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['key'], $data['catId'], $data['serId'])) {
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

// Decrypt the key and verify it
$f_key = decryptData($data['key']);
if ($f_key !== '2021') {
    echo json_encode(["error" => "Invalid authentication key"]);
    exit;
}

// Connect to database
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    error_log("DB Connection Error: " . $conn->connect_error);
    echo json_encode(["error" => "Internal server error"]);
    exit;
}

$catId = $data['catId'];
$serId = $data['serId'];

// Prepare and bind SQL query
$stmt = $conn->prepare("SELECT * FROM items WHERE category_id = ? AND service_id = ?");
$stmt->bind_param("ii", $catId, $serId);
$stmt->execute();
$result = $stmt->get_result();

// Prepare response
$wash = [];
$base_url = "http://192.168.0.109/Laundry/adminpanel/uploads/items/";
while ($row = $result->fetch_assoc()) {
    $wash[] = [
        "id"       => $row['id'],
        "name"     => $row['name'],
        "subtitle" => $row['subtitle'] ?? '',
        "price"    => $row['price'],
        "image"    => $row['image'] ? $base_url . $row['image'] : ""
    ];
}

// Output response
echo json_encode(["wash" => $wash]);

// Close connections
if ($stmt) $stmt->close();
$conn->close();

// Function to decrypt data
function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}
?>
