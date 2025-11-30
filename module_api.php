<?php
// module_api.php - AJAX endpoint for module management
require_once __DIR__ . '/auth_check.php';

$db = new SQLite3(__DIR__ . '/modules.db');
$db->exec('CREATE TABLE IF NOT EXISTS modules (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT UNIQUE, desc TEXT, status TEXT)');

header('Content-Type: application/json; charset=utf-8');

function error($msg) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $res = $db->query('SELECT * FROM modules ORDER BY id ASC');
    $modules = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $modules[] = $row;
    }
    echo json_encode(['ok' => true, 'modules' => $modules]);
    exit;
}

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    if (!$name) error('Modül adı zorunlu.');
    $stmt = $db->prepare('INSERT INTO modules (name, desc, status) VALUES (:name, :desc, :status)');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':desc', $desc, SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    try {
        $stmt->execute();
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        error('Modül eklenemedi: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['desc'] ?? '');
    $status = $_POST['status'] ?? 'aktif';
    if (!$id || !$name) error('ID ve modül adı zorunlu.');
    $stmt = $db->prepare('UPDATE modules SET name = :name, desc = :desc, status = :status WHERE id = :id');
    $stmt->bindValue(':name', $name, SQLITE3_TEXT);
    $stmt->bindValue(':desc', $desc, SQLITE3_TEXT);
    $stmt->bindValue(':status', $status, SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) error('ID zorunlu.');
    $stmt = $db->prepare('DELETE FROM modules WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

error('Geçersiz istek.');
