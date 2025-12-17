<?php

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$mail = $data['mail'];
$key = $data['key'];

$f_key = decryptData($key);

if ($f_key == '2021' && strlen($mail) > 0) {

    $temp = array();    
    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
    }

    // Fetch user info
    $sql = "SELECT * FROM users WHERE email LIKE '$mail'";
    $output = mysqli_query($conn, $sql);
    
    if ($output && mysqli_num_rows($output) > 0) {
        while ($row = mysqli_fetch_assoc($output)) {
            $temp['id'] = $row['id'];
            $temp['name'] = $row['name'];
            $temp['email'] = $row['email'];
            $temp['phone'] = $row['phone'];       // ✅ Added phone
            $temp['address'] = $row['address'];   // ✅ Added address
            $temp['image'] = 'http://192.168.0.109/Laundry/adminpanel/' . $row['image'];
        }
        echo json_encode($temp);
    } else {
        echo json_encode(["error" => "No data found"]);
    }

    $conn->close();
}

// ======================= Helper Function =====================================

function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}

?>
