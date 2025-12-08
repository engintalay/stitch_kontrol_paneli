<?php
// setup.php - Veritabanı oluşturma ve sorun giderme scripti
header('Content-Type: text/plain; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

$dir = __DIR__;
$dbPath = $dir . '/chat.db';

echo "=== Sistem Kontrolü ===\n";
echo "PHP Sürümü: " . phpversion() . "\n";
echo "Mevcut Dizin: $dir\n";

// 1. İzin Kontrolü
echo "Dizin Yazılabilir mi? ";
if (is_writable($dir)) {
    echo "EVET (" . substr(sprintf('%o', fileperms($dir)), -4) . ")\n";
} else {
    echo "HAYIR (" . substr(sprintf('%o', fileperms($dir)), -4) . ")\n";
    echo "HATA: Lütfen bu dizine yazma izni verin (chmod 777 $dir)\n";
}

// 2. SQLite Extension Kontrolü
echo "SQLite3 Eklentisi Yüklü mü? ";
if (extension_loaded('sqlite3')) {
    echo "EVET\n";
} else {
    echo "HAYIR\n";
    echo "HATA: 'php-sqlite3' paketi yüklü değil. (sudo apt install php-sqlite3)\n";
    exit;
}

// 3. Veritabanı Oluşturma Denemesi
echo "\n=== Veritabanı Oluşturma ===\n";
try {
    $db = new SQLite3($dbPath);
    $db->enableExceptions(true);
    
    echo "Veritabanı bağlantısı başarılı.\n";
    
    $db->exec('CREATE TABLE IF NOT EXISTS conversations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        title TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    echo "Tablo 'conversations' kontrol edildi/oluşturuldu.\n";

    $db->exec('CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conversation_id INTEGER NOT NULL,
        role TEXT NOT NULL,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
    )');
    echo "Tablo 'messages' kontrol edildi/oluşturuldu.\n";
    
    // İzinleri ayarla (Herkes yazabilsin)
    @chmod($dbPath, 0666);
    echo "Veritabanı dosya izinleri 0666 olarak ayarlandı.\n";
    
    echo "\nİŞLEM BAŞARILI: Sohbet botu kullanıma hazır!\n";
    
} catch (Exception $e) {
    echo "\nKRİTİK HATA: " . $e->getMessage() . "\n";
}
?>
