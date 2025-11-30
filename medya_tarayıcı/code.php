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
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(0, 0, 0, 0.8);
      justify-content: center;
      align-items: center;
      z-index: 1000;
    }
    .modal-content {
      position: relative;
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      max-width: 90vw;
      max-height: 90vh;
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .modal img, .modal video {
      max-width: 80vw; /* Constrain image width to 80% of the viewport */
      max-height: 80vh; /* Constrain image height to 80% of the viewport */
      object-fit: contain; /* Ensure the image scales proportionally */
      display: block;
      margin: auto;
      border: 1px solid #ccc; /* Optional: Add a border for better visibility */
      border-radius: 8px; /* Optional: Add rounded corners */
    }
    .modal-nav {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 50px;
      height: 50px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.7);
      color: #fff;
      border-radius: 50%;
      cursor: pointer;
      z-index: 1100; /* Ensure buttons are above other elements */
    }
    .modal-nav.left {
      left: 20px;
    }
    .modal-nav.right {
      right: 20px;
    }
    .modal-nav svg {
      width: 30px;
      height: 30px;
    }
    .close {
      position: absolute;
      top: 20px;
      right: 20px;
      background: rgba(0, 0, 0, 0.7);
      color: #fff;
      border: none;
      padding: 10px;
      border-radius: 50%;
      cursor: pointer;
      font-size: 1.5rem;
      z-index: 1100;
    }
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
    // Medya kök dizini artık veritabanından alınıyor
    <?php
      $db = new SQLite3(__DIR__ . '/../settings.db');
      $userId = $_SESSION['user_id'];
      $stmt = $db->prepare('SELECT setting_value FROM user_settings WHERE user_id = :user_id AND setting_key = :key');
      $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
      $stmt->bindValue(':key', 'media_root', SQLITE3_TEXT);
      $result = $stmt->execute();
      $mediaRoot = ($row = $result->fetchArray(SQLITE3_ASSOC)) ? $row['setting_value'] : '';
      if ($mediaRoot) {
        echo "window.MEDIA_ROOT = '" . addslashes($mediaRoot) . "';\n";
      } else {
        echo "window.MEDIA_ROOT = '';// media_root ayarı bulunamadı veya boş\n";
      }
    ?>
  </script>
  <script src="album/media_browser.js"></script>
</body>
  </html>

    <!-- Modal yapısı: media_browser.js ile tam uyumlu -->
    <div id="modal" class="modal" style="display:none;">
      <div id="modal-media" class="modal-content" style="max-width:90vw; max-height:90vh;"></div>
      <button class="close" onclick="closeModal()" style="font-size:2em;z-index:2;">&times;</button>
      <button class="modal-nav left" onclick="showPrev()" style="font-size:2em;z-index:2;">&#8592;</button>
      <button class="modal-nav right" onclick="showNext()" style="font-size:2em;z-index:2;">&#8594;</button>
    </div>
    </html>
</html>
