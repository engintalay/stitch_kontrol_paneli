<?php
// module_api.php - AJAX endpoint for module management
require_once __DIR__ . '/auth_check.php';

$db = new SQLite3(__DIR__ . '/modules.db');
// Prefer the schema used by the front page: title, icon, description, link, admin_only
$db->exec('CREATE TABLE IF NOT EXISTS modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    icon TEXT NOT NULL,
    description TEXT NOT NULL,
    link TEXT NOT NULL,
    admin_only INTEGER DEFAULT 0
)');

// Detect legacy schema (name/desc/status) and adapt if needed
$cols = [];
try {
    $res = $db->query("PRAGMA table_info('modules')");
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $cols[] = $r['name'];
    }
} catch (Exception $e) {
    // ignore
}

header('Content-Type: application/json; charset=utf-8');

function error($msg) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'list') {
    $modules = [];
    $res = $db->query('SELECT * FROM modules ORDER BY id ASC');
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        // Normalize to modern keys: title, icon, description, link, admin_only
        if (isset($row['title'])) {
            $modules[] = $row;
        } else {
            // legacy columns: name, desc, status — map them
            $modules[] = [
                'id' => $row['id'] ?? null,
                'title' => $row['name'] ?? ($row['title'] ?? ''),
                'icon' => $row['icon'] ?? 'apps',
                'description' => $row['desc'] ?? ($row['description'] ?? ''),
                'link' => $row['link'] ?? '#',
                'admin_only' => isset($row['status']) ? ($row['status'] === 'aktif' ? 0 : 0) : 0,
            ];
        }
    }
    echo json_encode(['ok' => true, 'modules' => $modules]);
    exit;
}

if ($action === 'add') {
    $title = trim($_POST['title'] ?? '');
    $icon = trim($_POST['icon'] ?? 'apps');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '#');
    $admin_only = intval($_POST['admin_only'] ?? 0);
    if (!$title) error('Modül başlığı zorunlu.');
    $stmt = $db->prepare('INSERT INTO modules (title, icon, description, link, admin_only) VALUES (:title, :icon, :description, :link, :admin_only)');
    if (!$stmt) error('Veritabanı hatası (ekleme hazırlanamadı): ' . $db->lastErrorMsg());
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':icon', $icon, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':link', $link, SQLITE3_TEXT);
    $stmt->bindValue(':admin_only', $admin_only, SQLITE3_INTEGER);
    try {
        $res = $stmt->execute();
        if ($res === false) error('Modül eklenemedi: ' . $db->lastErrorMsg());
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        error('Modül eklenemedi: ' . $e->getMessage());
    }
    exit;
}

if ($action === 'update') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $icon = trim($_POST['icon'] ?? 'apps');
    $description = trim($_POST['description'] ?? '');
    $link = trim($_POST['link'] ?? '#');
    $admin_only = intval($_POST['admin_only'] ?? 0);
    if (!$id || !$title) error('ID ve modül başlığı zorunlu.');
    $stmt = $db->prepare('UPDATE modules SET title = :title, icon = :icon, description = :description, link = :link, admin_only = :admin_only WHERE id = :id');
    if (!$stmt) error('Veritabanı hatası (güncelleme hazırlanamadı): ' . $db->lastErrorMsg());
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':icon', $icon, SQLITE3_TEXT);
    $stmt->bindValue(':description', $description, SQLITE3_TEXT);
    $stmt->bindValue(':link', $link, SQLITE3_TEXT);
    $stmt->bindValue(':admin_only', $admin_only, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    if ($res === false) error('Güncelleme başarısız: ' . $db->lastErrorMsg());
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) error('ID zorunlu.');
    $stmt = $db->prepare('DELETE FROM modules WHERE id = :id');
    if (!$stmt) error('Veritabanı hatası (silme hazırlanamadı): ' . $db->lastErrorMsg());
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    if ($res === false) error('Silme başarısız: ' . $db->lastErrorMsg());
    echo json_encode(['ok' => true]);
    exit;
}

error('Geçersiz istek.');
