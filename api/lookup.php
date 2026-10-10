<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
$path_id = isset($_GET['path_id']) ? intval($_GET['path_id']) : 0;
if (!$path_id) { echo json_encode(['error' => 'path_id required']); exit; }
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT * FROM workers WHERE path_id = ? LIMIT 1');
$stmt->execute([$path_id]);
$worker = $stmt->fetch(PDO::FETCH_ASSOC);
if ($worker) {
    $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
    $worker['image_url'] = $worker['image'] ? $base . '/uploads/' . basename($worker['image']) : null;
    echo json_encode(['ok' => true, 'worker' => $worker]);
} else {
    echo json_encode(['ok' => false, 'msg' => 'no worker for this path']);
}
