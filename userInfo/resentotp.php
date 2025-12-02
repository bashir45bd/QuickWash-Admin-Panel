<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$conn = new mysqli($host, $username, $password, $database);

$message = "";
$success = false;



if (isset($_GET['token'])) {
    $oldToken = $_GET['token'];

    $stmt = $conn->prepare("SELECT id, email FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $oldToken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
      // Generate verification code and timestamp
         $verificationCode = rand(100000, 999999);
         $createdAt = date('Y-m-d H:i:s');
		 
        $update = $conn->prepare("UPDATE users SET verification_token = ?, token_created_at = ? WHERE id = ?");
        $update->bind_param("ssi", $newToken, $createdAt, $row['id']);
        $update->execute();

        sendVerificationEmail($row['email'], $newToken);
        $message = "✅ A new verification email has been sent!";
        $success = true;
    } else {
        $message = "❌ Token is invalid or the email has already been verified.";
    }
}

$conn->close();

function sendVerificationEmail($email, $token) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'siuedu01.bd@gmail.com';
        $mail->Password = 'faoqzffmsyyebpsh'; // Use App Password for Gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('siuedu01.bd@gmail.com', 'QuickWash');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Resend: Verify your email';
        $verificationLink = "http://192.168.1.104/Laundry/userInfo/verifyotp.php?token=$token";
        $mail->Body = '
        <html>
        <head>
          <meta charset="UTF-8">
          <style>
            body {
              font-family: "Segoe UI", sans-serif;
              margin: 0;
              padding: 0;
              background-color: #f4f6f8;
            }
            .container {
              max-width: 600px;
              margin: 40px auto;
              background-color: #ffffff;
              border-radius: 10px;
              box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
              overflow: hidden;
              border: 1px solid #e1e4e8;
            }
            .header {
              background-color: #009fb5;
              padding: 30px 20px;
              text-align: center;
              color: #ffffff;
            }
            .header h1 {
              margin: 0;
              font-size: 24px;
            }
            .content {
              padding: 30px 25px;
              text-align: center;
              color: #333333;
            }
            .content p {
              font-size: 16px;
              margin: 10px 0;
            }
            .button {
              display: inline-block;
              margin-top: 25px;
              background-color: #009fb5;
              color: white;
              padding: 14px 30px;
              text-decoration: none;
              border-radius: 6px;
              font-size: 16px;
              font-weight: bold;
              transition: background-color 0.3s ease;
            }
            .button:hover {
              background-color: #007a8d;
            }
            .footer {
              padding: 20px;
              text-align: center;
              font-size: 13px;
              color: #777777;
              background-color: #f9f9f9;
            }
          </style>
        </head>
        <body>
          <div class="container">
            <div class="header">
              <h1>SIU Email Verification</h1>
            </div>
            <div class="content">
              <p>Hi there,</p>
              <p>Thank you for registering with <strong>QuickWash</strong>.</p>
              <p>Please verify your email address to complete your registration.</p>
              <a class="button" href="' . $verificationLink . '" target="_blank">Verify</a>
              <p style="margin-top: 30px; font-size: 13px; color: #888;">If you did not request this, you can safely ignore this email.</p>
            </div>
            <div class="footer">
              &copy; ' . date("Y") . 'QuickWash. All rights reserved.
            </div>
          </div>
        </body>
        </html>';
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification Resend</title>
    <style>
        body {
            background: linear-gradient(to right, #00c6ff, #0072ff);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .box {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            width: 90%;
            text-align: center;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .message {
            font-size: 18px;
            color: <?= $success ? '#27ae60' : '#c0392b' ?>;
        }

        .btn {
            margin-top: 20px;
            display: inline-block;
            background: #0072ff;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .btn:hover {
            background: #005ce6;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon"><?= $success ? '✅' : '❌' ?></div>
        <h2>Email Verification</h2>
        <div class="message"><?= $message ?></div>
    </div>
</body>
</html>
