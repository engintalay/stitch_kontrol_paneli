<?php
// login_check.php
session_start();
// CSRF token üretimi
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// --- IP ve e-posta bazlı rate limiting (brute-force koruması) ---
// Session'a bağlı sayaç yerine kalıcı bir tabloda tutulur; saldırgan cookie/session
// silerek sınırı aşamaz. Hem IP hem e-posta için ayrı eşikler uygulanır.
const LOGIN_RATE_WINDOW_SECONDS = 900;   // 15 dakikalık pencere
const LOGIN_RATE_MAX_PER_IP = 10;        // Bir IP'den pencere içinde izin verilen başarısız deneme
const LOGIN_RATE_MAX_PER_EMAIL = 5;      // Bir e-posta için pencere içinde izin verilen başarısız deneme

function get_client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function init_rate_limit_db(SQLite3 $db): void {
    $db->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        email TEXT NOT NULL,
        attempted_at INTEGER NOT NULL
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_ip ON login_attempts(ip, attempted_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_login_attempts_email ON login_attempts(email, attempted_at)');
    // Pencere dışına çıkmış eski kayıtları temizle
    $cutoff = time() - LOGIN_RATE_WINDOW_SECONDS;
    $db->exec('DELETE FROM login_attempts WHERE attempted_at < ' . intval($cutoff));
}

/**
 * IP veya e-posta için rate limit aşılmış mı kontrol eder.
 * Aşılmışsa kalan bekleme süresini (saniye) döner, aksi halde 0.
 */
function check_rate_limit(SQLite3 $db, string $ip, string $email): int {
    $cutoff = time() - LOGIN_RATE_WINDOW_SECONDS;

    $stmt = $db->prepare('SELECT COUNT(*) as c, MIN(attempted_at) as oldest FROM login_attempts WHERE ip = :ip AND attempted_at >= :cutoff');
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->bindValue(':cutoff', $cutoff, SQLITE3_INTEGER);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row && $row['c'] >= LOGIN_RATE_MAX_PER_IP) {
        return max(0, LOGIN_RATE_WINDOW_SECONDS - (time() - $row['oldest']));
    }

    $stmt = $db->prepare('SELECT COUNT(*) as c, MIN(attempted_at) as oldest FROM login_attempts WHERE email = :email AND attempted_at >= :cutoff');
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':cutoff', $cutoff, SQLITE3_INTEGER);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($row && $row['c'] >= LOGIN_RATE_MAX_PER_EMAIL) {
        return max(0, LOGIN_RATE_WINDOW_SECONDS - (time() - $row['oldest']));
    }

    return 0;
}

function record_failed_attempt(SQLite3 $db, string $ip, string $email): void {
    $stmt = $db->prepare('INSERT INTO login_attempts (ip, email, attempted_at) VALUES (:ip, :email, :t)');
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':t', time(), SQLITE3_INTEGER);
    $stmt->execute();
}

function clear_attempts(SQLite3 $db, string $ip, string $email): void {
    $stmt = $db->prepare('DELETE FROM login_attempts WHERE ip = :ip OR email = :email');
    $stmt->bindValue(':ip', $ip, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die('Geçersiz istek (CSRF koruması)!');
    }
    $db = new SQLite3('users.db');
    init_rate_limit_db($db);

    $clientIp = get_client_ip();
    $retryAfter = check_rate_limit($db, $clientIp, $username);
    if ($retryAfter > 0) {
        http_response_code(429);
        $minutes = ceil($retryAfter / 60);
        die("Çok fazla başarısız giriş denemesi. Lütfen $minutes dakika sonra tekrar deneyin.");
    }

    // Kullanıcıyı e-posta ile bul (hash karşılaştırması SQL'de değil, PHP'de yapılır;
    // bcrypt her hesaplamada farklı salt ürettiği için WHERE ile eşleştirilemez)
    $stmt = $db->prepare('SELECT id, email, role, password_hash FROM users WHERE email = :username');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    $authenticated = false;
    if ($user) {
        $storedHash = $user['password_hash'];
        // Bcrypt/Argon2 formatı ($2y$, $argon2i$, $argon2id$ ile başlar)
        if (preg_match('/^\$(2y|argon2i|argon2id)\$/', $storedHash)) {
            $authenticated = password_verify($password, $storedHash);
        } else {
            // Eski format: salt'sız SHA-256. Doğrulandıktan sonra bcrypt'e yükselt (lazy migration).
            $authenticated = hash_equals($storedHash, hash('sha256', $password));
            if ($authenticated) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
                $upd->bindValue(':hash', $newHash, SQLITE3_TEXT);
                $upd->bindValue(':id', $user['id'], SQLITE3_INTEGER);
                $upd->execute();
            }
        }
    }

    if ($authenticated) {
        clear_attempts($db, $clientIp, $username);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: home.php');
        exit;
    } else {
        record_failed_attempt($db, $clientIp, $username);
        // Redirect back to login page with error indicator
        header('Location: login.php?error=1');
        exit;
    }
} else {
    // Güvenli form örneği
    echo '<form method="POST">';
    echo 'Kullanıcı adı veya e-posta: <input type="text" name="username" required><br>';
    echo 'Şifre: <input type="password" name="password" required><br>';
    echo '<input type="hidden" name="csrf_token" value="' . h($_SESSION['csrf_token']) . '">';
    echo '<button type="submit">Giriş Yap</button>';
    echo '</form>';
}
?>
