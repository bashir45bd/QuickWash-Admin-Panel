<?php


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$mail = $_POST['mail'] ?? '';
$f_mail = decryptData($mail);

if (empty($f_mail)) {
    echo "Enter E-mail Address";
    exit;
}


if (empty($f_mail)) {
    echo "Email decryption failed";
    exit;
}

// Check email exists and is verified
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_verified = 1");
$stmt->bind_param("s", $f_mail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $otp = rand(100000, 999999); // 6-digit OTP
    $expireTime = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Save OTP to database
    $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expire = ? WHERE email = ?");
    $update->bind_param("sss", $otp, $expireTime, $f_mail);
    $update->execute();

    if (sendOtpEmail($f_mail, $otp)) {
        echo "OTP sent";
    } else {
        echo "OTP sending failed";
    }
} else {
    echo "Email not found or not verified";
}
$conn->close();

function sendOtpEmail($email, $otp) {
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
        $mail->Subject = 'QuickWash Password Reset OTP';
        $mail->Body ="
<!DOCTYPE html>
<html lang='en'>
  <head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <style>
      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f6f8;
      }
      .container {
        max-width: 600px;
        margin: 40px auto;
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
      }
      .header {
        background-color: #ff8383;
        padding: 20px;
        color: #ffffff;
        text-align: center;
      }
      .header h1 {
        font-size: 26px;
        margin: 0;
      }
      .content {
        padding: 30px 20px;
        text-align: center;
        font-size: 16px;
        color: #333333;
      }
      .content strong {
        font-size: 20px;
        color: #ff8383;
      }
      .button {
        display: inline-block;
        margin-top: 20px;
        background-color: #ff8383;
        color: #ffffff;
        padding: 12px 30px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 16px;
        text-decoration: none;
        transition: background 0.3s ease;
      }
      .button:hover {
        background-color: #ff6b6b;
      }
      .footer {
        padding: 20px;
        text-align: center;
        font-size: 14px;
        color: #777777;
        background-color: #fafafa;
      }
    </style>
  </head>
  <body>
    <div class='container'>
      <div class='header'>
        <h1>QuickWash Password Reset</h1>
      </div>
      <div class='content'>
        <p>Hi there,</p>
        <p>Your OTP for resetting your <strong>QuickWash</strong> account password is:</p>
        <p><strong>$otp</strong></p>
        <p>This code is valid for the next <strong>2 minutes</strong>. Please use it to reset your password.</p>
        <a href='#' class='button'>Reset Password</a>
      </div>
      <div class='footer'>
        <p>If you did not request this, please ignore this email.</p>
        <p>&copy; 2025 QuickWash. All rights reserved.</p>
      </div>
    </div>
  </body>
</html>";


        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}
?>
