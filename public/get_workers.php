<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

try {
    $pdo = getPDO();
    $workers = $pdo->query('SELECT id, name, details, image FROM workers ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    
    // Add image basename for frontend
    foreach($workers as &$worker) {
        if($worker['image']) {
            $worker['image'] = basename($worker['image']);
        }
    }
    
    echo json_encode($workers);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>