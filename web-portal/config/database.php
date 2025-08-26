<?php
// Database configuration for InfinityFree hosting
$host = 'sql301.infinityfree.com'; // Replace with your actual DB host
$dbname = 'if0_38809716_1222'; // Replace with your actual DB name
$username = 'if0_38809716'; // Replace with your actual DB username
$password = 'Z4o7jCUbohAsZG'; // Replace with your actual DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}
?>
