<?php

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

// Get encrypted POST data
$mail = $_POST['mail'] ?? '';
$pass = $_POST['pass'] ?? '';
$key = $_POST['key'] ?? '';

// Decrypt
$f_key = decryptData($key);
$f_mail = decryptData($mail);

if ($f_key == '2021') {

    // Connect to database
    $conn = new mysqli($host, $username, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Escape input
    $f_mail = $conn->real_escape_string($f_mail);
    $pass = $conn->real_escape_string($pass);

    // Check credentials
    $query = "SELECT * FROM users WHERE email = '$f_mail' AND password = '$pass' AND is_verified = 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo "login|" . $row['id']; // ✅ return login and user_id
    } else {
        // Check if user exists but not verified
        $checkVerify = "SELECT * FROM users WHERE email = '$f_mail' AND password = '$pass' AND is_verified = 0";
        $verifyResult = mysqli_query($conn, $checkVerify);

        if ($verifyResult && mysqli_num_rows($verifyResult) > 0) {
            echo "Please verify your email first!";
        } else {
            echo "Invalid email or password";
        }
    }

    $conn->close();

} else {
    echo "Login Failed: Invalid key!";
}

// Encryption functions
function encryptData($text) {
    $encrypted = openssl_encrypt($text, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
    return base64_encode($encrypted);
}

function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}
?>
