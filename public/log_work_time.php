<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['worker_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

$pdo = getPDO();
$worker_id = intval($data['worker_id']);
$action = $data['action']; // 'start' or 'end'

try {
    if ($action === 'start') {
        // Check if worker already has active session
        $stmt = $pdo->prepare('SELECT id FROM work_logs WHERE worker_id = ? AND status = "active" LIMIT 1');
        $stmt->execute([$worker_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Worker already has active session']);
            exit;
        }
        
        // Start new work session
        $stmt = $pdo->prepare('INSERT INTO work_logs (worker_id, start_time, status) VALUES (?, NOW(), "active")');
        $stmt->execute([$worker_id]);
        
        echo json_encode(['success' => true, 'message' => 'Work session started']);
        
    } elseif ($action === 'end') {
        // End active work session
        $stmt = $pdo->prepare('SELECT id, start_time FROM work_logs WHERE worker_id = ? AND status = "active" LIMIT 1');
        $stmt->execute([$worker_id]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$log) {
            echo json_encode(['success' => false, 'error' => 'No active session found']);
            exit;
        }
        
        // Calculate duration and update
        $stmt = $pdo->prepare('UPDATE work_logs SET end_time = NOW(), duration_minutes = TIMESTAMPDIFF(MINUTE, start_time, NOW()), status = "completed" WHERE id = ?');
        $stmt->execute([$log['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Work session ended']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>