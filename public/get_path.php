<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing path ID']);
    exit;
}

try {
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM paths WHERE id = ? LIMIT 1');
    $stmt->execute([intval($_GET['id'])]);
    $path = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($path) {
        echo json_encode($path);
    } else {
        echo json_encode(['error' => 'Path not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>