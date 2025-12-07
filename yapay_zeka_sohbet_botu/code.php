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
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
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
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
  <div class="flex h-screen w-full">
    <!-- SideNavBar -->
    <aside class="flex w-64 flex-col border-r border-gray-200 dark:border-gray-800 bg-background-light dark:bg-[#111318] p-4">
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3">
          <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" 
               data-alt="AI Bot logo" 
               style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuBovY124WQr5pvSuwPlMDT_3dqgf_WH-6MMvsSMBxo5-30JlBS3h3ORtQ_0h9Co4ZvWNr1O4clfQ_uXRdxfVHlsswV-mjUp6tbdvoq1iKoyghN9-0JLxgbHpp2OcnlECnjohnpEJU99z7T_49SzkpIb1lU4JkBM_1AjUs12YV9x_rs1y_UHsuzKNTXlirlWq72P2lfxalfMnw4lj-_gzR0LpsN2qdPGB8mfF2mteudCVHl2cURzradG05Cgz-rSGlnuSbtcEKvrVaU");'>
          </div>
          <div class="flex flex-col">
            <h1 class="text-base font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($title) ?></h1>
            <p class="text-sm font-normal text-gray-500 dark:text-[#9da6b9]">Sohbet Geçmişi</p>
          </div>
        </div>
      </div>
      <div class="flex flex-1 flex-col justify-between mt-6">
        <nav class="flex flex-col gap-2">
          <a class="flex items-center gap-3 rounded-lg bg-primary/10 dark:bg-[#282e39] px-3 py-2" href="#">
            <span class="material-symbols-outlined text-primary dark:text-white text-base">chat</span>
            <p class="text-sm font-medium text-primary dark:text-white">Python Fonksiyonları</p>
          </a>
          <a class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800/50" href="#">
            <span class="material-symbols-outlined text-gray-500 dark:text-white text-base">chat</span>
            <p class="text-sm font-medium text-gray-700 dark:text-white">Haftasonu Gezi Planı</p>
          </a>
          <a class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800/50" href="#">
            <span class="material-symbols-outlined text-gray-500 dark:text-white text-base">chat</span>
            <p class="text-sm font-medium text-gray-700 dark:text-white">Yeni bir tarif</p>
          </a>
        </nav>
        <div class="flex flex-col gap-2">
          <button class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90">
            <span class="truncate">Yeni Sohbet</span>
          </button>
          <a class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800/50" href="../admin_panel.php">
            <span class="material-symbols-outlined text-gray-500 dark:text-white text-base">settings</span>
            <p class="text-sm font-medium text-gray-700 dark:text-white">Ayarlar (Admin)</p>
          </a>
          <a class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800/50" href="../home.php">
            <span class="material-symbols-outlined text-gray-500 dark:text-white text-base">home</span>
            <p class="text-sm font-medium text-gray-700 dark:text-white">Ana Sayfa</p>
          </a>
          <a class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600" href="../logout.php">
             <span class="material-symbols-outlined text-base">logout</span>
             <p class="text-sm font-medium">Çıkış</p>
          </a>
        </div>
      </div>
    </aside>
    <!-- Main Chat Area -->
    <main class="flex flex-1 flex-col">
      <header class="flex h-16 items-center border-b border-gray-200 dark:border-gray-800 px-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Python Fonksiyonları</h2>
        <div class="ml-auto flex items-center gap-4">
          <button class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white">
            <span class="material-symbols-outlined">more_horiz</span>
          </button>
        </div>
      </header>
      <div class="flex flex-1 flex-col overflow-y-auto p-6">
        <div class="flex-1 space-y-6">
          <!-- User Message -->
          <div class="flex items-start gap-4 justify-end">
            <div class="flex flex-col items-end gap-2 rounded-xl rounded-br-none bg-primary/10 dark:bg-primary/20 p-4 max-w-xl">
              <div class="flex items-center gap-2">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Siz</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">10:40</p>
              </div>
              <p class="text-base font-normal leading-relaxed text-gray-800 dark:text-gray-200">Bana Python'da bir fonksiyonun nasıl tanımlandığını gösterebilir misin?</p>
            </div>
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" 
                 data-alt="User avatar" 
                 style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAE3n2lFPFJl1o4E0DoxazyKuzW2PUen7MuLYASsGUMZrN0lvsIQlx7WSiMsWgXiLlIXfDreyNL01VIAJLS05M2HgbYL6D-thLZG24UZuzIqxZnQNk--Wx2ps8GUT3IKWnZYPlOegUcS8oFZ0Fnr28oR4Efw-dtnPtQBzBPryiuwMrvEUJs7Ec1_Vo_WsOqdiP4vTh0T-DDHF9lT2aun5jatpTDm6Y4oVj04lUUiHa60Iszjvj9xgUv2aOHrbVcwdABsYVFGa9Tmt0");'>
            </div>
          </div>
          <!-- AI Message -->
          <div class="flex items-start gap-4">
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" 
                 data-alt="AI Bot avatar" 
                 style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuCom8dSzWKTxBKwGDyDRaVsy8xUe_kWOmFlqOixh3k9GFWda03ICh42LeGyniNblp6I11RQCCpiMCSzNeDNwHPFP95vwQYLuMBNv-SvKtzp-S1z_z6FdxeYL7LQuKSrfNJuhNQ8x3eu965A9e5fytJxKPMuVAKfwPvK7L3CQCV0sHU2hCAd24NU5t48NJAseUMP25cKhZbU616qR5OALiC7_Z6FmaNDFYmQ1Z5ajuCnOWIZXDsa61NgElk_uTZM1P1QdkV3-ERIVCs");'>
            </div>
            <div class="flex flex-col gap-2 rounded-xl rounded-bl-none bg-gray-100 dark:bg-[#111318] p-4 max-w-xl">
              <div class="flex items-center gap-2">
                <p class="text-sm font-bold text-gray-900 dark:text-white">Yapay Zeka</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">10:41</p>
              </div>
              <p class="text-base font-normal leading-relaxed text-gray-800 dark:text-gray-200">Elbette, Python'da bir fonksiyon şu şekilde tanımlanır: <code class="bg-gray-200 dark:bg-gray-900 text-primary dark:text-primary-400 rounded-md px-1.5 py-0.5 font-mono text-sm">def fonksiyon_adi(parametreler):</code></p>
              <div class="flex items-center gap-2 self-end text-gray-500 dark:text-gray-400 mt-2">
                <button class="hover:text-primary"><span class="material-symbols-outlined text-sm">content_copy</span></button>
                <button class="hover:text-primary"><span class="material-symbols-outlined text-sm">thumb_up</span></button>
                <button class="hover:text-primary"><span class="material-symbols-outlined text-sm">thumb_down</span></button>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Composer -->
      <div class="border-t border-gray-200 dark:border-gray-800 p-4">
        <div class="relative">
          <textarea class="form-input w-full resize-none rounded-xl border-gray-300 bg-gray-100 dark:border-gray-700 dark:bg-[#282e39] text-gray-800 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:border-primary focus:ring-primary py-3 pl-4 pr-28" placeholder="Bir mesaj gönder..." rows="1"></textarea>
          <div class="absolute bottom-2 right-2 flex items-center gap-2">
            <button class="flex items-center justify-center p-2 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-primary">
              <span class="material-symbols-outlined">add_circle</span>
            </button>
            <button class="flex items-center justify-center p-2 text-gray-500 hover:text-primary dark:text-gray-400 dark:hover:text-primary">
              <span class="material-symbols-outlined">mic</span>
            </button>
            <button class="flex cursor-pointer items-center justify-center rounded-lg bg-primary h-9 w-9 text-white hover:bg-primary/90">
              <span class="material-symbols-outlined text-base">send</span>
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
