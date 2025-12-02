<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "laundry";

$conn = new mysqli($host, $username, $password, $database);

$message = "";
$success = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT id, token_created_at FROM users WHERE verification_token = ? AND is_verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $createdTime = strtotime($row['token_created_at']);
        $now = time();
        $difference = $now - $createdTime;

        if ($difference <= 60) {
            $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $update->bind_param("i", $row['id']);
            $update->execute();
            $message = "✅ Your email has been successfully verified!";
            $success = true;
        } else {
            $message = "⏳ This link has expired. <a href='http://192.168.1.104/Laundry/userInfo/resentotp.php?token=$token'>Resend verification email</a>";
        }
    } else {
        $message = "❌ Invalid or already verified token.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #6a11cb, #2575fc);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #fff;
            animation: fadeIn 1s ease-in;
        }

        .card {
            background: white;
            color: #333;
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
            max-width: 420px;
            width: 90%;
            text-align: center;
            animation: slideUp 0.8s ease-out;
        }

        .card h1 {
            font-size: 26px;
            font-weight: 600;
        }

        .icon {
            font-size: 58px;
            margin-bottom: 20px;
        }

        .message {
            font-size: 17px;
            margin-top: 15px;
            color: <?= $success ? '#27ae60' : '#c0392b' ?>;
        }

        a {
            color: #2575fc;
            text-decoration: none;
            font-weight: 500;
        }

        a:hover {
            text-decoration: underline;
        }

        .btn-home {
            margin-top: 30px;
            background: #2575fc;
            color: white;
            padding: 10px 25px;
            border-radius: 8px;
            display: inline-block;
            text-decoration: none;
            transition: background 0.3s;
        }

        .btn-home:hover {
            background: #1a5ede;
        }

        @keyframes slideUp {
            0% {
                transform: translateY(40px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
            }
            100% {
                opacity: 1;
            }
        }

        @media (max-width: 480px) {
            .card {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon"><?= $success ? '✅' : '❌' ?></div>
        <h1>Email Verification</h1>
        <div class="message"><?= $message ?></div>
       
    </div>
</body>
</html>
