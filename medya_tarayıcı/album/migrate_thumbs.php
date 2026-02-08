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
$total = count($metaFiles);
echo "Toplam meta dosyası bulundu: $total\n";

$migrated = 0;
$errors = 0;
$skipped = 0;

foreach ($metaFiles as $index => $metaFile) {
    $current = $index + 1;
    $percent = round(($current / $total) * 100);

    $meta = json_decode(file_get_contents($metaFile), true);
    if (!$meta || !isset($meta['target'])) {
        echo "[$current/$total] [%$percent] Hatalı meta dosyası: $metaFile\n";
        $errors++;
        continue;
    }

    $originalPath = $meta['target'];
    $size = isset($meta['size']) ? $meta['size'] : 250;
    $format = isset($meta['format']) ? $meta['format'] : 'jpg';

    // Cache dosyasının adını bul (meta dosyasının .meta ekini atarak)
    $cacheFile = substr($metaFile, 0, -5);

    if (!file_exists($cacheFile)) {
        echo "[$current/$total] [%$percent] Cache dosyası bulunamadı: $cacheFile\n";
        $errors++;
        continue;
    }

    if (!file_exists($originalPath)) {
        echo "[$current/$total] [%$percent] Orijinal dosya yok, siliniyor: $originalPath\n";
        @unlink($metaFile);
        @unlink($cacheFile);
        $skipped++;
        continue;
    }

    // Veritabanında zaten var mı kontrol et (Resume/Idempotency için ek önlem)
    $checkStmt = $db->prepare('SELECT id FROM thumbnails WHERE original_path = :path AND size = :size AND format = :format');
    $checkStmt->bindValue(':path', $originalPath, SQLITE3_TEXT);
    $checkStmt->bindValue(':size', $size, SQLITE3_INTEGER);
    $checkStmt->bindValue(':format', $format, SQLITE3_TEXT);
    $checkRes = $checkStmt->execute();
    if ($checkRes->fetchArray(SQLITE3_ASSOC)) {
        echo "[$current/$total] [%$percent] Zaten veritabanında var: " . basename($originalPath) . "\n";
        @unlink($metaFile);
        @unlink($cacheFile);
        $skipped++;
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
        echo "[$current/$total] [%$percent] Başarıyla taşındı: $filename\n";
    } else {
        echo "[$current/$total] [%$percent] Veritabanına kaydedilemedi: $filename\n";
        $errors++;
    }
}

echo "\nGöç tamamlandı.\n";
echo "Toplam: $total\n";
echo "Başarıyla taşınan: $migrated\n";
echo "Atlanan/Zaten var olan: $skipped\n";
echo "Hata sayısı: $errors\n";

// Boşsa .cache dizinini silmeyi deneyelim
if (count(glob($cacheDir . '/*')) === 0) {
    @rmdir($cacheDir);
    echo ".cache dizini boşaltıldı ve silindi.\n";
}
