<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check required POST data
if (!isset($_POST['email'], $_POST['code'])) {
    echo "Missing required data";
    exit;
}

$email = $_POST['email'];
$code = $_POST['code'];

// Decrypt the email
$f_mail = decryptData($email);
if ($f_mail === false) {
    echo "Invalid email decryption";
    exit;
}

// Escape input
$f_mail_escaped = $conn->real_escape_string($f_mail);
$code_escaped = $conn->real_escape_string($code);

// Fetch the user and token creation time
$sql = "SELECT verification_token, token_created_at, is_verified FROM users WHERE email = '$f_mail_escaped'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Check if already verified
    if ($user['is_verified'] == 1) {
        echo "Already verified";
        exit;
    }

    // Check if token matches
    if ($user['verification_token'] !== $code_escaped) {
        echo "Invalid code";
        exit;
    }

    // Check if token is within 2 minutes
    $tokenTime = new DateTime($user['token_created_at']);
    $now = new DateTime();
    $interval = $now->getTimestamp() - $tokenTime->getTimestamp();

    if ($interval >120) {
        echo "Token expired";
        exit;
    }

    // Update verification
    $update_sql = "UPDATE users SET is_verified = 1 WHERE email = '$f_mail_escaped'";
    if ($conn->query($update_sql)) {
        echo "Verified";
    } else {
        echo "Verification failed";
    }
} else {
    echo "Invalid email or token";
}

// Function to decrypt the email
function decryptData($text) {
    $decoded = base64_decode($text);
    if ($decoded === false) return false;

    $key = 'aB3$dEfGh1JkLmNo';
    $decrypted = openssl_decrypt($decoded, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

    return $decrypted !== false ? $decrypted : false;
}
?>
