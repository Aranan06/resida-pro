<?php
// =============================================
// includes/config.php – Veritabanı & Güvenlik
// =============================================

// ─── .env Yükleyici (composer olmadan) ───
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        // Tırnakları temizle
        $val = trim($val, "\"'");
        if (!array_key_exists($key, $_ENV) && !getenv($key)) {
            $_ENV[$key] = $val;
            putenv("$key=$val");
        }
    }
}
loadEnv(dirname(__DIR__) . '/.env');

// ─── Ortam Değişkenleri ───
$host    = $_ENV['DB_HOST']    ?? getenv('DB_HOST')    ?: 'localhost';
$dbname  = $_ENV['DB_NAME']    ?? getenv('DB_NAME')    ?: 'resida_pro';
$user    = $_ENV['DB_USER']    ?? getenv('DB_USER')    ?: 'root';
$pass    = $_ENV['DB_PASS']    ?? getenv('DB_PASS')    ?: '';
$charset = $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4';
$sessionTimeout = (int)($_ENV['SESSION_TIMEOUT'] ?? 1800);

// ─── Güvenli Session Başlatma (output öncesi olmalı) ───
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                  || (($_SERVER['SERVER_PORT'] ?? 80) == 443);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    } else {
        @session_start();
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $sessionTimeout)) {
            session_unset(); session_destroy();
            if (php_sapi_name() !== 'cli' && !headers_sent() && basename($_SERVER['PHP_SELF'] ?? '') !== 'index.php') {
                header('Location: index.php?error=timeout'); exit;
            }
            if (!headers_sent()) session_start();
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_activity'] = time();
            if (!isset($_SESSION['created_at'])) $_SESSION['created_at'] = time();
            elseif (time() - $_SESSION['created_at'] > 900) { session_regenerate_id(true); $_SESSION['created_at'] = time(); }
        }
    }
}

// ─── Güvenlik Başlıkları ───
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ─── PDO Bağlantısı ───
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '$charset' COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Üretimde detay gizle
    $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
    $msg = $isDebug ? htmlspecialchars($e->getMessage()) : 'Veritabanına bağlanılamadı. Lütfen yöneticinize başvurun.';
    die('<div style="font-family:sans-serif;padding:40px;color:#dc2626;background:#fef2f2;border-radius:8px;margin:40px;">
        <h2>⚠️ Veritabanı Bağlantı Hatası</h2>
        <p>' . $msg . '</p>
        <p><small>.env dosyasındaki bağlantı bilgilerini kontrol edin.</small></p>
    </div>');
}
