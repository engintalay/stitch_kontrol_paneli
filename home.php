<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: login.php');
    exit;
}
function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
// SQLite ile modül veritabanı
$db = new SQLite3('modules.db');
$db->exec('CREATE TABLE IF NOT EXISTS modules (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  icon TEXT NOT NULL,
  description TEXT NOT NULL,
  link TEXT NOT NULL,
  admin_only INTEGER DEFAULT 0
)');
// Eğer hiç modül yoksa örnek modülleri ekle
$res = $db->querySingle('SELECT COUNT(*) FROM modules');
if ($res == 0) {
  $db->exec("INSERT INTO modules (title, icon, description, link, admin_only) VALUES
    ('Kontrol Paneli', 'settings', 'Sistem ayarlarını yönetin', 'kontrol_paneli/code.php', 0),
    ('Medya Tarayıcı', 'perm_media', 'Medya dosyalarınızı yönetin', 'medya_tarayıcı/code.php', 0),
    ('Sistem Monitörü', 'monitor_heart', 'Sistem durumunu izleyin', 'sistem_monitörü/code.php', 0),
    ('Yapay Zeka Sohbet Botu', 'smart_toy', 'Yapay zeka ile sohbet edin', 'yapay_zeka_sohbet_botu/code.php', 0),
    ('Sunucu Kontrolü', 'settings', 'Sunucu özelliklerini görüntüleyin', 'server_check.php', 1),
    ('Admin Paneli', 'admin_panel_settings', 'Yönetici işlemleri', 'admin_panel.php', 1)
  ");
}
$modules = [];
$is_admin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$res = $db->query('SELECT * FROM modules');
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
  if ($row['admin_only'] && !$is_admin) continue;
  $modules[] = $row;
}

// Read system theme from settings.db
$systemTheme = 'auto';
try {
    $__db_tmp = new SQLite3('settings.db');
    $__stmt_theme = $__db_tmp->prepare('SELECT setting_value FROM settings WHERE setting_key = :key');
    $__stmt_theme->bindValue(':key', 'system_theme', SQLITE3_TEXT);
    $__res_theme = $__stmt_theme->execute();
    $__row_theme = $__res_theme->fetchArray(SQLITE3_ASSOC);
    if ($__row_theme && !empty($__row_theme['setting_value'])) {
        $systemTheme = $__row_theme['setting_value'];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html <?= $systemTheme === 'dark' ? 'class="dark"' : '' ?> lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Kontrol Paneli - Ana Sayfa</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
  <script>
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
  <?php if ($systemTheme === 'auto'): ?>
  <script>
    // Apply dark class if user's OS prefers dark mode
    (function(){
      try {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
          document.documentElement.classList.add('dark');
        }
      } catch (e) {}
    })();
  </script>
  <?php endif; ?>
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
<body class="bg-background-light dark:bg-background-dark font-display min-h-screen flex items-center justify-center">
  <div class="w-full max-w-2xl p-8 bg-white dark:bg-[#181c23] rounded-xl shadow-lg">
    <div class="flex flex-col items-center mb-8">
      <span class="material-symbols-outlined text-primary text-6xl mb-2">dashboard</span>
      <h1 class="text-3xl font-bold text-primary mb-2">Kontrol Paneli</h1>
      <p class="text-gray-500 dark:text-gray-400 text-base">Hoşgeldiniz! Aşağıdan sistem modüllerine erişebilirsiniz.</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <?php foreach ($modules as $mod): ?>
      <a href="<?=h($mod['link'])?>" class="flex flex-col items-center p-6 bg-background-light dark:bg-[#23272f] rounded-lg shadow hover:shadow-lg transition">
        <span class="material-symbols-outlined text-4xl text-primary mb-2"><?=h($mod['icon'])?></span>
        <span class="font-semibold text-lg text-primary"><?=h($mod['title'])?></span>
        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center"><?=h($mod['description'])?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
