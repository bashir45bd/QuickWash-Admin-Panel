<?php
include 'db.php';

$userId = $_POST['user_id'];
$fcmToken = $_POST['fcm_token'];

// First, fetch the current token from DB
$stmt = $conn->prepare("SELECT fcm_token FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->bind_result($currentToken);
$stmt->fetch();
$stmt->close();

// If token is different, update it
if ($currentToken !== $fcmToken) {
    $updateStmt = $conn->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
    $updateStmt->bind_param("si", $fcmToken, $userId);
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Token updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update token']);
    }
    $updateStmt->close();
} else {
    // Token is same, no need to update
    echo json_encode(['success' => true, 'message' => 'Token already up to date']);
}

$conn->close();
?>
