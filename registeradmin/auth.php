<?php
session_start();
include "db.php";

$username = $_POST['username'];
$password = $_POST['password'];

// Fetch from DB
$stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    if (password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = true;
        header("Location: dashboard.php");
        exit();
    }
}

echo "<script>alert('Invalid Credentials'); window.location='login.php';</script>";
