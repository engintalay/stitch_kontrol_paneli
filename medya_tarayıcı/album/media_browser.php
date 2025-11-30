<?php
// media_browser.php
// /media klasöründen dosya ve klasörleri JSON olarak döner
header('Content-Type: application/json');


$mediaRootFile = __DIR__ . '/media_root.txt';
if (file_exists($mediaRootFile)) {
    $lines = file($mediaRootFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $mediaRoot = '';
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $mediaRoot = $line;
        break;
    }
    $mediaRoot = rtrim($mediaRoot, "/\");
    if ($mediaRoot === '') {
        $msg = 'media_root.txt boş veya sadece açıklama satırı var';
        error_log('[media_browser.php] ' . $msg);
        echo json_encode(['error' => $msg]);
        exit;
    }
} else {
    echo json_encode(['error' => 'media_root.txt bulunamadı', 'file' => $mediaRootFile]);
    exit;
}
$baseDir = realpath($mediaRoot);
$relPath = isset($_GET['path']) ? $_GET['path'] : '';
$relPath = trim($relPath, '/');


$debug = [];
$debug['mediaRoot'] = $mediaRoot;
$debug['baseDir'] = $baseDir;
$debug['relPath'] = $relPath;
$debug['request'] = $_REQUEST;

$targetDir = $relPath ? realpath($baseDir . '/' . $relPath) : $baseDir;
$debug['targetDir'] = $targetDir;
if (!$targetDir || strpos($targetDir, $baseDir) !== 0) {
    $msg = 'Geçersiz yol';
    error_log('[media_browser.php] ' . $msg . ' | Debug: ' . json_encode($debug));
    echo json_encode(['error' => $msg, 'debug' => $debug]);
    exit;
}


$items = @scandir($targetDir);
if ($items === false) {
    $msg = 'Dizin okunamadı';
    error_log('[media_browser.php] ' . $msg . ' | Debug: ' . json_encode($debug));
    echo json_encode(['error' => $msg, 'debug' => $debug]);
    exit;
}
$result = [];
// Geri seçeneği ekle (sadece kök altındaysa)
if ($relPath) {
    $parentPath = dirname($relPath);
    if ($parentPath === '.') $parentPath = '';
    $result[] = [
        'name' => '..',
        'path' => $parentPath,
        'type' => 'back'
    ];
}
foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $fullPath = $targetDir . '/' . $item;
    $relItemPath = $relPath ? ($relPath . '/' . $item) : $item;
    $type = 'file';
    $size = null;
    if (is_dir($fullPath)) {
        $type = 'dir';
    } else {
        $ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) {
            $type = 'image';
        } elseif (in_array($ext, ['mp4','webm','mkv','avi','mov'])) {
            $type = 'video';
        }
        $size = is_file($fullPath) ? filesize($fullPath) : null;
    }
    $result[] = [
        'name' => $item,
        'path' => $relItemPath,
        'type' => $type,
        'size' => $size
    ];
}

echo json_encode([
    'items_org' => $items,
    'request' => $_REQUEST,
    'items' => $result,
    'current' => $relPath,
    'root' => $mediaRoot,
    'debug' => $debug
]);
