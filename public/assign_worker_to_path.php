<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['worker_id']) || !isset($data['path_name'])) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    $pdo = getPDO();
    
    // Get the latest path with this name
    $stmt = $pdo->prepare('SELECT id FROM paths WHERE name = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute([$data['path_name']]);
    $path = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$path) {
        echo json_encode(['success' => false, 'error' => 'Path not found']);
        exit;
    }
    
    // Assign worker to path
    $stmt = $pdo->prepare('UPDATE workers SET path_id = ? WHERE id = ?');
    $stmt->execute([$path['id'], $data['worker_id']]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>