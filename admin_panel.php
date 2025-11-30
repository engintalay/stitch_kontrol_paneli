<?php
// admin_panel.php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: home.php');
    exit;
}
function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Admin Paneli</title>
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
  <div class="flex h-screen">
    <!-- Sidebar -->
    <aside class="flex flex-col w-64 bg-white dark:bg-[#19202e] border-r border-gray-200 dark:border-gray-700/50">
      <div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200 dark:border-gray-700/50">
        <div class="size-8 text-white bg-primary rounded-lg flex items-center justify-center">
          <span class="material-symbols-outlined">admin_panel_settings</span>
        </div>
        <h1 class="text-lg font-bold text-gray-800 dark:text-white">Admin Paneli</h1>
      </div>
      <nav class="flex-1 p-4 space-y-2">
        <a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/20 text-primary dark:bg-primary/30" href="#">
          <span class="material-symbols-outlined">dashboard</span>
          <p class="text-sm font-medium">Panel</p>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
          <span class="material-symbols-outlined !fill-0">group</span>
          <p class="text-sm font-medium">Kullanıcı Yönetimi</p>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
          <span class="material-symbols-outlined !fill-0">apps</span>
          <p class="text-sm font-medium">Uygulama Yönetimi</p>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
          <span class="material-symbols-outlined !fill-0">settings</span>
          <p class="text-sm font-medium">Sistem Ayarları</p>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
          <span class="material-symbols-outlined !fill-0">analytics</span>
          <p class="text-sm font-medium">Raporlar</p>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
          <span class="material-symbols-outlined !fill-0">receipt_long</span>
          <p class="text-sm font-medium">Kayıtlar</p>
        </a>
      </nav>
      <div class="p-4 border-t border-gray-200 dark:border-gray-700/50">
        <div class="flex gap-3 items-center">
          <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Admin User Profile Picture" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAhqz9JqIJs2JaRgJ7EquDXzK6o2-YrZovVCbZ7W6YwYNVcJ5hrFxytNkXkZ1LTyfUQV9P8SsrHLNineB6miq-pmJzswSa7CImUGtEHSoZ7gZoKbOLvUDK1BUl0E4oBB7DdFZUm7QPgaDdKp5nDnfdjzQySEhfu-cl2JJ8saXZrVZo-VWre7MJ6w7wH6kiM9aEDX_aScxnyFMkCC_NNBK9fn_HawC2iTfMPeSVHKfk6kapF7H6-mtcGlQO9v2k4J5XMA99FVihim8Q");'></div>
          <div class="flex flex-col">
            <h1 class="text-gray-800 dark:text-white text-sm font-medium leading-normal">
              <?php echo isset($_SESSION['user_email']) ? h($_SESSION['user_email']) : 'Admin Kullanıcı'; ?>
            </h1>
            <p class="text-gray-500 dark:text-gray-400 text-xs font-normal leading-normal">
              <?php echo isset($_SESSION['user_role']) ? 'Rol: ' . h($_SESSION['user_role']) : 'admin@example.com'; ?>
            </p>
          </div>
        </div>
      </div>
    </aside>
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <header class="flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-700/50 px-8 py-4 bg-white dark:bg-[#19202e]">
        <div class="flex items-center gap-8 flex-1">
          <span class="font-bold text-xl text-primary">Admin Paneli</span>
        </div>
        <a href="/home.php" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
      </header>
      <main class="flex-1 overflow-y-auto p-8">
        <div class="max-w-2xl mx-auto">
          <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
            <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
            Admin Paneli
          </h1>
          <p class="text-gray-500 dark:text-gray-400 mb-6">Yönetici işlemleri ve sistem yönetimi burada yapılır.</p>
          <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-0 min-h-[120px]">
            <div class="border-b border-gray-200 dark:border-gray-700 flex">
              <button id="tab-panel" class="px-6 py-3 text-sm font-semibold focus:outline-none tab-active text-primary border-b-2 border-primary bg-white dark:bg-[#23272f]">Panel</button>
              <button id="tab-users" class="px-6 py-3 text-sm font-semibold focus:outline-none text-gray-600 dark:text-gray-300 hover:text-primary">Kullanıcı Yönetimi</button>
            </div>
            <div id="tab-content-panel" class="p-6">
              Yönetici işlemleri ve sistem yönetimi burada yapılır.
            </div>
            <div id="tab-content-users" class="p-6 hidden">
              <div class="flex justify-between mb-4">
                <button class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                  <span class="material-symbols-outlined">person_add</span>
                  Yeni Kullanıcı Ekle
                </button>
                <form method="get" class="flex items-center gap-2">
                  <input type="text" name="q" placeholder="Kullanıcı ara..." class="form-input rounded-lg px-3 py-2 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#23272f] text-sm"/>
                  <button type="submit" class="px-3 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                    <span class="material-symbols-outlined text-base">search</span>
                  </button>
                </form>
              </div>
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="py-2 px-3 text-left">ID</th>
                    <th class="py-2 px-3 text-left">E-posta</th>
                    <th class="py-2 px-3 text-left">Rol</th>
                    <th class="py-2 px-3 text-left">İşlemler</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Örnek kullanıcı satırı -->
                  <tr class="border-b border-gray-100 dark:border-gray-800">
                    <td class="py-2 px-3">1</td>
                    <td class="py-2 px-3">admin@example.com</td>
                    <td class="py-2 px-3">admin</td>
                    <td class="py-2 px-3">
                      <button class="text-blue-600 hover:underline mr-2">Düzenle</button>
                      <button class="text-red-600 hover:underline">Sil</button>
                    </td>
                  </tr>
                  <!-- /Örnek kullanıcı satırı -->
                </tbody>
              </table>
            </div>
          </div>
          <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-300">
            </main>
            <script>
            // Sekmeli içerik yönetimi
            const tabPanel = document.getElementById('tab-panel');
            const tabUsers = document.getElementById('tab-users');
            const contentPanel = document.getElementById('tab-content-panel');
            const contentUsers = document.getElementById('tab-content-users');
            tabPanel.addEventListener('click', function() {
              tabPanel.classList.add('tab-active', 'text-primary', 'border-b-2', 'border-primary', 'bg-white', 'dark:bg-[#23272f]');
              tabPanel.classList.remove('text-gray-600', 'dark:text-gray-300', 'hover:text-primary');
              tabUsers.classList.remove('tab-active', 'text-primary', 'border-b-2', 'border-primary', 'bg-white', 'dark:bg-[#23272f]');
              tabUsers.classList.add('text-gray-600', 'dark:text-gray-300', 'hover:text-primary');
              contentPanel.classList.remove('hidden');
              contentUsers.classList.add('hidden');
            });
            tabUsers.addEventListener('click', function() {
              tabUsers.classList.add('tab-active', 'text-primary', 'border-b-2', 'border-primary', 'bg-white', 'dark:bg-[#23272f]');
              tabUsers.classList.remove('text-gray-600', 'dark:text-gray-300', 'hover:text-primary');
              tabPanel.classList.remove('tab-active', 'text-primary', 'border-b-2', 'border-primary', 'bg-white', 'dark:bg-[#23272f]');
              tabPanel.classList.add('text-gray-600', 'dark:text-gray-300', 'hover:text-primary');
              contentPanel.classList.add('hidden');
              contentUsers.classList.remove('hidden');
            });
            </script>
            <?php if (isset($_SESSION['user_email']) || isset($_SESSION['user_id'])): ?>
              <span class="font-semibold">Giriş yapan kullanıcı:</span>
              <?php
                $user = [];
                if (isset($_SESSION['user_email'])) $user[] = htmlspecialchars($_SESSION['user_email']);
                if (isset($_SESSION['user_id'])) $user[] = 'ID: ' . htmlspecialchars($_SESSION['user_id']);
                if (isset($_SESSION['user_role'])) $user[] = 'Rol: ' . htmlspecialchars($_SESSION['user_role']);
                echo implode(' | ', $user);
              ?>
            <?php else: ?>
              <span class="text-red-500">Kullanıcı oturumu bulunamadı.</span>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </div>
</body>
</html>
