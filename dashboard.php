<?php
// dashboard.php – Login işlemi + rol bazlı yönlendirme
require_once 'includes/config.php';
require_once 'includes/functions.php';

// ─── GİRİŞ İŞLEMİ ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password']) && !isset($_POST['action'])) {
    // CSRF – login için esnek: hatalıysa yenile, die etme
    $tok = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tok)) {
        header('Location: index.php?error=csrf');
        exit;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $username = trim($_POST['username'] ?? '');

    // Brute-force koruması
    if (isRateLimited($pdo, $ip, 5, 15)) {
        $mins = getRemainingLockoutMinutes($pdo, $ip, 15);
        header('Location: index.php?error=rate_limited&mins=' . $mins);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $loginUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $isValid = $loginUser && password_verify($_POST['password'], $loginUser['password']);
    recordLoginAttempt($pdo, $ip, $username, $isValid ? 1 : 0);

    if ($isValid) {
        // Session fixation koruması
        session_regenerate_id(true);
        $_SESSION['user'] = $loginUser;
        $_SESSION['last_activity'] = time();
        $_SESSION['created_at'] = time();
        header('Location: dashboard.php');
        exit;
    } else {
        header('Location: index.php?error=1');
        exit;
    }
}

// ─── OTURUM KONTROLÜ ───
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// ─── ROL BAZLI PANEL ───
switch ($user['role']) {
    case 'admin':    require_once 'admin_panel.php';    break;
    case 'manager':  require_once 'manager_panel.php';  break;
    case 'resident': require_once 'resident_panel.php'; break;
    default:
        echo '<div style="padding:40px;font-family:sans-serif;color:#dc2626;">Geçersiz kullanıcı rolü.</div>';
}
?>