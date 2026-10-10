<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$eventType = $data['event_type'] ?? $data['activity_type'] ?? $data['activity'] ?? null;

if ($eventType === null || $eventType === '' || !isset($data['description'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

// coordinates و سایر متادیتای اضافی در ستون metadata (JSON) ذخیره می‌شوند
$metadata = null;
if (isset($data['coordinates']) && $data['coordinates'] !== null) {
    $metadata = json_encode(['coordinates' => $data['coordinates']], JSON_UNESCAPED_UNICODE);
} elseif (isset($data['metadata']) && $data['metadata'] !== null) {
    $metadata = is_string($data['metadata']) ? $data['metadata'] : json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('INSERT INTO activity_logs (activity_type, description, worker_id, metadata) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $eventType,
        $data['description'],
        $data['worker_id'] ?? null,
        $metadata
    ]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>