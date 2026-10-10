<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = getPDO();
$paths = $pdo->query('SELECT id, name, x, y, width, height FROM paths ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($paths);
