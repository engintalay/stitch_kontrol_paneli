<?php
// media_serve.php
// /media altındaki dosyaları güvenli şekilde sunar
$baseDir = realpath(__DIR__ . '/../media');
$relPath = isset($_GET['path']) ? $_GET['path'] : '';
$relPath = trim($relPath, '/');
$filePath = $relPath ? "/media/".$relPath : false;

if (!$filePath || strpos($filePath, $baseDir) !== 0 || !is_file($filePath)) {
    http_response_code(404);
    echo 'Dosya bulunamadı veya erişim yok.<br>';
    echo $filePath;
    exit;
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
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

$filesize = filesize($filePath);
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

$fp = fopen($filePath, 'rb');
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
