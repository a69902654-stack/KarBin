<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image'])) {
    echo json_encode(['success' => false, 'error' => 'No image data']);
    exit;
}

$imageData = $data['image'];
$timestamp = $data['timestamp'] ?? time();
$descriptor = $data['descriptor'] ?? null;


$imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$decodedImage = base64_decode($imageData);


$uploadDir = __DIR__ . '/../uploads/faces/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}


$filename = 'face_' . $timestamp . '_' . uniqid() . '.jpg';
$filepath = $uploadDir . $filename;


if (file_put_contents($filepath, $decodedImage)) {

    try {
        $pdo = getPDO();
        $stmt = $pdo->prepare('INSERT INTO face_encodings (filename, descriptor, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$filename, json_encode($descriptor)]);
        
        echo json_encode([
            'success' => true,
            'filename' => $filename,
            'path' => '/uploads/faces/' . $filename,
            'has_descriptor' => !empty($descriptor)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
}
?>