<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$pdo = getPDO();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Save activity data
        $input = json_decode(file_get_contents('php://input'), true);
        $activity = $input['activity_type'] ?? $input['activity'] ?? null;
        
        if (!$input || $activity === null || $activity === '' || !isset($input['worker_id'])) {
            echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (activity_type, worker_id, path_id, confidence) 
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $activity,
            $input['worker_id'],
            $input['path_id'] ?? null,
            $input['confidence'] ?? 0
        ]);
        
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]);
        
    } else {
        // Get activity data
        $worker_id = $_GET['worker_id'] ?? null;
        $limit = min(100, max(1, intval($_GET['limit'] ?? 50)));
        
        $sql = "
            SELECT al.*, w.name as worker_name, p.name as path_name 
            FROM activity_logs al 
            LEFT JOIN workers w ON al.worker_id = w.id 
            LEFT JOIN paths p ON al.path_id = p.id 
        ";
        
        $params = [];
        if ($worker_id) {
            $sql .= " WHERE al.worker_id = ?";
            $params[] = $worker_id;
        }
        
        // $limit قبلاً با intval مقداردهی شده؛ inline کردن آن از خطای LIMIT '?' جلوگیری می‌کند
        $sql .= " ORDER BY al.created_at DESC LIMIT " . $limit;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['ok' => true, 'activities' => $activities]);
    }
    
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
?>