<?php
// user_api.php - AJAX endpoint for user management
require_once __DIR__ . '/auth_check.php';

$db = new SQLite3(__DIR__ . '/users.db');
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, password TEXT, role TEXT, permissions TEXT DEFAULT "")');

// Ensure compatibility with older DBs: add 'permissions' column if it's missing
try {
    $colsRes = $db->query("PRAGMA table_info('users')");
    $cols = [];
    while ($c = $colsRes->fetchArray(SQLITE3_ASSOC)) {
        $cols[] = $c['name'];
    }
    if (!in_array('permissions', $cols)) {
        // add the column with a default empty JSON array string for older rows
        $db->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT ''");
    }
} catch (Exception $e) {
    // If migration fails, continue — queries will report errors which will be handled below
}

header('Content-Type: application/json; charset=utf-8');

function error($msg) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $where = $q ? 'WHERE email LIKE :q' : '';
    $stmt = $db->prepare("SELECT id, email, role, permissions FROM users $where ORDER BY id ASC");
    if ($q) $stmt->bindValue(':q', "%$q%", SQLITE3_TEXT);
    $res = $stmt->execute();
    $users = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $row['permissions'] = $row['permissions'] ? json_decode($row['permissions'], true) : [];
        $users[] = $row;
    }
    echo json_encode(['ok' => true, 'users' => $users]);
    exit;
}

if ($action === 'add') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $permissions = $_POST['permissions'] ?? [];
    if (!$email || !$password) error('E-posta ve şifre zorunlu.');
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO users (email, password, role, permissions) VALUES (:email, :password, :role, :permissions)');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':permissions', json_encode($permissions), SQLITE3_TEXT);
    try {
        $stmt->execute();
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        error('Kullanıcı eklenemedi: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $permissions = $_POST['permissions'] ?? [];
    if (!$id) error('ID zorunlu.');
    $stmt = $db->prepare('UPDATE users SET role = :role, permissions = :permissions WHERE id = :id');
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':permissions', json_encode($permissions), SQLITE3_TEXT);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) error('ID zorunlu.');
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

error('Geçersiz istek.');
