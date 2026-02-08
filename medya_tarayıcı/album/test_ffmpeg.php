<?php
header('Content-Type: text/plain');
echo "FFmpeg Check\n";
echo "------------\n";

$path = '';
exec('which ffmpeg', $output, $returnCode);
if ($returnCode === 0) {
    echo "FFmpeg path (which): " . $output[0] . "\n";
} else {
    echo "FFmpeg not in PATH for 'which'\n";
}

$output2 = [];
exec('ffmpeg -version 2>&1', $output2, $returnCode2);
if ($returnCode2 === 0) {
    echo "FFmpeg version output: " . $output2[0] . "\n";
} else {
    echo "FFmpeg -version failed with code $returnCode2\n";
    echo "Output: " . implode("\n", $output2) . "\n";
}

echo "\nSystem info:\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "User: " . exec('whoami') . "\n";
echo "Current Dir: " . __DIR__ . "\n";
echo "Current Dir Writable: " . (is_writable(__DIR__) ? 'Yes' : 'No') . "\n";
$dbPath = __DIR__ . '/thumbnails.db';
echo "thumbnails.db path: $dbPath\n";
echo "thumbnails.db exists: " . (file_exists($dbPath) ? 'Yes' : 'No') . "\n";
if (file_exists($dbPath)) {
    echo "thumbnails.db Writable: " . (is_writable($dbPath) ? 'Yes' : 'No') . "\n";
}

echo "\nPHP info:\n";
echo "Safe mode (deprecated): " . (ini_get('safe_mode') ? 'on' : 'off') . "\n";
echo "Disabled functions: " . ini_get('disable_functions') . "\n";
?>