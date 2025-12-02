<?php
session_start();
include "db.php";
header('Content-Type: application/json');

if (!isset($_SESSION['admin'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'], $data['name'], $data['phone'], $data['address'], $data['noti_status'])) {
    $id = (int)$data['id'];
    $name = htmlspecialchars($data['name']);
    $phone = htmlspecialchars($data['phone']);
    $address = htmlspecialchars($data['address']);
    $status = htmlspecialchars($data['noti_status']);

    $stmt = $conn->prepare("UPDATE orders SET name = ?, phone = ?, address = ?, noti_status = ? WHERE id = ?");
    $stmt->bind_param('ssssi', $name, $phone, $address, $status, $id);

    if ($stmt->execute()) {
        // Get user_id
        $userQuery = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
        $userQuery->bind_param("i", $id);
        $userQuery->execute();
        $userQuery->bind_result($userId);
        $userQuery->fetch();
        $userQuery->close();

        // Get FCM token
        $tokenStmt = $conn->prepare("SELECT fcm_token FROM users WHERE id = ?");
        $tokenStmt->bind_param("i", $userId);
        $tokenStmt->execute();
        $tokenStmt->bind_result($fcmToken);
        $tokenStmt->fetch();
        $tokenStmt->close();

        if (!empty($fcmToken)) {
            sendFCMPush($fcmToken, "Order Update", "Your order status is now: $status");
        }

        echo json_encode(['status' => 'success', 'message' => 'Order updated and user notified']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Update failed']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
}


// ✅ Send FCM push using HTTP v1 API
function sendFCMPush($deviceToken, $title, $body) {
    $serviceAccountPath = __DIR__ . '/notification_quickwash.json';

    $accessToken = getAccessTokenFromJson($serviceAccountPath);

    $url = "https://fcm.googleapis.com/v1/projects/quickwash-d9074/messages:send";

    $message = [
        "message" => [
            "token" => $deviceToken,
            "notification" => [
                "title" => $title,
                "body" => $body
            ],
            "data" => [
                "click_action" => "ORDER_STATUS_ACTIVITY",
                "status" => $body
            ]
        ]
    ];

    $headers = [
        "Authorization: Bearer $accessToken",
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        error_log("❌ CURL Error: " . curl_error($ch));
    } else {
        error_log("📤 FCM Response ($http_code): $response");
    }
    curl_close($ch);
}

// ✅ Get OAuth2 token from service account
function getAccessTokenFromJson($jsonKeyFilePath) {
    $key = json_decode(file_get_contents($jsonKeyFilePath), true);

    $header = ['alg' => 'RS256', 'typ' => 'JWT'];
    $iat = time();
    $exp = $iat + 3600;
    $claimSet = [
        'iss' => $key['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $iat,
        'exp' => $exp
    ];

    $jwtHeader = base64UrlEncode(json_encode($header));
    $jwtClaim = base64UrlEncode(json_encode($claimSet));
    $data = "$jwtHeader.$jwtClaim";

    // Sign JWT with private key
    openssl_sign($data, $signature, $key['private_key'], 'sha256WithRSAEncryption');
    $jwt = "$data." . base64UrlEncode($signature);

    // Exchange for access token
    $postFields = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    $result = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($result, true);
    return $response['access_token'];
}

// ✅ Helper: Base64 URL encode
function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
?>
