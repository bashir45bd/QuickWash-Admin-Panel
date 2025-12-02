<?php
include "db.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $name = $_POST['name'];
    $subtitle = $_POST['subtitle'] ?? '';
    $category_id = $_POST['category_id'];
    $service_id = $_POST['service_id'];
    $price = $_POST['price'];
    $created_at = date('Y-m-d H:i:s');

    $imagePath = null;

    // Image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {

        $targetDir = "uploads/items/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = $fileName;
        }
    }

    // Prepare Insert
    $sql = "INSERT INTO items (category_id, service_id, name, subtitle, image, price, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Debug: show error if prepare fails
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters (price as string)
    $stmt->bind_param("iisssss", 
        $category_id, 
        $service_id, 
        $name, 
        $subtitle, 
        $imagePath, 
        $price, 
        $created_at
    );

    // Execute
    if ($stmt->execute()) {
        header("Location: manage_items.php");
        exit();
    } else {
        echo "Error executing query: " . $stmt->error;
    }
}
?>
