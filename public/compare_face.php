<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['image'])) {
    echo json_encode(['match' => false, 'error' => 'No image data']);
    exit;
}

$pdo = getPDO();

// Get all workers with images
$workers = $pdo->query('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id WHERE w.image IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);

// Simple face matching based on image similarity
$currentImage = $data['image'];

// For demo purposes, we'll do a simple random match
// In real implementation, you would use face recognition algorithms
$matchFound = false;
$matchedWorker = null;

if (!empty($workers)) {
    // Simulate face recognition with 70% chance of finding a match
    if (rand(1, 10) <= 7) {
        $matchFound = true;
        $matchedWorker = $workers[array_rand($workers)];
        
        // Add image path for frontend
        if ($matchedWorker['image']) {
            $matchedWorker['image'] = basename($matchedWorker['image']);
        }
    }
}

echo json_encode([
    'match' => $matchFound,
    'worker' => $matchedWorker,
    'total_workers' => count($workers)
]);
?>