<?php
// yapay_zeka_sohbet_botu/chat_api.php
require_once __DIR__ . '/../auth_check.php';

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'];
$dbPath = __DIR__ . '/chat.db';
$settingsDbPath = __DIR__ . '/../settings.db';

@ini_set('display_errors', 0);
@ini_set('log_errors', 1);

function jsonOut($data) {
    echo json_encode($data);
    exit;
}

function error($msg) {
    http_response_code(400);
    jsonOut(['ok' => false, 'error' => $msg]);
}

try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    
    $db->exec('CREATE TABLE IF NOT EXISTS conversations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conversation_id INTEGER NOT NULL,
        role TEXT NOT NULL, -- user, assistant, system
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    )');
} catch (Exception $e) {
    http_response_code(500);
    jsonOut(['ok' => false, 'error' => 'Veritabanı hatası: ' . $e->getMessage()]);
}

$action = $_REQUEST['action'] ?? '';

// Helper to get a setting
function getSetting($db, $uid, $key, $default = '') {
    $stmt = $db->prepare('SELECT setting_value FROM user_settings WHERE user_id = :uid AND setting_key = :key');
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    return ($row && !empty($row['setting_value'])) ? $row['setting_value'] : $default;
}

// Helper to set a setting
function setSetting($db, $uid, $key, $val) {
    // Check exist
    $stmt = $db->prepare('SELECT id FROM user_settings WHERE user_id = :uid AND setting_key = :key');
    $stmt->bindValue(':uid', $uid, SQLITE3_TEXT);
    $stmt->bindValue(':key', $key, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    
    if ($row) {
        $upd = $db->prepare('UPDATE user_settings SET setting_value = :val WHERE id = :id');
        $upd->bindValue(':val', $val, SQLITE3_TEXT);
        $upd->bindValue(':id', $row['id'], SQLITE3_INTEGER);
        $upd->execute();
    } else {
        $ins = $db->prepare('INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (:uid, :key, :val)');
        $ins->bindValue(':uid', $uid, SQLITE3_TEXT);
        $ins->bindValue(':key', $key, SQLITE3_TEXT);
        $ins->bindValue(':val', $val, SQLITE3_TEXT);
        $ins->execute();
    }
}

// 2. Handle Settings Management
if ($action === 'save_settings') {
    $apiKey = trim($_POST['api_key'] ?? '');
    $baseUrl = trim($_POST['base_url'] ?? '');
    $model = trim($_POST['model'] ?? '');
    
    // API Key can be empty for some local LLMs but usually needed. Let's allow empty if user insists, but warn if nothing works.
    // Actually, let's keep it required for now as most need it or a dummy one.
    // if (!$apiKey) error('API Anahtarı boş olamaz.');
    
    $sDb = new SQLite3($settingsDbPath);
    setSetting($sDb, $userId, 'openai_api_key', $apiKey);
    setSetting($sDb, $userId, 'openai_base_url', $baseUrl);
    setSetting($sDb, $userId, 'openai_model', $model);
    
    jsonOut(['ok' => true]);
}

if ($action === 'get_settings') {
    $sDb = new SQLite3($settingsDbPath);
    $apiKey = getSetting($sDb, $userId, 'openai_api_key', '');
    $baseUrl = getSetting($sDb, $userId, 'openai_base_url', 'https://api.openai.com/v1');
    $model = getSetting($sDb, $userId, 'openai_model', 'gpt-4o');
    
    jsonOut([
        'ok' => true, 
        'api_key' => $apiKey, 
        'base_url' => $baseUrl, 
        'model' => $model,
        'has_key' => !empty($apiKey)
    ]);
}

// 3. Chat Management
if ($action === 'list_chats') {
    $stmt = $db->prepare('SELECT * FROM conversations WHERE user_id = :uid ORDER BY created_at DESC');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $chats = [];
    while ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $chats[] = $r;
    }
    jsonOut(['ok' => true, 'chats' => $chats]);
}

if ($action === 'new_chat') {
    $stmt = $db->prepare('INSERT INTO conversations (user_id, title) VALUES (:uid, :title)');
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->bindValue(':title', 'Yeni Sohbet');
    $stmt->execute();
    $id = $db->lastInsertRowID();
    jsonOut(['ok' => true, 'id' => $id]);
}

if ($action === 'get_chat') {
    $chatId = intval($_GET['id'] ?? 0);
    if (!$chatId) error('ID gerekli.');
    
    // Auth check for chat
    $stmtC = $db->prepare('SELECT * FROM conversations WHERE id = :id AND user_id = :uid');
    $stmtC->bindValue(':id', $chatId, SQLITE3_INTEGER);
    $stmtC->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $resC = $stmtC->execute();
    $chat = $resC->fetchArray(SQLITE3_ASSOC);
    if (!$chat) error('Sohbet bulunamadı.');
    
    $stmtM = $db->prepare('SELECT * FROM messages WHERE conversation_id = :cid ORDER BY id ASC');
    $stmtM->bindValue(':cid', $chatId, SQLITE3_INTEGER);
    $resM = $stmtM->execute();
    $messages = [];
    while ($r = $resM->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = $r;
    }
    
    jsonOut(['ok' => true, 'chat' => $chat, 'messages' => $messages]);
}

if ($action === 'list_models') {
    $sDb = new SQLite3($settingsDbPath);
    $apiKey = getSetting($sDb, $userId, 'openai_api_key', '');
    $baseUrl = getSetting($sDb, $userId, 'openai_base_url', 'https://api.openai.com/v1');
    
    $baseUrl = rtrim($baseUrl, '/');
    $apiUrl = $baseUrl . '/models';
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    // Timeout for checking models should be short
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        jsonOut(['ok' => false, 'error' => 'Bağlantı hatası: ' . $err]);
    }
    
    $json = json_decode($response, true);
    if ($httpCode !== 200) {
        $msg = $json['error']['message'] ?? 'Sunucu hatası (' . $httpCode . ')';
        jsonOut(['ok' => false, 'error' => $msg]);
    }

    // OpenAI format: { object: "list", data: [ {id: "gpt-4", ...}, ... ] }
    $models = [];
    if (isset($json['data']) && is_array($json['data'])) {
        foreach ($json['data'] as $m) {
            if (isset($m['id'])) $models[] = $m['id'];
        }
    }
    
    jsonOut(['ok' => true, 'models' => $models]);
}

if ($action === 'delete_chat') {
    $chatId = intval($_POST['id'] ?? 0);
    if (!$chatId) error('ID gerekli.');
    
    $stmt = $db->prepare('DELETE FROM conversations WHERE id = :id AND user_id = :uid');
    $stmt->bindValue(':id', $chatId, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    jsonOut(['ok' => true]);
}

if ($action === 'send_message') {
    $chatId = intval($_POST['chat_id'] ?? 0);
    $content = trim($_POST['message'] ?? '');
    if (!$chatId || !$content) error('Geçersiz veri.');

    // 1. Get Settings
    $sDb = new SQLite3($settingsDbPath);
    $apiKey = getSetting($sDb, $userId, 'openai_api_key', '');
    $baseUrl = getSetting($sDb, $userId, 'openai_base_url', 'https://api.openai.com/v1');
    $model = getSetting($sDb, $userId, 'openai_model', 'gpt-4o');
    
    // Normalize Base URL: remove trailing slash
    $baseUrl = rtrim($baseUrl, '/');
    $apiUrl = $baseUrl . '/chat/completions';
    
    // Some local LLMs don't enforce API key, but we warn if empty just in case unless user knows what they are doing.
    // We already allow saving empty, so we pass it as is.

    // 2. Validate Chat Ownership
    $stmtC = $db->prepare('SELECT * FROM conversations WHERE id = :id AND user_id = :uid');
    $stmtC->bindValue(':id', $chatId, SQLITE3_INTEGER);
    $stmtC->bindValue(':uid', $userId, SQLITE3_INTEGER);
    if (!$stmtC->execute()->fetchArray()) error('Sohbet bulunamadı.');

    // 3. Save User Message
    $stmtIns = $db->prepare('INSERT INTO messages (conversation_id, role, content) VALUES (:cid, :role, :content)');
    $stmtIns->bindValue(':cid', $chatId, SQLITE3_INTEGER);
    $stmtIns->bindValue(':role', 'user', SQLITE3_TEXT);
    $stmtIns->bindValue(':content', $content, SQLITE3_TEXT);
    $stmtIns->execute();

    // 4. Update Title if it's the first message
    $countRes = $db->querySingle("SELECT COUNT(*) FROM messages WHERE conversation_id = $chatId");
    if ($countRes <= 1) {
        $shortTitle = mb_substr($content, 0, 30) . (mb_strlen($content) > 30 ? '...' : '');
        $updT = $db->prepare('UPDATE conversations SET title = :t WHERE id = :id');
        $updT->bindValue(':t', $shortTitle, SQLITE3_TEXT);
        $updT->bindValue(':id', $chatId, SQLITE3_INTEGER);
        $updT->execute();
    }

    // 5. Prepare Context
    $context = [];
    // Only add system prompt if it's NOT a local LLM that might act weird, but usually it's fine.
    // Or maybe make system prompt configurable? For now stick to default.
    $context[] = ['role' => 'system', 'content' => 'Sen yardımsever bir asistansın. Türkçe cevap ver.'];
    
    $resHist = $db->query("SELECT role, content FROM messages WHERE conversation_id = $chatId ORDER BY id ASC");
    while ($r = $resHist->fetchArray(SQLITE3_ASSOC)) {
        $context[] = ['role' => $r['role'], 'content' => $r['content']];
    }

    // 6. Call API
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model,
        'messages' => $context,
        'temperature' => 0.7
    ]));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) error('Bağlantı Hatası: ' . $err);
    
    $json = json_decode($response, true);
    if ($httpCode !== 200 || isset($json['error'])) {
        $apiErr = $json['error']['message'] ?? 'Bilinmeyen hata';
        error('API Hatası (' . $httpCode . '): ' . $apiErr);
    }

    $assistantMsg = $json['choices'][0]['message']['content'] ?? '';
    
    if (!$assistantMsg) error('Boş cevap alındı.');

    // 7. Save Assistant Message
    $stmtIns->bindValue(':role', 'assistant', SQLITE3_TEXT);
    $stmtIns->bindValue(':content', $assistantMsg, SQLITE3_TEXT);
    $stmtIns->execute();

    jsonOut(['ok' => true, 'response' => $assistantMsg]);
}
