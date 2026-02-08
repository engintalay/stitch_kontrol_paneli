<?php
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../../auth_check.php';
}
// thumb.php: Küçük resimleri SQLite veritabanında saklar
// Web: thumb.php?path=dosya/yolu.jpg&size=150
// Bash: php thumb.php /media/disk1/Diger/yedek/2025/2025-01/2025-01-20/2025-01-20_23-23-01_3826.webp 150

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("[thumb.php] PHP ERROR: $errstr in $errfile on line $errline");
    return false;
});

// Veritabanı başlatma
$dbPath = __DIR__ . '/thumbnails.db';
$db = new SQLite3($dbPath);
$db->exec('CREATE TABLE IF NOT EXISTS thumbnails (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    original_path TEXT NOT NULL,
    filename TEXT NOT NULL,
    size INTEGER NOT NULL,
    format TEXT NOT NULL,
    data BLOB NOT NULL,
    mtime INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(original_path, size, format)
)');

$isCli = (php_sapi_name() === 'cli');

if ($isCli) {
    $targetFile = isset($argv[1]) ? $argv[1] : '';
    $size = isset($argv[2]) ? intval($argv[2]) : 250;
    // CLI modunda da baseDir belirlemek gerekebilir, ancak doğrudan path verilmişse onu kullanırız
    echo "[CLI] targetFile: $targetFile\n";
    echo "[CLI] size: $size\n";
} else {
    // Medya kök dizinini ayar veritabanından oku
    $settingsDb = new SQLite3(__DIR__ . '/../../settings.db');
    $userId = $_SESSION['user_id'];
    $stmt = $settingsDb->prepare('SELECT setting_value FROM user_settings WHERE user_id = :user_id AND setting_key = :key');
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

    // Göreli ise proje köküne göre çöz
    if ($mediaRoot[0] !== '/') {
        $projectRoot = realpath(__DIR__ . '/../..');
        $baseDir = realpath($projectRoot . '/' . $mediaRoot);
    } else {
        $baseDir = realpath($mediaRoot);
    }

    if (!$baseDir || !is_dir($baseDir)) {
        $msg = 'Medya kök dizini bulunamadı: ' . $mediaRoot;
        error_log('[thumb.php] ' . $msg);
        http_response_code(500);
        echo $msg;
        exit;
    }

    // Cache temizleme (Veritabanı bazlı)
    if (isset($_GET['clear_cache'])) {
        $clearRel = isset($_GET['path']) ? ltrim(trim($_GET['path']), '/') : '';
        if ($clearRel === '') {
            $db->exec('DELETE FROM thumbnails');
            echo "Tüm cache temizlendi.";
        } else {
            $absClearDir = realpath($baseDir . '/' . $clearRel);
            if ($absClearDir) {
                // Bu klasör altındaki tüm kayıtları sil
                $stmt = $db->prepare('DELETE FROM thumbnails WHERE original_path LIKE :pattern');
                $stmt->bindValue(':pattern', $absClearDir . '%', SQLITE3_TEXT);
                $stmt->execute();
                echo "Klasöre özel cache temizlendi.";
            } else {
                http_response_code(400);
                echo 'Geçersiz clear_cache path';
            }
        }
        exit;
    }

    $relPath = isset($_GET['path']) ? ltrim(trim($_GET['path']), '/') : '';
    $size = isset($_GET['size']) ? intval($_GET['size']) : 250;
    $targetFile = $relPath ? realpath($baseDir . '/' . $relPath) : false;
}

if (!$targetFile || !is_file($targetFile)) {
    $msg = 'Dosya bulunamadı: ' . ($targetFile ?: 'geçersiz path');
    if ($isCli) {
        echo $msg . "\n";
        exit(1);
    } else {
        http_response_code(404);
        echo $msg;
        exit;
    }
}

// Çıktı formatı belirleme
$outFormat = 'jpg';
if (isset($_GET['format']) && strtolower($_GET['format']) === 'webp') {
    $outFormat = 'webp';
} else {
    $accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '';
    if (strpos($accept, 'image/webp') !== false)
        $outFormat = 'webp';
}

// Orijinal dosyanın mtime bilgisini al (Cache kontrolü için)
if (!file_exists($targetFile)) {
    error_log("[thumb.php] HATA: Target file bulunamadı: " . $targetFile);
}
$currentMtime = filemtime($targetFile);
$filename = basename($targetFile);

// Veritabanından kontrol et
$stmt = $db->prepare('SELECT data, mtime FROM thumbnails WHERE original_path = :path AND size = :size AND format = :format');
$stmt->bindValue(':path', $targetFile, SQLITE3_TEXT);
$stmt->bindValue(':size', $size, SQLITE3_INTEGER);
$stmt->bindValue(':format', $outFormat, SQLITE3_TEXT);
$res = $stmt->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

if ($row) {
    if ($row['mtime'] === $currentMtime) {
        if (!$isCli) {
            header('Content-Type: ' . ($outFormat === 'webp' ? 'image/webp' : 'image/jpeg'));
            echo $row['data'];
        } else {
            echo "Cache bulundu (veritabanından).\n";
        }
        exit;
    } else {
        // Dosya değişmiş, eski kaydı sil
        $stmt = $db->prepare('DELETE FROM thumbnails WHERE original_path = :path AND size = :size AND format = :format');
        $stmt->bindValue(':path', $targetFile, SQLITE3_TEXT);
        $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
        $stmt->bindValue(':format', $outFormat, SQLITE3_TEXT);
        $stmt->execute();
    }
}

// Resim/Video Mini Resim Oluşturma Mantığı
if (!function_exists('imagecreatetruecolor')) {
    error_log("[thumb.php] HATA: GD kütüphanesi yüklü değil.");
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
$imageExts = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
$videoExts = ['mp4', 'mkv', 'webm', 'avi', 'mov', 'mpeg', 'mpg', 'ts'];

$tmpCacheFile = __DIR__ . '/.tmp_thumb_' . md5($targetFile . $size . $outFormat);
if (file_exists($tmpCacheFile))
    unlink($tmpCacheFile);

// Eğer video ise FFmpeg kullan
if (in_array($ext, $videoExts)) {
    $vf = "scale=if(gt(iw,ih),$size,-2):if(gt(ih,iw),-2,$size)";
    $seek = 2; // Saniye
    $quality = isset($_GET['q']) ? intval($_GET['q']) : 75;

    // Doğrudan FFmpeg ile oluşturmayı dene
    if ($outFormat === 'webp') {
        $cmd = "ffmpeg -y -ss " . escapeshellarg($seek) . " -i " . escapeshellarg($targetFile) . " -vframes 1 -q:v " . escapeshellarg($quality) . " -vf " . escapeshellarg($vf) . " " . escapeshellarg($tmpCacheFile) . " 2>&1";
    } else {
        $cmd = "ffmpeg -y -ss " . escapeshellarg($seek) . " -i " . escapeshellarg($targetFile) . " -vframes 1 -q:v 2 -vf " . escapeshellarg($vf) . " " . escapeshellarg($tmpCacheFile) . " 2>&1";
    }
    error_log("[thumb.php] Video isleniyor. Komut: $cmd");
    exec($cmd, $ffOut, $ffRc);

    if ($ffRc !== 0 || !file_exists($tmpCacheFile)) {
        error_log("[thumb.php] FFmpeg hatası ($ffRc). Çıkış: " . implode("\n", $ffOut));
        // Hata durumunda (GD ile işlemek için kare çıkar)
        $cmdFrame = "ffmpeg -y -ss " . escapeshellarg($seek) . " -i " . escapeshellarg($targetFile) . " -vframes 1 -q:v 2 " . escapeshellarg($tmpCacheFile) . " 2>&1";
        error_log("[thumb.php] Kare cikariliyor: $cmdFrame");
        exec($cmdFrame, $ffOut2, $ffRc2);
        if ($ffRc2 !== 0) {
            error_log("[thumb.php] Kare cıkarma hatası ($ffRc2). Çıkış: " . implode("\n", $ffOut2));
        }
    }
}

// GD ile işleme (eğer FFmpeg doğrudan çıktı üretmediyse veya resim dosyasıysa)
if (!file_exists($tmpCacheFile) && in_array($ext, $imageExts)) {
    list($w, $h) = getimagesize($targetFile);
    if ($w > 0 && $h > 0) {
        $ratio = $w / $h;
        if ($w > $h) {
            $newW = $size;
            $newH = round($size / $ratio);
        } else {
            $newH = $size;
            $newW = round($size * $ratio);
        }

        $thumb = imagecreatetruecolor($newW, $newH);
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($targetFile);
                break;
            case 'png':
                $src = imagecreatefrompng($targetFile);
                break;
            case 'gif':
                $src = imagecreatefromgif($targetFile);
                break;
            case 'webp':
                $src = imagecreatefromwebp($targetFile);
                break;
            default:
                $src = false;
        }

        if ($src) {
            imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
            if ($outFormat === 'webp' && function_exists('imagewebp')) {
                imagewebp($thumb, $tmpCacheFile, 80);
            } else {
                imagejpeg($thumb, $tmpCacheFile, 80);
            }
            imagedestroy($thumb);
            imagedestroy($src);
        }
    }
}

// Veritabanına kaydet ve dosyayı oku
if (file_exists($tmpCacheFile)) {
    $binaryData = file_get_contents($tmpCacheFile);
    $stmt = $db->prepare('INSERT OR REPLACE INTO thumbnails (original_path, filename, size, format, data, mtime) VALUES (:path, :filename, :size, :format, :data, :mtime)');
    $stmt->bindValue(':path', $targetFile, SQLITE3_TEXT);
    $stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
    $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
    $stmt->bindValue(':format', $outFormat, SQLITE3_TEXT);
    $stmt->bindValue(':data', $binaryData, SQLITE3_BLOB);
    $stmt->bindValue(':mtime', $currentMtime, SQLITE3_INTEGER);
    $stmt->execute();

    unlink($tmpCacheFile);

    if (!$isCli) {
        header('Content-Type: ' . ($outFormat === 'webp' ? 'image/webp' : 'image/jpeg'));
        echo $binaryData;
    } else {
        echo "Thumb oluşturuldu ve veritabanına kaydedildi.\n";
    }
} else {
    if (!$isCli) {
        http_response_code(500);
        echo 'Mini resim oluşturulamadı.';
    } else {
        echo "Hata: Mini resim oluşturulamadı.\n";
    }
}
