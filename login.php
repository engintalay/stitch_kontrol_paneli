<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
  <meta charset="utf-8"/>
  <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
  <title>Giriş Yap</title>
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
  <div class="w-full max-w-md p-8 bg-white dark:bg-[#181c23] rounded-xl shadow-lg">
    <?php if (isset($_GET['error'])): ?>
      <div class="mb-4 text-red-600 text-sm">Giriş başarısız! Bilgilerinizi kontrol edin.</div>
    <?php endif; ?>
    <div class="flex flex-col items-center mb-6">
      <span class="material-symbols-outlined text-primary text-5xl mb-2">lock</span>
      <h1 class="text-2xl font-bold text-primary mb-1">Giriş Yap</h1>
      <p class="text-gray-500 dark:text-gray-400 text-sm">Lütfen hesabınıza giriş yapın</p>
    </div>
    <form class="space-y-5" method="POST" action="login_check.php">
      <div>
        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kullanıcı adı veya e-posta</label>
        <input id="username" name="username" type="text" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#23272f] text-gray-900 dark:text-gray-100 focus:ring-primary focus:border-primary" placeholder="Kullanıcı adı veya e-posta"/>
      </div>
      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Şifre</label>
        <input id="password" name="password" type="password" required class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-[#23272f] text-gray-900 dark:text-gray-100 focus:ring-primary focus:border-primary" placeholder="Şifreniz"/>
      </div>
      <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"/>
          <label for="remember" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Beni hatırla</label>
        </div>
        <a href="#" class="text-sm text-primary hover:underline">Şifremi unuttum?</a>
      </div>
      <button type="submit" class="w-full py-2 px-4 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Giriş Yap</button>
    </form>
    <p class="mt-6 text-center text-sm text-gray-500 dark:text-gray-400">
      Hesabınız yok mu? <a href="#" class="text-primary hover:underline">Kayıt Ol</a>
    </p>
  </div>
</body>
</html>
