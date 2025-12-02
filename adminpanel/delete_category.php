<?php
session_start(); // Start session to use $_SESSION
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = $_POST["id"];

    // Check if category is being used in any items
    $check = $conn->prepare("SELECT COUNT(*) as total FROM items WHERE category_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $res = $check->get_result()->fetch_assoc();

    if ($res['total'] > 0) {
        $_SESSION['error'] = "Cannot delete: category in use.";
    } else {
        // Get image path to delete the file
        $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($imagePath);
        $stmt->fetch();
        $stmt->close();

        // Delete category from database
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Delete the image file from the server
            if (!empty($imagePath) && file_exists($imagePath)) {
                unlink($imagePath);
            }
            $_SESSION['success'] = "Category deleted.";
        } else {
            $_SESSION['error'] = "Delete failed.";
        }
    }
}

header("Location: categories.php");
exit();
