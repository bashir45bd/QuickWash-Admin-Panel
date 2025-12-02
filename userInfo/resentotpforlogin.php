<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate POST input
if (!isset($_POST['email'])) {
    echo "Email is required";
    exit;
}

$email = $_POST['email'];
$decryptedEmail = decryptData($email);

if (!$decryptedEmail) {
    echo "Invalid email";
    exit;
}

// Check if user exists and is not verified
$sql = "SELECT * FROM users WHERE email = '$decryptedEmail' AND is_verified = 0";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    // Generate new token
    $token = random_int(100000, 999999); // 6-digit code

    // Update token and time
    $updateSql = "UPDATE users SET verification_token = '$token', token_created_at = NOW() WHERE email = '$decryptedEmail'";
    if ($conn->query($updateSql)) {
        // Send email
        if (sendVerificationEmail($decryptedEmail, $token)) {
            echo "Verification email resent";
        } else {
            echo "Failed to send email";
        }
    } else {
        echo "Database update failed";
    }
} else {
    echo "User not found or already verified";
}

// Decrypt function
function decryptData($text) {
    $decoded = base64_decode($text);
    if ($decoded === false) return false;

    $key = 'aB3$dEfGh1JkLmNo';
    $decrypted = openssl_decrypt($decoded, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    return $decrypted !== false ? $decrypted : false;
}

function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'siuedu01.bd@gmail.com';
        $mail->Password = 'faoqzffmsyyebpsh';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('siuedu01.bd@gmail.com', 'QuickWash');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Resent Email';
        $mail->Body ="
<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Email Verification</title>
    <style>
      body {
        margin: 0;
        padding: 0;
        font-family: Arial, sans-serif;
        background-color: #f4f6f8;
      }
      .container {
        max-width: 600px;
        margin: 40px auto;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 0 12px rgba(0, 0, 0, 0.08);
        padding: 30px 25px;
        color: #333333;
      }
      .header {
        color: #ff8383;
        text-align: center;
        font-size: 24px;
        margin-bottom: 10px;
      }
      .code {
        text-align: center;
        font-size: 36px;
        color: #ff8383;
        margin: 20px 0;
        letter-spacing: 3px;
      }
      .content {
        font-size: 16px;
        line-height: 1.6;
        text-align: center;
      }
      .footer {
        margin-top: 30px;
        font-size: 13px;
        color: #888888;
        text-align: center;
      }
    </style>
  </head>
  <body>
    <div class='container'>
      <div class='header'>Hello</div>
      <div class='content'>
        <p>Thank you for registering with <strong>QuickWash</strong>.</p>
        <p>Your verification code is:</p>
      </div>
      <div class='code'>$code</div>
      <div class='content'>
        <p>Please enter this code in the app to verify your email. It is valid for <strong>2 minutes</strong>.</p>
        <p>If you did not sign up, you can safely ignore this email.</p>
      </div>
      <div class='footer'>— QuickWash Team</div>
    </div>
  </body>
</html>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>
