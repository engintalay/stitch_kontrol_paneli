<?php
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../../auth_check.php';
}
// thumb.php: Küçük resim üretir ve .cache klasöründe saklar
// Web: thumb.php?path=dosya/yolu.jpg&size=150
// Bash: php thumb.php /media/disk1/Diger/yedek/2025/2025-01/2025-01-20/2025-01-20_23-23-01_3826.webp 150

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("[thumb.php] PHP ERROR: $errstr in $errfile on line $errline");
    return false;
});

$cacheDir = __DIR__ . '/.cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

$isCli = (php_sapi_name() === 'cli');

if (!$isCli && isset($_GET['clear_cache'])) {
    $deleted = 0;
    foreach (array_merge(glob($cacheDir . '/*.jpg') ?: [], glob($cacheDir . '/*.webp') ?: []) as $file) {
        if (is_file($file) && unlink($file)) $deleted++;
    }
    echo "Cache temizlendi. Silinen dosya: $deleted";
    exit;
}

if ($isCli) {
    $targetFile = isset($argv[1]) ? $argv[1] : '';
    $size = isset($argv[2]) ? intval($argv[2]) : 250;
    $relPath = ltrim(str_replace(realpath('/media') . '/', '', $targetFile), '/');
    echo "[CLI] targetFile: $targetFile\n";
    echo "[CLI] size: $size\n";
    echo "[CLI] relPath: $relPath\n";
    echo "[CLI} md5 Target: $targetFile".'_'."$size)\n";
} else {
    // Medya kök dizinini ayar veritabanından oku
    $db = new SQLite3(__DIR__ . '/../../settings.db');
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare('SELECT setting_value FROM user_settings WHERE user_id = :user_id AND setting_key = :key');
    $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $stmt->bindValue(':key', 'media_root', SQLITE3_TEXT);
    $result = $stmt->execute();
    $mediaRoot = ($row = $result->fetchArray(SQLITE3_ASSOC)) ? $row['setting_value'] : '';
    if (!$mediaRoot) {
        $msg = 'media_root not set or empty';
        error_log('[thumb.php] ' . $msg);
        http_response_code(500);
        echo $msg;
        exit;
    }
    $mediaRoot = rtrim($mediaRoot, "/\\");
    error_log('[thumb.php] mediaRoot yolu: ' . $mediaRoot);
    // Göreli ise proje köküne göre çöz
    if ($mediaRoot[0] !== '/') {
        $projectRoot = realpath(__DIR__ . '/../..');
        error_log('[thumb.php] projectRoot: ' . ($projectRoot ?: 'false'));
        $baseDir = realpath($projectRoot . '/' . $mediaRoot);
    } else {
        $baseDir = realpath($mediaRoot);
    }
    error_log('[thumb.php] Çözümlenen baseDir yolu: ' . ($baseDir ?: 'false'));
    if (!$baseDir || !is_dir($baseDir)) {
        $msg = 'Medya kök dizini bulunamadı veya dizin değil: ' . $mediaRoot . ' (çözümlenen: ' . ($baseDir ?: 'false') . ')';
        error_log('[thumb.php] ' . $msg);
        http_response_code(500);
        echo $msg;
        exit;
    }
    if (!is_readable($baseDir)) {
        $msg = 'Medya kök dizinine okuma izni yok: ' . $baseDir;
        error_log('[thumb.php] ' . $msg);
        http_response_code(500);
        echo $msg;
        exit;
    }
    $relPath = isset($_GET['path']) ? ltrim(trim($_GET['path']), '/') : '';
    $size = isset($_GET['size']) ? intval($_GET['size']) : 250;
    $targetFile = $relPath ? realpath($baseDir . '/' . $relPath) : false;
    error_log('[thumb.php] İstenen dosya: ' . $baseDir . '/' . $relPath);
    error_log('[thumb.php] Çözümlenen dosya: ' . ($targetFile ?: 'false'));
    if (!$targetFile || !is_file($targetFile)) {
        $msg = 'Dosya bulunamadı veya erişim yok: ' . ($targetFile ?: ($baseDir . '/' . $relPath));
        error_log('[thumb.php] ' . $msg);
    }
}

if (!$targetFile || !is_file($targetFile)) {
    $msg = 'Dosya bulunamadı veya erişim yok: ' . ($targetFile ?: ($baseDir . '/' . $relPath));
    error_log('[thumb.php] ' . $msg);
    if ($isCli) {
        echo $msg . "\n";
        exit(1);
    } else {
        http_response_code(404);
        echo $msg;
        exit;
    }
}

if (!function_exists('imagecreatetruecolor')) {
    if ($isCli) {
        echo "GD kütüphanesi yüklü değil.\n";
        exit(2);
    } else {
        http_response_code(500);
        echo 'GD kütüphanesi yüklü değil.';
        exit;
    }
}

$ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
$imageExts = ['jpg','jpeg','png','gif','bmp','webp'];
$videoExts = ['mp4','mkv','webm','avi','mov','mpeg','mpg','ts'];

// Decide output format: use query param `format=webp` or Accept header if available
$outFormat = 'jpg';
if (isset($_GET['format']) && strtolower($_GET['format']) === 'webp') {
    $outFormat = 'webp';
} else {
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    if (strpos($accept, 'image/webp') !== false) $outFormat = 'webp';
}
$cacheExt = $outFormat === 'webp' ? '.webp' : '.jpg';
$cacheFile = $cacheDir . '/' . md5($targetFile . '_' . $size . '_' . $outFormat) . $cacheExt;
if (file_exists($cacheFile)) {
    if ($isCli) {
        echo "Cache dosyası zaten var: $cacheFile\n";
    } else {
        header('Content-Type: ' . ($outFormat === 'webp' ? 'image/webp' : 'image/jpeg'));
        readfile($cacheFile);
    }
    exit;
}

// If it's a video, try to generate the final cached thumbnail directly with ffmpeg (faster).
$tmpFrame = '';
if (in_array($ext, $videoExts)) {
    // Try direct ffmpeg -> cacheFile with scaling. Fall back to extracting a frame then GD if this fails.
    $ffmpegAvailable = false;
    exec('command -v ffmpeg', $ffpath, $frc);
    if ($frc === 0 && !empty($ffpath)) $ffmpegAvailable = true;

    if ($ffmpegAvailable) {
        // Build scale filter: set max dimension to $size while preserving aspect ratio
        $vf = "scale=if(gt(iw,ih),$size,-2):if(gt(ih,iw),-2,$size)";
        $vfArg = escapeshellarg($vf);
        // Determine seek point: try middle of the video via ffprobe if available
        $seek = 2;
        $duration = false;
        exec('command -v ffprobe', $pp, $prc);
        if ($prc === 0 && !empty($pp)) {
            $cmdDur = 'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($targetFile) . ' 2>&1';
            exec($cmdDur, $dout, $drc);
            if ($drc === 0 && !empty($dout) && is_numeric($dout[0])) {
                $duration = floatval($dout[0]);
                if ($duration > 4) {
                    $seek = max(1, round($duration / 2));
                } else {
                    $seek = 1;
                }
            }
        }
        // quality param for WebP
        $quality = isset($_GET['q']) ? intval($_GET['q']) : 75;
        if ($quality < 10) $quality = 10;
        if ($quality > 100) $quality = 100;

        // choose ffmpeg output options per desired format
        if ($outFormat === 'webp') {
            // use quality param (0-100) for webp
            $cmd = "ffmpeg -y -ss " . escapeshellarg($seek) . " -i " . escapeshellarg($targetFile) . " -vframes 1 -q:v " . escapeshellarg($quality) . " -vf " . $vfArg . " " . escapeshellarg($cacheFile) . " 2>&1";
        } else {
            $cmd = "ffmpeg -y -ss " . escapeshellarg($seek) . " -i " . escapeshellarg($targetFile) . " -vframes 1 -q:v 2 -vf " . $vfArg . " " . escapeshellarg($cacheFile) . " 2>&1";
        }
        exec($cmd, $out, $rc);
        if ($rc === 0 && file_exists($cacheFile)) {
            // success: directly created cacheFile with ffmpeg
            if ($isCli) {
                echo "ffmpeg doğrudan cache dosyası oluşturdu: $cacheFile\n";
            } else {
                header('Content-Type: ' . ($outFormat === 'webp' ? 'image/webp' : 'image/jpeg'));
                readfile($cacheFile);
            }
            exit;
        }
        // else fall through to tmpFrame extraction + GD
    }

    // Fallback: extract a single frame to a temporary file, then resize with GD
    $tmpFrame = $cacheDir . '/frame_' . md5($targetFile) . '.jpg';
    if (!file_exists($tmpFrame)) {
        $seek = 2; // seconds into the video to grab a frame
        $cmd = "ffmpeg -y -ss " . escapeshellarg($seek) . " -i " . escapeshellarg($targetFile) . " -vframes 1 -q:v 2 " . escapeshellarg($tmpFrame) . " 2>&1";
        exec($cmd, $out, $rc);
        if ($rc !== 0 || !file_exists($tmpFrame)) {
            if ($isCli) {
                echo "ffmpeg ile frame çıkarılamadı. Komut: $cmd\nÇıkış:\n" . implode("\n", $out) . "\n";
                exit(5);
            } else {
                http_response_code(500);
                echo 'Video dosyasından mini resim oluşturulamadı (ffmpeg hatası).';
                exit;
            }
        }
    }
    $sourceForResize = $tmpFrame;
} elseif (in_array($ext, $imageExts)) {
    $sourceForResize = $targetFile;
} else {
    if ($isCli) {
        echo "Desteklenmeyen dosya türü: $ext\n";
        exit(3);
    } else {
        http_response_code(415);
        echo 'Desteklenmeyen dosya türü.';
        exit;
    }
}

// Now create resized thumbnail from $sourceForResize using GD
list($w, $h) = getimagesize($sourceForResize);
$ratio = $w / $h;
if ($w > $h) {
    $newW = $size;
    $newH = round($size / $ratio);
} else {
    $newH = $size;
    $newW = round($size * $ratio);
}
$thumb = imagecreatetruecolor($newW, $newH);

switch (strtolower(pathinfo($sourceForResize, PATHINFO_EXTENSION))) {
    case 'jpg':
    case 'jpeg':
        $src = imagecreatefromjpeg($sourceForResize);
        break;
    case 'png':
        $src = imagecreatefrompng($sourceForResize);
        break;
    case 'gif':
        $src = imagecreatefromgif($sourceForResize);
        break;
    case 'bmp':
        $src = function_exists('imagecreatefrombmp') ? imagecreatefrombmp($sourceForResize) : false;
        break;
    case 'webp':
        $src = function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourceForResize) : false;
        break;
    default:
        $src = false;
}
if (!$src) {
    if ($isCli) {
        echo "Resim okunamadı veya GD kütüphanesi bu formatı desteklemiyor.\n";
        if ($tmpFrame && file_exists($tmpFrame)) unlink($tmpFrame);
        exit(4);
    } else {
        if ($tmpFrame && file_exists($tmpFrame)) unlink($tmpFrame);
        http_response_code(500);
        echo 'Resim okunamadı veya GD kütüphanesi bu formatı desteklemiyor.';
        exit;
    }
}
imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
// write output according to chosen format
if ($outFormat === 'webp' && function_exists('imagewebp')) {
    imagewebp($thumb, $cacheFile, 80);
} else {
    // fallback to jpeg
    imagejpeg($thumb, $cacheFile, 80);
}
if ($isCli) {
    echo "Thumb oluşturuldu: $cacheFile\n";
} else {
    header('Content-Type: ' . ($outFormat === 'webp' ? 'image/webp' : 'image/jpeg'));
    readfile($cacheFile);
}
imagedestroy($thumb);
imagedestroy($src);
if ($tmpFrame && file_exists($tmpFrame)) unlink($tmpFrame);
