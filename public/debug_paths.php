<?php
require_once __DIR__ . '/../config.php';
$pdo = getPDO();

echo "<h3>Debug Paths with Assignments</h3>";

// Check if path_assignments table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'path_assignments'");
    if ($result->rowCount() == 0) {
        echo "<p style='color:red'>Table path_assignments does not exist!</p>";
    } else {
        echo "<p style='color:green'>Table path_assignments exists</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error checking table: " . $e->getMessage() . "</p>";
}

// Show all assignments
try {
    $assignments = $pdo->query("SELECT * FROM path_assignments")->fetchAll(PDO::FETCH_ASSOC);
    echo "<h4>Path Assignments (" . count($assignments) . "):</h4>";
    echo "<pre>" . print_r($assignments, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error getting assignments: " . $e->getMessage() . "</p>";
}

// Show paths with worker info
try {
    $paths = $pdo->query('
        SELECT p.*, w.name as worker_name, w.image as worker_image 
        FROM paths p 
        LEFT JOIN path_assignments pa ON p.id = pa.path_id 
        LEFT JOIN workers w ON pa.worker_id = w.id 
        ORDER BY p.id DESC
    ')->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Paths with Worker Info (" . count($paths) . "):</h4>";
    echo "<pre>" . print_r($paths, true) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red'>Error getting paths: " . $e->getMessage() . "</p>";
}
?>