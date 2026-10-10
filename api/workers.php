<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json; charset=utf-8');
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id WHERE w.id = ? LIMIT 1');
        $stmt->execute([$id]);
        $w = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($w) {
            $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
            $w['image_url'] = $w['image'] ? $base . '/uploads/' . basename($w['image']) : null;
            echo json_encode(['ok' => true, 'worker' => $w]);
        } else echo json_encode(['ok' => false, 'msg' => 'not found']);
    } else {
        $list = $pdo->query('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id ORDER BY w.id DESC')->fetchAll(PDO::FETCH_ASSOC);
        $base = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
        foreach($list as &$w){ $w['image_url'] = $w['image'] ? $base . '/uploads/' . basename($w['image']) : null; }
        echo json_encode($list);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $path_id = isset($_POST['path_id']) ? intval($_POST['path_id']) : null;
    $image = trim($_POST['image'] ?? '') ?: null;
    if ($id) {
        $stmt = $pdo->prepare('UPDATE workers SET name = ?, details = ?, path_id = ?, image = ? WHERE id = ?');
        $stmt->execute([$name,$details,$path_id,$image,$id]);
        echo json_encode(['ok' => true]); exit;
    } else {
        $stmt = $pdo->prepare('INSERT INTO workers (name, details, path_id, image, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $details, $path_id, $image]);
        echo json_encode(['ok' => true, 'id' => $pdo->lastInsertId()]); exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $d);
    $id = intval($d['id'] ?? 0);
    if ($id) {
        $stmt = $pdo->prepare('DELETE FROM workers WHERE id = ?');
        $stmt->execute([$id]);
        echo json_encode(['ok' => true]); exit;
    }
    echo json_encode(['ok' => false]); exit;
}

echo json_encode(['ok' => false, 'msg' => 'unsupported method']);
