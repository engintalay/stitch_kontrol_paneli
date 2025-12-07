<?php
// yapay_zeka_sohbet_botu/code.php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: /login.php');
    exit;
}

$title = 'Yapay Zeka Sohbet Botu';
$icon = 'smart_toy';
$description = 'Yapay zeka ile sohbet edin';

// Read system theme from settings.db
$systemTheme = 'auto';
try {
    $__db_tmp = new SQLite3(__DIR__ . '/../settings.db');
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
          <p>Burada <?= htmlspecialchars($title) ?> ile ilgili dinamik içerik yer alacak.</p>
        </div>
      </div>
    </main>
    <div class="flex justify-end p-4">
      <a href="../logout.php" class="px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">Çıkış</a>
    </div>
  </div>
</body>
</html>
