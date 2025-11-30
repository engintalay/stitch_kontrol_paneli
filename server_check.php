<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$gd = function_exists('gd_info');
$imagejpeg = function_exists('imagejpeg');
$imagewebp = function_exists('imagewebp');
$imagecreatefromjpeg = function_exists('imagecreatefromjpeg');
$imagecreatefrompng = function_exists('imagecreatefrompng');
$imagecreatefromwebp = function_exists('imagecreatefromwebp');
$imagecreatetruecolor = function_exists('imagecreatetruecolor');
$exif = function_exists('exif_read_data');
$ffmpeg = (bool) shell_exec('command -v ffmpeg');


$missing = [];
if (!$gd) $missing['php-gd'] = 'sudo pacman -S php-gd';
if (!$imagejpeg || !$imagewebp || !$imagecreatefromjpeg || !$imagecreatefrompng || !$imagecreatefromwebp || !$imagecreatetruecolor) $missing['php-gd (fonksiyonlar)'] = 'sudo pacman -S php-gd';
if (!$exif) $missing['php-exif'] = 'sudo pacman -S php-exif';
if (!$ffmpeg) $missing['ffmpeg'] = 'sudo pacman -S ffmpeg';


function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

$php_version = phpversion();
$sqlite3 = class_exists('SQLite3');
$pdo_sqlite = in_array('sqlite', PDO::getAvailableDrivers());
$session = function_exists('session_start');
$writable = is_writable(__DIR__);

?>
<!DOCTYPE html>
<html class="dark" lang="tr">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Sunucu Özellikleri Kontrolü</title>
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
<body class="font-display bg-background-light dark:bg-background-dark">
    <div class="flex flex-col min-h-screen">
        <header class="flex items-center justify-between p-4 bg-white/80 dark:bg-[#181c23] shadow">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-3xl">settings</span>
                <span class="font-bold text-xl text-primary">Sunucu Kontrolü</span>
            </div>
            <a href="/home.php" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">Ana Sayfa</a>
        </header>
        <main class="flex-1 p-8 flex items-center justify-center">
            <div class="w-full max-w-md p-8 bg-white dark:bg-[#181c23] rounded-xl shadow-lg">
                <?php if (!empty($missing)): ?>
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-900/40 border border-red-300 dark:border-red-700 rounded-lg">
                    <div class="font-bold text-red-700 dark:text-red-300 mb-2">Eksik Paket/Fonksiyonlar</div>
                    <ul class="mb-2 text-sm text-red-800 dark:text-red-200">
                        <?php foreach ($missing as $desc => $cmd): ?>
                        <li><strong><?=h($desc)?></strong> &rarr; <code class="bg-gray-200 dark:bg-gray-800 px-1 py-0.5 rounded text-xs"><?=h($cmd)?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="text-xs text-gray-500 dark:text-gray-400">EndeavourOS (Arch tabanlı) için önerilen kurulum komutlarıdır.</div>
                </div>
                <?php endif; ?>
                <div class="flex flex-col items-center mb-6">
                    <span class="material-symbols-outlined text-primary text-5xl mb-2">settings</span>
                    <h1 class="text-2xl font-bold text-primary mb-1">Sunucu Özellikleri Kontrolü</h1>
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Sunucu ortamınızın gereksinimleri karşılayıp karşılamadığını kontrol edin.</p>
                </div>
                <ul class="space-y-3 text-base">
                    <li>PHP Sürümü: <strong><?=h($php_version)?></strong></li>
                    <li>SQLite3: <span class="<?= $sqlite3 ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $sqlite3 ? 'Yüklü' : 'YOK' ?></span></li>
                    <li>PDO SQLite: <span class="<?= $pdo_sqlite ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $pdo_sqlite ? 'Yüklü' : 'YOK' ?></span></li>
                    <li>Session desteği: <span class="<?= $session ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $session ? 'Var' : 'YOK' ?></span></li>
                    <li>Proje dizini yazılabilir mi?: <strong><?= $writable ? '<span class="text-green-600">Evet</span>' : '<span class="text-red-600">Hayır</span>' ?></strong></li>
                    <li>GD kütüphanesi: <span class="<?= $gd ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $gd ? 'Yüklü' : 'YOK' ?></span></li>
                    <li>imagejpeg: <span class="<?= $imagejpeg ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $imagejpeg ? 'Var' : 'YOK' ?></span></li>
                    <li>imagewebp: <span class="<?= $imagewebp ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $imagewebp ? 'Var' : 'YOK' ?></span></li>
                    <li>imagecreatefromjpeg: <span class="<?= $imagecreatefromjpeg ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $imagecreatefromjpeg ? 'Var' : 'YOK' ?></span></li>
                    <li>imagecreatefrompng: <span class="<?= $imagecreatefrompng ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $imagecreatefrompng ? 'Var' : 'YOK' ?></span></li>
                    <li>imagecreatefromwebp: <span class="<?= $imagecreatefromwebp ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $imagecreatefromwebp ? 'Var' : 'YOK' ?></span></li>
                    <li>imagecreatetruecolor: <span class="<?= $imagecreatetruecolor ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $imagecreatetruecolor ? 'Var' : 'YOK' ?></span></li>
                    <li>exif_read_data: <span class="<?= $exif ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $exif ? 'Var' : 'YOK' ?></span></li>
                    <li>ffmpeg (binary): <span class="<?= $ffmpeg ? 'text-green-600' : 'text-red-600' ?> font-semibold"><?= $ffmpeg ? 'Var' : 'YOK' ?></span></li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>
