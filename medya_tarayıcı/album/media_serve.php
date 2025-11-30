<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
// media_serve.php: Güvenli medya dosyası sunumu, dinamik kök dizin ve detaylı hata/log desteği
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[media_serve.php] PHP ERROR: $errstr in $errfile on line $errline");
    return false;
});

// Medya kök dizinini media_root.txt dosyasından oku
$mediaRootFile = __DIR__ . '/media_root.txt';
$mediaRoot = false;
if (file_exists($mediaRootFile)) {
    $lines = file($mediaRootFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        $mediaRoot = $line;
        break;
    }
    $mediaRoot = rtrim($mediaRoot, "/\\");
}
error_log('[media_serve.php] mediaRoot yolu: ' . $mediaRoot);
if (!$mediaRoot) {
    $msg = 'media_root.txt okunamadı veya boş.';
    error_log('[media_serve.php] ' . $msg);
    http_response_code(500);
    echo $msg;
    exit;
}
// Göreli ise proje köküne göre çöz
if ($mediaRoot[0] !== '/') {
    $projectRoot = realpath(__DIR__ . '/../..');
    error_log('[media_serve.php] projectRoot: ' . ($projectRoot ?: 'false'));
    $baseDir = realpath($projectRoot . '/' . $mediaRoot);
} else {
    $baseDir = realpath($mediaRoot);
}
error_log('[media_serve.php] Çözümlenen baseDir yolu: ' . ($baseDir ?: 'false'));
if (!$baseDir || !is_dir($baseDir)) {
    $msg = 'Medya kök dizini bulunamadı veya dizin değil: ' . $mediaRoot . ' (çözümlenen: ' . ($baseDir ?: 'false') . ')';
    error_log('[media_serve.php] ' . $msg);
    http_response_code(500);
    echo $msg;
    exit;
}
if (!is_readable($baseDir)) {
    $msg = 'Medya kök dizinine okuma izni yok: ' . $baseDir;
    error_log('[media_serve.php] ' . $msg);
    http_response_code(500);
    echo $msg;
    exit;
}
$relPath = isset($_GET['path']) ? ltrim(trim($_GET['path']), '/') : '';
$targetFile = $relPath ? realpath($baseDir . '/' . $relPath) : false;
error_log('[media_serve.php] İstenen dosya: ' . $baseDir . '/' . $relPath);
error_log('[media_serve.php] Çözümlenen dosya: ' . ($targetFile ?: 'false'));
if (!$targetFile || !is_file($targetFile)) {
    $msg = 'Dosya bulunamadı veya erişim yok: ' . ($targetFile ?: ($baseDir . '/' . $relPath));
    error_log('[media_serve.php] ' . $msg);
    http_response_code(404);
    echo $msg;
    exit;
}

$ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
$mimeTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
    'webp' => 'image/webp',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
    'mkv' => 'video/x-matroska',
    'avi' => 'video/x-msvideo',
    'mov' => 'video/quicktime',
];
$mime = isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
header('Content-Type: ' . $mime);

$filesize = filesize($targetFile);
$start = 0;
$end = $filesize - 1;
$length = $filesize;
$httpRange = isset($_SERVER['HTTP_RANGE']) ? $_SERVER['HTTP_RANGE'] : false;
if ($httpRange) {
    if (preg_match('/bytes=(\d*)-(\d*)/', $httpRange, $matches)) {
        if ($matches[1] !== '') $start = intval($matches[1]);
        if ($matches[2] !== '') $end = intval($matches[2]);
        if ($end > $filesize - 1) $end = $filesize - 1;
        $length = $end - $start + 1;
        header('HTTP/1.1 206 Partial Content');
        header("Content-Range: bytes $start-$end/$filesize");
    }
}
header('Accept-Ranges: bytes');
header('Content-Length: ' . $length);

$fp = fopen($targetFile, 'rb');
if ($start > 0) fseek($fp, $start);
$bufferSize = 8192;
$bytesSent = 0;
while (!feof($fp) && $bytesSent < $length) {
    $toRead = min($bufferSize, $length - $bytesSent);
    $data = fread($fp, $toRead);
    echo $data;
    $bytesSent += strlen($data);
    if (connection_status() != CONNECTION_NORMAL) break;
}
fclose($fp);
