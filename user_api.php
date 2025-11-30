<?php
// user_api.php - AJAX endpoint for user management
require_once __DIR__ . '/auth_check.php';

$db = new SQLite3(__DIR__ . '/users.db');
$db->exec('CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE, password TEXT, role TEXT, permissions TEXT DEFAULT "")');

// Ensure compatibility with older DBs: add missing columns if they're missing
$cols = [];
try {
    $colsRes = $db->query("PRAGMA table_info('users')");
    while ($c = $colsRes->fetchArray(SQLITE3_ASSOC)) {
        $cols[] = $c['name'];
    }
    if (!in_array('permissions', $cols)) {
        // add the column with a default empty JSON string for older rows
        $db->exec("ALTER TABLE users ADD COLUMN permissions TEXT DEFAULT ''");
        $cols[] = 'permissions';
    }
    // ensure 'password' column exists (older DBs may not have it)
    if (!in_array('password', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN password TEXT DEFAULT ''");
        $cols[] = 'password';
    }
    // ensure 'role' column exists
    if (!in_array('role', $cols)) {
        $db->exec("ALTER TABLE users ADD COLUMN role TEXT DEFAULT 'user'");
        $cols[] = 'role';
    }
    // (password_hash, if present, is already included by PRAGMA)
} catch (Exception $e) {
    // If migration fails, continue — queries will report errors which will be handled below
}

// Determine which password column to use when inserting/updating users
$passwordColumn = in_array('password_hash', $cols) ? 'password_hash' : 'password';

header('Content-Type: application/json; charset=utf-8');

// Create table to store per-user module permissions (one row per allowed module)
$db->exec("CREATE TABLE IF NOT EXISTS user_modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    module_key TEXT NOT NULL,
    UNIQUE(user_id, module_key)
)");

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
    if (!$stmt) error('Veritabanı hatası (listeleme hazırlanamadı).');
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
    $hash = hash('sha256', $password);

    $insertCols = ['email', $passwordColumn, 'role', 'permissions'];
    $placeholders = [':email', ':password', ':role', ':permissions'];
    $sql = 'INSERT INTO users (' . implode(', ', $insertCols) . ') VALUES (' . implode(', ', $placeholders) . ')';
    $stmt = $db->prepare($sql);
    if (!$stmt) error('Veritabanı hatası (ekleme hazırlanamadı): ' . $db->lastErrorMsg());
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':permissions', json_encode($permissions), SQLITE3_TEXT);
    $res = $stmt->execute();
    if ($res === false) {
        error('Kullanıcı eklenemedi: ' . $db->lastErrorMsg());
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $role = $_POST['role'] ?? '';
    $permissions = $_POST['permissions'] ?? [];
    if (!$id) error('ID zorunlu.');
    // Allow optional password update. If provided, hash and include in the UPDATE.
    $password = $_POST['password'] ?? '';
    $setParts = ['role = :role', 'permissions = :permissions'];
    if ($password !== '') {
        $setParts[] = "$passwordColumn = :password";
    }
    $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = :id';
    $stmt = $db->prepare($sql);
    if (!$stmt) error('Veritabanı hatası (güncelleme hazırlanamadı): ' . $db->lastErrorMsg());
    $stmt->bindValue(':role', $role, SQLITE3_TEXT);
    $stmt->bindValue(':permissions', json_encode($permissions), SQLITE3_TEXT);
    if ($password !== '') {
        if ($passwordColumn === 'password_hash') {
            $hash = hash('sha256', $password);
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
        }
        $stmt->bindValue(':password', $hash, SQLITE3_TEXT);
    }
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    if ($res === false) {
        error('Güncelleme başarısız: ' . $db->lastErrorMsg());
    }
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) error('ID zorunlu.');
    $stmt = $db->prepare('DELETE FROM users WHERE id = :id');
    if (!$stmt) error('Veritabanı hatası (silme hazırlanamadı).');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmt->execute();
    echo json_encode(['ok' => true]);
    exit;
}

// Return modules assigned to a user: ?action=modules&user_id=123
if ($action === 'modules') {
    $user_id = intval($_GET['user_id'] ?? $_POST['user_id'] ?? 0);
    if (!$user_id) error('user_id zorunlu.');
    $stmt = $db->prepare('SELECT module_key FROM user_modules WHERE user_id = :uid ORDER BY module_key ASC');
    if (!$stmt) error('Veritabanı hatası (modül listesi hazırlanamadı).');
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $mods = [];
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $mods[] = $r['module_key'];
    }
    echo json_encode(['ok' => true, 'modules' => $mods]);
    exit;
}

// Set modules for a user (replace existing): POST action=set_modules user_id=.. modules[]=mod1&modules[]=mod2
if ($action === 'set_modules') {
    $user_id = intval($_POST['user_id'] ?? 0);
    if (!$user_id) error('user_id zorunlu.');
    $modules = $_POST['modules'] ?? [];
    if (!is_array($modules)) $modules = [$modules];
    // Begin transaction-like behavior
    $db->exec('BEGIN');
    $del = $db->prepare('DELETE FROM user_modules WHERE user_id = :uid');
    if (!$del) { $db->exec('ROLLBACK'); error('Veritabanı hatası (silme hazırlanamadı).'); }
    $del->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $del->execute();
    $ins = $db->prepare('INSERT OR IGNORE INTO user_modules (user_id, module_key) VALUES (:uid, :mkey)');
    if (!$ins) { $db->exec('ROLLBACK'); error('Veritabanı hatası (ekleme hazırlanamadı).'); }
    foreach ($modules as $m) {
        $ins->bindValue(':uid', $user_id, SQLITE3_INTEGER);
        $ins->bindValue(':mkey', trim((string)$m), SQLITE3_TEXT);
        $ins->execute();
    }
    $db->exec('COMMIT');
    echo json_encode(['ok' => true]);
    exit;
}

error('Geçersiz istek.');
