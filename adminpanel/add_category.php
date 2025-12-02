<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $imagePath = '';

    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "image-store/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $imageName = uniqid() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $imageName;

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $validTypes = ["jpg", "jpeg", "png", "gif", "webp"];

        if (in_array($imageFileType, $validTypes)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                // Save relative path to DB (you can customize this if needed)
                $imagePath = $targetFile;
            } else {
                $_SESSION['error'] = "Failed to upload image.";
                header("Location: categories.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid image type. Allowed: jpg, jpeg, png, gif, webp.";
            header("Location: categories.php");
            exit();
        }
    }

    // Insert category into database
    $stmt = $conn->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $imagePath);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Category added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add category.";
    }

    header("Location: categories.php");
    exit();
} else {
    header("Location: categories.php");
    exit();
}
