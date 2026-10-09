<?php
// auth_check.php: Tüm sayfalarda oturum kontrolü ve uygun yanıt
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    $isAjax = (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
        (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    );
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Giriş yapılmamış', 'login' => false]);
    } else {
        header('Location: /login.php');
    }
    exit;
}

// Oturum başına CSRF token üretimi (yoksa oluşturulur)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// AJAX/API endpoint'leri için CSRF token doğrulama yardımcı fonksiyonu.
// Token; POST body, JSON body veya X-CSRF-Token header üzerinden kabul edilir.
function verify_csrf_token() {
    $token = $_POST['csrf_token']
        ?? $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? '';
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Geçersiz veya eksik CSRF token.']);
        exit;
    }
}
