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
    $password_hash = hash('sha256', $password);
    //$password_hash = password_hash($password, PASSWORD_DEFAULT);
    // Kullanıcı adı veya e-posta ile giriş
    //echo "SELECT id, email, role FROM users WHERE (email = '$username' OR email = '$username' ) AND password_hash = '$password_hash';";
    //exit(1);

    $stmt = $db->prepare('SELECT id, email, role FROM users WHERE (email = :username OR email = :username2) AND password_hash = :password_hash');
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':username2', $username, SQLITE3_TEXT);
    $stmt->bindValue(':password_hash', $password_hash, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    if ($user) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: home.php');
        exit;
    } else {
        $_SESSION['login_attempts']++;
        echo "Giriş başarısız! Bilgilerinizi kontrol edin.";
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
