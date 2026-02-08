<?php
// migrate_thumbs.php: Eski dosya tabanlı cache verilerini SQLite veritabanına taşır.

error_reporting(E_ALL);
ini_set('display_errors', 1);

$cacheDir = __DIR__ . '/.cache';
$dbPath = __DIR__ . '/thumbnails.db';

if (!is_dir($cacheDir)) {
    echo "Eski cache dizini bulunamadı: $cacheDir\n";
    exit;
}

if (!class_exists('SQLite3')) {
    echo "SQLite3 sınıfı bulunamadı. Lütfen PHP SQLite3 eklentisinin kurulu olduğundan emin olun.\n";
    exit;
}

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

$metaFiles = glob($cacheDir . '/*.meta');
echo "Toplam meta dosyası bulundu: " . count($metaFiles) . "\n";

$migrated = 0;
$errors = 0;

foreach ($metaFiles as $metaFile) {
    $meta = json_decode(file_get_contents($metaFile), true);
    if (!$meta || !isset($meta['target'])) {
        echo "Hatalı meta dosyası: $metaFile\n";
        $errors++;
        continue;
    }

    $originalPath = $meta['target'];
    $size = isset($meta['size']) ? $meta['size'] : 250;
    $format = isset($meta['format']) ? $meta['format'] : 'jpg';

    // Cache dosyasının adını bul (meta dosyasının .meta ekini atarak)
    $cacheFile = substr($metaFile, 0, -5);

    if (!file_exists($cacheFile)) {
        echo "Cache dosyası bulunamadı: $cacheFile\n";
        $errors++;
        continue;
    }

    if (!file_exists($originalPath)) {
        echo "Orijinal dosya artık yok, atlanıyor: $originalPath\n";
        // İsterseniz burada hem cache hem meta dosyasını silebilirsiniz
        @unlink($metaFile);
        @unlink($cacheFile);
        continue;
    }

    $binaryData = file_get_contents($cacheFile);
    $currentMtime = filemtime($originalPath);
    $filename = basename($originalPath);

    $stmt = $db->prepare('INSERT OR REPLACE INTO thumbnails (original_path, filename, size, format, data, mtime) VALUES (:path, :filename, :size, :format, :data, :mtime)');
    $stmt->bindValue(':path', $originalPath, SQLITE3_TEXT);
    $stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
    $stmt->bindValue(':size', $size, SQLITE3_INTEGER);
    $stmt->bindValue(':format', $format, SQLITE3_TEXT);
    $stmt->bindValue(':data', $binaryData, SQLITE3_BLOB);
    $stmt->bindValue(':mtime', $currentMtime, SQLITE3_INTEGER);

    if ($stmt->execute()) {
        $migrated++;
        @unlink($metaFile);
        @unlink($cacheFile);
    } else {
        echo "Veritabanına kaydedilemedi: $originalPath\n";
        $errors++;
    }
}

echo "Göç tamamlandı.\n";
echo "Başarıyla taşınan: $migrated\n";
echo "Hata sayısı: $errors\n";

// Boşsa .cache dizinini silmeyi deneyelim
if (count(glob($cacheDir . '/*')) === 0) {
    @rmdir($cacheDir);
    echo ".cache dizini boşaltıldı ve silindi.\n";
}
