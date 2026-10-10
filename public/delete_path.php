<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Missing path ID']);
    exit;
}

try {
    $pdo = getPDO();
    
    // First, remove path assignment from workers
    $stmt = $pdo->prepare('UPDATE workers SET path_id = NULL WHERE path_id = ?');
    $stmt->execute([intval($data['id'])]);
    
    // Then delete the path
    $stmt = $pdo->prepare('DELETE FROM paths WHERE id = ?');
    $stmt->execute([intval($data['id'])]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>