<?php
// Set response header
header('Content-Type: application/json');

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "Laundry";

// Read JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate input
if (!isset($data['key'])) {
    echo json_encode(["error" => "Missing authentication key"]);
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

// Fetch categories
$result = $conn->query("SELECT * FROM categories");

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = [
        "id" => $row['id'],
        "name" => $row['name'],
		"image" => "http://192.168.0.106/Laundry/adminpanel/" . $row['image']
    ];
}

echo json_encode(["categories" => $categories], JSON_PRETTY_PRINT);
$conn->close();


// Function to decrypt data
function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}
?>
