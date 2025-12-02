<?php
session_start();
include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST["id"];
    $name = trim($_POST["name"]);
    $imagePath = '';

    // Fetch existing image path
    $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($currentImagePath);
    $stmt->fetch();
    $stmt->close();

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
                $imagePath = $targetFile;

                // Delete old image if exists
                if (!empty($currentImagePath) && file_exists($currentImagePath)) {
                    unlink($currentImagePath);
                }
            } else {
                $_SESSION['error'] = "Failed to upload new image.";
                header("Location: categories.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Invalid image type. Allowed: jpg, jpeg, png, gif, webp.";
            header("Location: categories.php");
            exit();
        }
    } else {
        $imagePath = $currentImagePath; // retain old image
    }

    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE categories SET name = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $imagePath, $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category updated successfully.";
        } else {
            $_SESSION['error'] = "Update failed.";
        }
    }

    header("Location: categories.php");
    exit();
} else {
    header("Location: categories.php");
    exit();
}
