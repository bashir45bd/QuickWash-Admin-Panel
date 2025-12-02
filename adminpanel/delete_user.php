<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];

    // Get current image to delete
    $res = $conn->query("SELECT image FROM users WHERE id = $id");
    if ($res->num_rows) {
        $row = $res->fetch_assoc();
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']); // Delete image file
        }
    }

    // Delete user
    if ($conn->query("DELETE FROM users WHERE id = $id")) {
        header("Location: manageUsers.php?success=User deleted successfully");
        exit();
    } else {
        header("Location: manageUsers.php?error=Failed to delete user");
        exit();
    }
} else {
    header("Location: manageUsers.php?error=Invalid request");
    exit();
}
