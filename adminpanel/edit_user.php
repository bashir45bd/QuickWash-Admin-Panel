<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $oldImage = $_POST['old_image']; // existing image path

    $imagePath = $oldImage; // default is old image

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $newImageName = "uploads/" . time() . "." . $ext;

        if (!is_dir('uploads')) { // create folder if not exists
            mkdir('uploads', 0777, true);
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $newImageName)) {
            // Delete old image if exists
            if (!empty($oldImage) && file_exists($oldImage)) {
                unlink($oldImage);
            }
            $imagePath = $newImageName;
        }
    }

    // Update user record
    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, image=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $imagePath, $id);

    if ($stmt->execute()) {
        header("Location: manageUsers.php?success=User updated successfully");
        exit();
    } else {
        header("Location: manageUsers.php?error=Failed to update user");
        exit();
    }
} else {
    header("Location: manageUsers.php?error=Invalid request");
    exit();
}
?>
