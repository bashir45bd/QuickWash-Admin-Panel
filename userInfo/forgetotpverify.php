<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";
// Connect to DB
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$email = $_POST['mail'] ?? '';
$otp = $_POST['otp'] ?? '';
$newPassword = $_POST['new_pass'] ?? '';

// Decrypt email
$f_mail = decryptData($email);

// Validate
if (empty($f_mail) || empty($otp) || empty($newPassword)) {
    echo "All fields are required";
    exit;
}

// Escape input
$f_mail = $conn->real_escape_string($f_mail);
$otp = $conn->real_escape_string($otp);
$newPassword = $conn->real_escape_string($newPassword); // plain text

// Check OTP match
$sql = "SELECT * FROM users WHERE email = '$f_mail' AND reset_token = '$otp'";
$result = $conn->query($sql);

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Check expiry
    $expireTime = strtotime($row['token_expire']);
    if (time() > $expireTime) {
        echo "OTP expired";
        exit;
    }

    // Update password (as plain text)
    $update_sql = "UPDATE users SET password = '$newPassword', reset_token = NULL, token_expire = NULL WHERE email = '$f_mail'";
    if ($conn->query($update_sql)) {
        echo "success";
    } else {
        echo "Update failed";
    }

} else {
    echo "Invalid OTP or email";
}

$conn->close();

// Decrypt function
function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}
?>
