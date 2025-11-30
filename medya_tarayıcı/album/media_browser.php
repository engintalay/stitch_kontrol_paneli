<?php
require_once __DIR__ . '/../../auth_check.php';
// media_browser.php
// /media klasöründen dosya ve klasörleri JSON olarak döner
header('Content-Type: application/json');

error_log('[media_browser.php] Başlatıldı');

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
    $mediaRoot = rtrim($mediaRoot, "/\\");
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
error_log('[media_browser.php] mediaRoot yolu: ' . $mediaRoot);
$baseDir = false;
if ($mediaRoot !== '') {
    // Eğer yol absolute değilse, proje köküne göre çöz
    if ($mediaRoot[0] === '/') {
        $baseDir = realpath($mediaRoot);
    } else {
        // Proje kökü: media_browser.php dosyasının 2 üstü
        $projectRoot = realpath(__DIR__ . '/../..');
        error_log('[media_browser.php] projectRoot: ' . $projectRoot);
        $baseDir = realpath($projectRoot . '/' . $mediaRoot);
    }
}
error_log('[media_browser.php] Çözümlenen baseDir yolu: ' . ($baseDir ?: 'false'));
$relPath = isset($_GET['path']) ? $_GET['path'] : '';
$relPath = trim($relPath, '/');
error_log('[media_browser.php] İstenen relatif yol: ' . $relPath);
// mediaRoot klasörü erişim ve içerik kontrolü
if (!$baseDir || !is_dir($baseDir)) {
    $msg = 'mediaRoot klasörü bulunamadı veya dizin değil: ' . $mediaRoot . ' (çözümlenen: ' . ($baseDir ?: 'false') . ')';
    error_log('[media_browser.php] ' . $msg);
    error_log($baseDir);
    echo json_encode(['error' => $msg, 'debug' => [
        'mediaRoot' => $mediaRoot,
        'relPath' => $relPath,
        'request' => $_REQUEST
    ]]);
    exit;
}
$testFiles = @scandir($baseDir);
if ($testFiles === false || count(array_diff($testFiles, ['.','..'])) === 0) {
    $msg = 'mediaRoot klasörü boş veya okunamıyor: ' . $baseDir;
    error_log('[media_browser.php] ' . $msg);
    echo json_encode(['error' => $msg, 'debug' => [
        'mediaRoot' => $mediaRoot,
        'baseDir' => $baseDir,
        'relPath' => $relPath,
        'request' => $_REQUEST
    ]]);
    exit;
}
// Klasör erişimi ve içerik kontrolü başarılı
error_log('[media_browser.php] mediaRoot klasörü erişim ve içerik kontrolü başarılı: ' . $baseDir);
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
