<?php
// crypto_helper.php — API key gibi hassas verileri veritabanında şifreli saklamak için
// yardımcı fonksiyonlar. libsodium (secretbox / XSalsa20-Poly1305) kullanılır.
//
// Şifreleme anahtarı proje dizininde ayrı bir dosyada (.encryption_key) saklanır,
// bu dosya .gitignore'dadır ve asla versiyon kontrolüne dahil edilmez. Dosya yoksa
// otomatik olarak güvenli rastgele bir anahtar üretilip oluşturulur.

define('ENCRYPTION_KEY_FILE', __DIR__ . '/.encryption_key');

/**
 * Şifreleme anahtarını dosyadan okur, yoksa üretir ve 0600 izinle kaydeder.
 */
function get_encryption_key(): string {
    if (file_exists(ENCRYPTION_KEY_FILE)) {
        $key = file_get_contents(ENCRYPTION_KEY_FILE);
        $key = base64_decode(trim($key), true);
        if ($key !== false && strlen($key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            return $key;
        }
    }
    // Anahtar yok veya geçersiz: yeni bir anahtar üret ve kaydet
    $key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    file_put_contents(ENCRYPTION_KEY_FILE, base64_encode($key));
    @chmod(ENCRYPTION_KEY_FILE, 0600);
    return $key;
}

/**
 * Düz metni şifreler, base64 ile kodlanmış (nonce + ciphertext) string döner.
 * Boş string için boş string döner (örn. API key henüz ayarlanmamışsa).
 */
function encrypt_secret(string $plaintext): string {
    if ($plaintext === '') {
        return '';
    }
    $key = get_encryption_key();
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);
    return base64_encode($nonce . $ciphertext);
}

/**
 * encrypt_secret() ile şifrelenmiş veriyi deşifre eder. Hata durumunda (örn.
 * eski/düz metin veri, bozuk veri) boş string döner — çağıran taraf bunu
 * "ayarlanmamış" gibi ele almalıdır.
 */
function decrypt_secret(string $encoded): string {
    if ($encoded === '') {
        return '';
    }
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
        return '';
    }
    $key = get_encryption_key();
    $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
    return $plaintext === false ? '' : $plaintext;
}

/**
 * Bir API key'in sadece son 4 karakterini gösteren maskelenmiş halini üretir.
 * Örn: "sk-abcdef123456" -> "sk-...3456". Frontend'e tam key göndermemek için kullanılır.
 */
function mask_secret(string $secret): string {
    if ($secret === '') {
        return '';
    }
    $len = strlen($secret);
    if ($len <= 4) {
        return str_repeat('*', $len);
    }
    return '...' . substr($secret, -4);
}
