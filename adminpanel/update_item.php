<?php
session_start();
if (!isset($_SESSION['admin'])) exit('Unauthorized');

include 'db.php';

$item_id = $_POST['item_id'] ?? '';
$price = $_POST['price'] ?? '';
$subtitle = $_POST['subtitle'] ?? '';

if (!$item_id) exit('Item ID missing');

// Handle image upload
$image_name = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    // Fetch old image
    $oldImage = $conn->query("SELECT image FROM items WHERE id='$item_id'")->fetch_assoc()['image'];

    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $image_name = 'item_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
    $upload_dir = 'uploads/items/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_name)) {
        // Delete old image if exists
        if ($oldImage && file_exists($upload_dir . $oldImage)) {
            unlink($upload_dir . $oldImage);
        }
    }
}

// Build query dynamically
$fields = [];
$params = [];
$types = '';
if ($price !== '') { $fields[] = 'price=?'; $params[] = $price; $types .= 'd'; }
if ($subtitle !== '') { $fields[] = 'subtitle=?'; $params[] = $subtitle; $types .= 's'; }
if ($image_name) { $fields[] = 'image=?'; $params[] = $image_name; $types .= 's'; }

if (count($fields) > 0) {
    $sql = "UPDATE items SET " . implode(',', $fields) . " WHERE id=?";
    $params[] = $item_id;
    $types .= 'i';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    echo $stmt->execute() ? 'success' : 'Failed';
} else {
    echo 'Nothing to update';
}
