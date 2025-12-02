<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// Database config
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Receive POST data
$name = $_POST['name'] ?? '';
$mail = $_POST['mail'] ?? '';
$pass = $_POST['pass'] ?? '';
$key = $_POST['key'] ?? '';

// Decrypt values
$f_key = decryptData($key);
$f_mail = decryptData($mail);

// Validate key
if ($f_key !== '2021') {
    echo "Invalid key";
    exit;
}

// Validate email format
if (!filter_var($f_mail, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format!";
    exit;
}

// Escape
$s_mail = $conn->real_escape_string($f_mail);
$s_name = $conn->real_escape_string($name);
$s_pass = $conn->real_escape_string($pass);

// Check if email exists
$emailCheck = "SELECT * FROM users WHERE email = '$s_mail'";
$checkResult = $conn->query($emailCheck);

if ($checkResult && $checkResult->num_rows > 0) {
    echo "Created";
  
} else {
		// Generate verification code and timestamp
$verificationCode = rand(100000, 999999);
$createdAt = date('Y-m-d H:i:s');

		
// Insert into database (without bind_param)
$insertSQL = "INSERT INTO users (name, email, password, verification_token, is_verified, token_created_at) 
              VALUES ('$s_name', '$s_mail', '$s_pass', '$verificationCode', 0, '$createdAt')";

if ($conn->query($insertSQL) === TRUE) {
    if (sendVerificationEmail($s_mail, $verificationCode, $s_name)) {
        echo "Verification";
    } else {
        echo "Email sending failed";
    }
} else {
    echo "Database insert failed";
}
}


$conn->close();

// ------------------ Functions ------------------

function sendVerificationEmail($email, $code, $name) {
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
        $mail->Subject = 'Verify Your Email';
        $mail->Body = "
<html>
  <head>
    <meta charset='UTF-8'>
  </head>
  <body style='font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px;'>
    <div style='max-width: 600px; margin: auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);'>
      <h2 style='color: #ff8383; margin-bottom: 10px;'>Hello, " . htmlspecialchars($name) . "</h2>
      <p style='font-size: 16px; color: #333;'>Thank you for registering with <strong>QuickWash</strong>.</p>
      <p style='font-size: 16px; color: #333;'>Your verification code is:</p>
      <h1 style='color: #ff8383; font-size: 36px; letter-spacing: 2px;'>" . htmlspecialchars($code) . "</h1>
      <p style='font-size: 16px; color: #333;'>Please enter this code in the app to verify your email. This code is valid for <strong>2 minutes</strong>.</p>
      <p style='font-size: 14px; color: #666;'>If you did not sign up for a QuickWash account, you can safely ignore this email.</p>
      <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
      <p style='font-size: 13px; color: #999; text-align: center;'>— The QuickWash Team</p>
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

function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}
?>
