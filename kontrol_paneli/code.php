<?php
// kontrol_paneli/code.php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: /login.php');
    exit;
}

// Database setup for user-specific settings
$db = new SQLite3(__DIR__ . '/../settings.db');
$db->exec('CREATE TABLE IF NOT EXISTS user_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id TEXT NOT NULL,
    setting_key TEXT NOT NULL,
    setting_value TEXT NOT NULL,
    UNIQUE(user_id, setting_key)
)');
// Ensure a global settings table exists for system-wide values
$db->exec('CREATE TABLE IF NOT EXISTS settings (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  setting_key TEXT NOT NULL UNIQUE,
  setting_value TEXT NOT NULL
)');

// Handle media_root setting
$userId = $_SESSION['user_id'];
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['media_root'])) {
    $mediaRoot = trim($_POST['media_root']);
    $stmt = $db->prepare('INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES (:user_id, :key, :value)
                          ON CONFLICT(user_id, setting_key) DO UPDATE SET setting_value = excluded.setting_value');
    $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
    $stmt->bindValue(':key', 'media_root', SQLITE3_TEXT);
    $stmt->bindValue(':value', $mediaRoot, SQLITE3_TEXT);
    if ($stmt->execute()) {
        $message = 'Medya root dizini başarıyla kaydedildi!';
    } else {
        $errorInfo = $db->lastErrorMsg();
        $message = 'Medya root dizini kaydedilemedi! Hata: ' . htmlspecialchars($errorInfo);
    }
}

  // Handle system theme setting (system-wide)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['system_theme'])) {
    $theme = in_array($_POST['system_theme'], ['light', 'dark', 'auto']) ? $_POST['system_theme'] : 'auto';
    $stmt = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
                ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value');
    $stmt->bindValue(':key', 'system_theme', SQLITE3_TEXT);
    $stmt->bindValue(':value', $theme, SQLITE3_TEXT);
    if ($stmt->execute()) {
      $message = 'Sistem teması kaydedildi: ' . htmlspecialchars($theme);
    } else {
      $message = 'Sistem teması kaydedilemedi: ' . htmlspecialchars($db->lastErrorMsg());
    }
  }
$stmt = $db->prepare('SELECT setting_value FROM user_settings WHERE user_id = :user_id AND setting_key = :key');
$stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
$stmt->bindValue(':key', 'media_root', SQLITE3_TEXT);
$result = $stmt->execute();
$currentMediaRoot = ($row = $result->fetchArray(SQLITE3_ASSOC)) ? $row['setting_value'] : '';

// Load current system theme
$stmtTheme = $db->prepare('SELECT setting_value FROM settings WHERE setting_key = :key');
$stmtTheme->bindValue(':key', 'system_theme', SQLITE3_TEXT);
$resTheme = $stmtTheme->execute();
$currentSystemTheme = ($r = $resTheme->fetchArray(SQLITE3_ASSOC)) ? $r['setting_value'] : 'auto';

// Handle build index request (bulk index)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['build_index'])) {
  $msg = '';
  if (empty($currentMediaRoot)) {
    $message = 'Medya root dizini ayarlı değil. Lütfen önce Medya Root dizinini ayarlayın.';
  } else {
    // Resolve base dir similar to thumb.php
    $mediaRoot = rtrim($currentMediaRoot, "/\\");
    if ($mediaRoot[0] !== '/') {
      $projectRoot = realpath(__DIR__ . '/../..');
      $baseDir = realpath($projectRoot . '/' . $mediaRoot);
    } else {
      $baseDir = realpath($mediaRoot);
    }
    if (!$baseDir || !is_dir($baseDir) || !is_readable($baseDir)) {
      $message = 'Medya kök dizini çözümlenemedi veya okunamıyor: ' . htmlspecialchars($currentMediaRoot);
    } else {
      // Scan files
      $imageExts = ['jpg','jpeg','png','gif','bmp','webp'];
      $videoExts = ['mp4','mkv','webm','avi','mov','mpeg','mpg','ts'];
      $allowed = array_merge($imageExts, $videoExts);
      $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS));
      $results = [];
      foreach ($rii as $file) {
        if ($file->isFile()) {
          $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
          if (in_array($ext, $allowed)) {
            $real = $file->getRealPath();
            // Normalize path to start with /media/ if baseDir contains '/media'
            $results[$real] = [];
          }
        }
      }
      // Write scan_results.json in album folder
      $scanFile = __DIR__ . '/../medya_tarayıcı/album/scan_results.json';
      $tmp = $scanFile . '.tmp';
      if (file_put_contents($tmp, json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
        rename($tmp, $scanFile);
        $message = 'Indexleme tamamlandı. Toplam dosya: ' . count($results);
      } else {
        $message = 'Indexleme sırasında yazma hatası oluştu.';
      }
    }
  }
}

$title = 'Kontrol Paneli';
$icon = 'settings';
$description = 'Sistem ayarlarını yönetin';
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title><?= htmlspecialchars($title) ?></title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#135bec",
            "background-light": "#f6f6f8",
            "background-dark": "#101622",
          },
          fontFamily: {
            "display": ["Space Grotesk", "sans-serif"]
          },
          borderRadius: {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
          },
        },
      },
    }
  </script>
  <style>
    .material-symbols-outlined {
      font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
    }
  </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
  <div class="flex flex-col min-h-screen">
    <header class="flex items-center justify-between p-4 bg-white/80 dark:bg-[#181c23] shadow">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-3xl">dashboard</span>
        <span class="font-bold text-xl text-primary"><?= htmlspecialchars($title) ?></span>
      </div>
      <a href="../home.php" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
    </header>
    <main class="flex-1 p-8">
      <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
          <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($icon) ?></span>
          <?= htmlspecialchars($title) ?>
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6"><?= htmlspecialchars($description) ?></p>
        <!-- Dinamik içerik buraya gelecek -->
        <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-6">
          <?php if ($message): ?>
            <p class="text-sm font-medium text-red-500 dark:text-red-400 mb-4"> <?= htmlspecialchars($message) ?> </p>
          <?php endif; ?>
          <form method="POST" class="space-y-4">
            <label for="media_root" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Medya Root Dizini</label>
            <input type="text" id="media_root" name="media_root" value="<?= htmlspecialchars($currentMediaRoot) ?>" class="form-input w-full" placeholder="/path/to/media" required />
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Kaydet</button>
          </form>
          <hr class="my-6" />
          <!-- Index status and control -->
          <div class="mt-4">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">Medya Index Durumu</h3>
            <?php
              // Calculate indexed/total counts
              $indexed = 0;
              $totalFiles = 0;
              $scanFile = __DIR__ . '/../medya_tarayıcı/album/scan_results.json';
              if (file_exists($scanFile)) {
                $data = json_decode(file_get_contents($scanFile), true);
                if (is_array($data)) $indexed = count($data);
              }
              if (!empty($currentMediaRoot)) {
                // resolve baseDir similar to above
                $mediaRoot = rtrim($currentMediaRoot, "/\\");
                if ($mediaRoot[0] !== '/') {
                    $projectRoot = realpath(__DIR__ . '/../..');
                    $baseDir = realpath($projectRoot . '/' . $mediaRoot);
                } else {
                    $baseDir = realpath($mediaRoot);
                }
                if ($baseDir && is_dir($baseDir) && is_readable($baseDir)) {
                  $exts = ['jpg','jpeg','png','gif','bmp','webp','mp4','mkv','webm','avi','mov','mpeg','mpg','ts'];
                  $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS));
                  foreach ($it as $f) {
                      if ($f->isFile()) {
                          $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
                          if (in_array($ext, $exts)) $totalFiles++;
                      }
                  }
                }
              }
              $remaining = max(0, $totalFiles - $indexed);
            ?>
            <div class="grid grid-cols-3 gap-4 mb-4">
              <div class="p-3 bg-gray-50 dark:bg-[#1f2937] rounded">
                <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Medya Dosyası</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $totalFiles ?></div>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-[#1f2937] rounded">
                <div class="text-sm text-gray-500 dark:text-gray-400">Indexlenen</div>
                <div class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?= $indexed ?></div>
              </div>
              <div class="p-3 bg-gray-50 dark:bg-[#1f2937] rounded">
                <div class="text-sm text-gray-500 dark:text-gray-400">Kalan</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $remaining ?></div>
              </div>
            </div>
            <form method="POST">
              <input type="hidden" name="build_index" value="1" />
              <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Toplu Indexleme Başlat</button>
            </form>
          </div>
          <hr class="my-6" />
          <form method="POST" class="space-y-4">
            <label for="system_theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sistem Teması</label>
            <select id="system_theme" name="system_theme" class="form-select w-full">
              <option value="auto" <?= $currentSystemTheme === 'auto' ? 'selected' : '' ?>>Otomatik (tercih edilen renk düzenini kullan)</option>
              <option value="light" <?= $currentSystemTheme === 'light' ? 'selected' : '' ?>>Açık</option>
              <option value="dark" <?= $currentSystemTheme === 'dark' ? 'selected' : '' ?>>Koyu</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Temayı Kaydet</button>
          </form>
        </div>
      </div>
    </main>
    <div class="flex justify-end p-4">
      <a href="../logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">Çıkış</a>
    </div>
  </div>
</body>
</html>
