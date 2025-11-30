<?php
// admin_panel.php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: home.php');
    exit;
}
?>
<!DOCTYPE html>

<html class="dark" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Yönetici Paneli</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300..700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<style>
        .material-symbols-outlined {
            font-variation-settings:
            'FILL' 1,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
    </style>
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
              "display": ["Space Grotesk"]
            },
            borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
          },
        },
      }
    </script>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="flex h-screen">
<!-- SideNavBar -->
<aside class="flex flex-col w-64 bg-white dark:bg-[#19202e] border-r border-gray-200 dark:border-gray-700/50">
<div class="flex items-center gap-3 px-6 py-5 border-b border-gray-200 dark:border-gray-700/50">
<div class="size-8 text-white bg-primary rounded-lg flex items-center justify-center">
<svg class="size-5" fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_6_330)">
<path clip-rule="evenodd" d="M24 0.757355L47.2426 24L24 47.2426L0.757355 24L24 0.757355ZM21 35.7574V12.2426L9.24264 24L21 35.7574Z" fill="currentColor" fill-rule="evenodd"></path>
</g>
<defs>
<clippath id="clip0_6_330"><rect fill="white" height="48" width="48"></rect></clippath>
</defs>
</svg>
</div>
<h1 class="text-lg font-bold text-gray-800 dark:text-white">Admin Panel</h1>
</div>
<nav class="flex-1 p-4 space-y-2">
<a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/20 text-primary dark:bg-primary/30" href="#">
<span class="material-symbols-outlined">dashboard</span>
<p class="text-sm font-medium">Dashboard</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
<span class="material-symbols-outlined !fill-0">group</span>
<p class="text-sm font-medium">User Management</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
<span class="material-symbols-outlined !fill-0">apps</span>
<p class="text-sm font-medium">Application Management</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
<span class="material-symbols-outlined !fill-0">settings</span>
<p class="text-sm font-medium">System Settings</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
<span class="material-symbols-outlined !fill-0">analytics</span>
<p class="text-sm font-medium">Reports</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg" href="#">
<span class="material-symbols-outlined !fill-0">receipt_long</span>
<p class="text-sm font-medium">Logs</p>
</a>
</nav>
<div class="p-4 border-t border-gray-200 dark:border-gray-700/50">
<div class="flex gap-3 items-center">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Admin User Profile Picture" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuAhqz9JqIJs2JaRgJ7EquDXzK6o2-YrZovVCbZ7W6YwYNVcJ5hrFxytNkXkZ1LTyfUQV9P8SsrHLNineB6miq-pmJzswSa7CImUGtEHSoZ7gZoKbOLvUDK1BUl0E4oBB7DdFZUm7QPgaDdKp5nDnfdjzQySEhfu-cl2JJ8saXZrVZo-VWre7MJ6w7wH6kiM9aEDX_aScxnyFMkCC_NNBK9fn_HawC2iTfMPeSVHKfk6kapF7H6-mtcGlQO9v2k4J5XMA99FVihim8Q");'></div>
<div class="flex flex-col">
<h1 class="text-gray-800 dark:text-white text-sm font-medium leading-normal">Admin User</h1>
<p class="text-gray-500 dark:text-gray-400 text-xs font-normal leading-normal">admin@example.com</p>
</div>
</div>
</div>
</aside>
<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-hidden">
<!-- TopNavBar -->
<header class="flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-700/50 px-8 py-4 bg-white dark:bg-[#19202e]">
<div class="flex items-center gap-8 flex-1">
<label class="flex flex-col min-w-40 w-full max-w-sm">
<div class="flex w-full flex-1 items-stretch rounded-lg h-10">
<div class="text-gray-500 dark:text-gray-400 flex bg-gray-100 dark:bg-background-dark items-center justify-center pl-3.5 rounded-l-lg border-r-0">
<span class="material-symbols-outlined !fill-0 text-xl">search</span>
</div>
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border-none bg-gray-100 dark:bg-background-dark h-full placeholder:text-gray-500 dark:placeholder:text-gray-400 px-4 rounded-l-none border-l-0 pl-2 text-sm font-normal" placeholder="Search..." value=""/>
</div>
</label>
</div>
<div class="flex items-center justify-end gap-4">
<button class="relative flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-gray-100 dark:bg-background-dark text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-white/10">
<span class="material-symbols-outlined !fill-0">notifications</span>
<div class="absolute top-2 right-2.5 size-2 bg-red-500 rounded-full"></div>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Admin User Profile Picture" style='background-image: url("https://lh3.googleusercontent.com/aida-public/AB6AXuD7mBbKDzauZQeAfuikk8LQqR2x4CllldVd1tkgZ9ncsyDqQICsPcF4pyRNI81IziknUxqn9Pa8OvU2x42nXohB5LvYu79GEoYU16p6dJKL8gTM7Vk4z61JQRtJVQJyyBSvrwBHMMXivhs0drtcP59NW8CGLbBfvW9a4NuCIqRy2wcgrCuBkH50lFMcBo7ByYdtLvjeqyQJa2QqwXZ1_Wa4wP3rv9oiUIUIMtPCnSDUB7AIoidFgezEdr2GWgPwT9vSQgHt6W_Icxs");'></div>
</div>
</header>
<!-- Page Content -->
<main class="flex-1 overflow-y-auto p-8">
<!-- PageHeading -->
<div class="flex flex-wrap justify-between gap-3 mb-6">
<div class="flex min-w-72 flex-col gap-1">
<h2 class="text-gray-800 dark:text-white text-3xl font-bold leading-tight tracking-tight">Dashboard</h2>
<p class="text-gray-500 dark:text-gray-400 text-base font-normal leading-normal">Welcome back, Admin! Here's a quick overview of your system.</p>
</div>
</div>
<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-[#19202e] border border-gray-200 dark:border-gray-700/50">
<p class="text-gray-600 dark:text-gray-300 text-sm font-medium leading-normal">Total Users</p>
<p class="text-gray-900 dark:text-white tracking-tight text-3xl font-bold leading-tight">1,482</p>
<p class="text-green-500 text-sm font-medium leading-normal">+1.5% from last month</p>
</div>
<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-[#19202e] border border-gray-200 dark:border-gray-700/50">
<p class="text-gray-600 dark:text-gray-300 text-sm font-medium leading-normal">Active Sessions</p>
<p class="text-gray-900 dark:text-white tracking-tight text-3xl font-bold leading-tight">97</p>
<p class="text-red-500 text-sm font-medium leading-normal">-0.5% from yesterday</p>
</div>
<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-[#19202e] border border-gray-200 dark:border-gray-700/50">
<p class="text-gray-600 dark:text-gray-300 text-sm font-medium leading-normal">System Health</p>
<p class="text-gray-900 dark:text-white tracking-tight text-3xl font-bold leading-tight">99.8%</p>
<p class="text-green-500 text-sm font-medium leading-normal">+0.1% from last hour</p>
</div>
</div>
<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
<div class="lg:col-span-3 flex flex-col gap-2 rounded-xl border border-gray-200 dark:border-gray-700/50 p-6 bg-white dark:bg-[#19202e]">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal">User Signups</p>
<div class="flex items-baseline gap-2">
<p class="text-gray-900 dark:text-white tracking-tight text-3xl font-bold leading-tight truncate">890</p>
<p class="text-green-500 text-sm font-medium leading-normal">+12.5%</p>
</div>
<p class="text-gray-500 dark:text-gray-400 text-sm font-normal leading-normal">Last 30 Days</p>
<div class="flex min-h-[220px] flex-1 flex-col gap-8 pt-4">
<svg fill="none" height="100%" preserveaspectratio="none" viewbox="-3 0 478 150" width="100%" xmlns="http://www.w3.org/2000/svg">
<path d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25V149H326.769H0V109Z" fill="url(#paint0_linear_1131_5935_light)"></path>
<path class="dark:hidden" d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" stroke="#135bec" stroke-linecap="round" stroke-width="3"></path>
<path class="hidden dark:block" d="M0 109C18.1538 109 18.1538 21 36.3077 21C54.4615 21 54.4615 41 72.6154 41C90.7692 41 90.7692 93 108.923 93C127.077 93 127.077 33 145.231 33C163.385 33 163.385 101 181.538 101C199.692 101 199.692 61 217.846 61C236 61 236 45 254.154 45C272.308 45 272.308 121 290.462 121C308.615 121 308.615 149 326.769 149C344.923 149 344.923 1 363.077 1C381.231 1 381.231 81 399.385 81C417.538 81 417.538 129 435.692 129C453.846 129 453.846 25 472 25" stroke="#135bec" stroke-linecap="round" stroke-width="3"></path>
<defs>
<lineargradient gradientunits="userSpaceOnUse" id="paint0_linear_1131_5935_light" x1="236" x2="236" y1="1" y2="149">
<stop stop-color="#135bec" stop-opacity="0.2"></stop>
<stop offset="1" stop-color="#135bec" stop-opacity="0"></stop>
</lineargradient>
</defs>
</svg>
</div>
</div>
<div class="lg:col-span-2 flex flex-col gap-2 rounded-xl border border-gray-200 dark:border-gray-700/50 p-6 bg-white dark:bg-[#19202e]">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal">Application Usage</p>
<div class="flex items-baseline gap-2">
<p class="text-gray-900 dark:text-white tracking-tight text-3xl font-bold leading-tight truncate">3,204 hrs</p>
<p class="text-green-500 text-sm font-medium leading-normal">+8.2%</p>
</div>
<p class="text-gray-500 dark:text-gray-400 text-sm font-normal leading-normal">Last 30 Days</p>
<div class="grid min-h-[220px] grid-flow-col gap-6 grid-rows-[1fr_auto] items-end justify-items-center px-3 pt-4">
<div class="bg-primary/30 dark:bg-primary/40 w-full rounded-t" style="height: 90%;"></div>
<p class="text-gray-500 dark:text-gray-400 text-xs font-bold leading-normal">App A</p>
<div class="bg-primary/30 dark:bg-primary/40 w-full rounded-t" style="height: 30%;"></div>
<p class="text-gray-500 dark:text-gray-400 text-xs font-bold leading-normal">App B</p>
<div class="bg-primary/30 dark:bg-primary/40 w-full rounded-t" style="height: 60%;"></div>
<p class="text-gray-500 dark:text-gray-400 text-xs font-bold leading-normal">App C</p>
<div class="bg-primary/30 dark:bg-primary/40 w-full rounded-t" style="height: 10%;"></div>
<p class="text-gray-500 dark:text-gray-400 text-xs font-bold leading-normal">App D</p>
<div class="bg-primary/30 dark:bg-primary/40 w-full rounded-t" style="height: 75%;"></div>
<p class="text-gray-500 dark:text-gray-400 text-xs font-bold leading-normal">App E</p>
</div>
</div>
</div>
</main>
</div>
</div>
</body></html>
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
  <div class="flex flex-col min-h-screen">
    <header class="flex items-center justify-between p-4 bg-white/80 dark:bg-[#181c23] shadow">
      <div class="flex items-center gap-2">
        <span class="material-symbols-outlined text-primary text-3xl">admin_panel_settings</span>
        <span class="font-bold text-xl text-primary">Admin Paneli</span>
      </div>
      <a href="/home.php" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
    </header>
    <main class="flex-1 p-8">
      <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-2 text-primary flex items-center gap-2">
          <span class="material-symbols-outlined text-3xl">admin_panel_settings</span>
          Yönetici Paneli
        </h1>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Yönetici işlemleri ve sistem yönetimi burada yapılır.</p>
        <div class="bg-white dark:bg-[#23272f] rounded-lg shadow p-6 min-h-[120px] flex items-center justify-center">
          <!-- Buraya yönetici işlemleri eklenecek -->
        </div>
        <div class="mt-8 text-center text-sm text-gray-600 dark:text-gray-300">
          <?php if (isset($_SESSION['user_email']) || isset($_SESSION['user_id'])): ?>
            <span class="font-semibold">Giriş yapan kullanıcı:</span>
            <?php
              $user = [];
              if (isset($_SESSION['user_email'])) $user[] = 'E-posta: ' . htmlspecialchars($_SESSION['user_email']);
              if (isset($_SESSION['user_id'])) $user[] = 'Kullanıcı ID: ' . htmlspecialchars($_SESSION['user_id']);
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
</body>
</html>
