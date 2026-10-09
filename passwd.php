<?php
// passwd.php — CLI aracı: VT'de elle şifre değiştirmek için uygulamanın
// kullandığı hash algoritmasının (bcrypt / password_hash) bir şifre için
// ne ürettiğini gösterir.
// Not: Eski kayıtlarda salt'sız SHA-256 kullanılıyordu; bu format artık
// kullanılmıyor, login_check.php girişte otomatik olarak bcrypt'e yükseltiyor.
// Kullanım: php passwd.php <şifre>

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Bu script sadece konsoldan (CLI) çalıştırılabilir.\n";
    exit(1);
}

if ($argc < 2 || trim($argv[1]) === '') {
    fwrite(STDERR, "Kullanım: php passwd.php <şifre>\n");
    exit(1);
}

$password = $argv[1];

echo "bcrypt (password_hash): " . password_hash($password, PASSWORD_DEFAULT) . "\n";
