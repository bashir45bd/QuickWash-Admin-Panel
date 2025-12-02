<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $item_id = intval($_POST['item_id'] ?? 0);

    if ($item_id > 0) {

        // STEP 1: Get image name from DB
        $stmt = $conn->prepare("SELECT image FROM items WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->bind_result($imageName);
        $stmt->fetch();
        $stmt->close();

        // STEP 2: Delete image from server
        if (!empty($imageName)) {
            $filePath = "uploads/items/" . $imageName;
            if (file_exists($filePath)) {
                unlink($filePath);  // delete file
            }
        }

        // STEP 3: Delete item from database
        $stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $item_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Item deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete item.";
        }

        $stmt->close();

    } else {
        $_SESSION['error'] = "Invalid item ID.";
    }

    header("Location: manage_items.php");
    exit();
}
?>
