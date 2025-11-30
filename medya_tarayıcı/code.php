<?php
$title = 'Medya Tarayıcı';
$icon = 'perm_media';
$description = 'Medya dosyalarınızı yönetin';
require_once __DIR__ . '/../auth_check.php';
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
    /* Medya tarayıcıya özel stiller */
    #browser { display: flex; flex-wrap: wrap; gap: 16px; justify-content: center; }
    .item { width: 120px; max-width: 40vw; text-align: center; cursor: pointer; }
    .thumb { position: relative; width: 125px; height: 125px; max-width: 37vw; max-height: 37vw; object-fit: cover; border: 1px solid #ccc; }
    .play-overlay {
        position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);
        width: 48px; height: 48px; border-radius: 50%; background: rgba(0,0,0,0.6);
        color: #fff; display: flex; align-items: center; justify-content: center; font-size: 22px; pointer-events: none;
    }
    .modal { display: none; position: fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.8); justify-content:center; align-items:center; z-index:1000; }
    .modal-content { position: relative; background: #fff; padding: 20px; border-radius: 8px; max-width: 95vw; max-height: 95vh; }
    .modal img, .modal video { max-width: 80vw; max-height: 60vh; display:block; margin:auto; }
    .close { position: absolute; top: 10px; right: 10px; background: #222; color: #fff; border: none; padding: 16px; cursor: pointer; border-radius: 4px; font-size: 2rem; z-index:2; }
    .modal-nav { position: absolute; top: 0; bottom: 0; width: 20vw; min-width: 60px; max-width: 120px; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.01); cursor: pointer; z-index: 1; }
    .modal-nav.left { left: 0; } .modal-nav.right { right: 0; }
    .modal-nav svg { width: 48px; height: 48px; fill: #fff; opacity: 0.7; }
    @media (max-width: 600px) {
      .item { width: 48vw; max-width: 48vw; }
      .thumb { width: 50vw; height: 50vw; max-width: 50vw; max-height: 50vw; }
      .modal-content { padding: 8px; }
      .close, .prev, .next { padding: 10px; font-size: 2.2rem; }
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
      <a href="../logout.php" class="ml-2 px-4 py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition">Çıkış</a>
    </header>
    <main class="flex-1 p-8">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
          <span class="material-symbols-outlined text-3xl"><?= htmlspecialchars($icon) ?></span>
          <?= htmlspecialchars($title) ?>
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6"><?= htmlspecialchars($description) ?></p>
        <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-6">
          <button id="clearCacheBtn" class="mb-4 px-4 py-2 bg-primary text-white rounded hover:bg-blue-700 transition">Cache Temizle</button>
          <div id="path" class="mb-4"></div>
          <div id="browser"></div>
        </div>
      </div>
    </main>
  </div>
</main>
  <script>
    // Medya kök dizini media_root.txt dosyasından dinamik olarak alınır
    <?php
      $mediaRootFile = __DIR__ . '/album/media_root.txt';
      $mediaRoot = '';
      if (file_exists($mediaRootFile)) {
        $lines = file($mediaRootFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
          $line = trim($line);
          if ($line === '' || strpos($line, '#') === 0) continue;
          $mediaRoot = rtrim($line, "/\\");
          break;
        }
      }
      if ($mediaRoot) {
        echo "window.MEDIA_ROOT = '" . addslashes($mediaRoot) . "';\n";
      } else {
        echo "window.MEDIA_ROOT = '';// media_root.txt bulunamadı veya boş\n";
      }
    ?>
  </script>
  <script src="album/media_browser.js"></script>
</body>
  </html>

    <!-- Modal yapısı: media_browser.js ile tam uyumlu -->
    <div id="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.8); align-items:center; justify-content:center;">
      <div id="modal-media" style="max-width:90vw; max-height:90vh;"></div>
      <button onclick="closeModal()" style="position:absolute;top:16px;right:16px;font-size:2em;z-index:2;">&times;</button>
      <button onclick="showPrev()" style="position:absolute;left:16px;top:50%;font-size:2em;z-index:2;">&#8592;</button>
      <button onclick="showNext()" style="position:absolute;right:56px;top:50%;font-size:2em;z-index:2;">&#8594;</button>
    </div>
</html>
