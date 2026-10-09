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

// Brute-force koruması için basit gecikme
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if ($_SESSION['login_attempts'] > 5) {
    sleep(3);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die('Geçersiz istek (CSRF koruması)!');
    }
    $db = new SQLite3('users.db');

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
        $_SESSION['login_attempts'] = 0;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: home.php');
        exit;
    } else {
        $_SESSION['login_attempts']++;
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
