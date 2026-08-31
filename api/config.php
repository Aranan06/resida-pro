<?php
// api/config.php – API ortak ayarları
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

function api_json($data, $code=200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
function api_error($msg, $code=400) { api_json(['success'=>false,'error'=>$msg], $code); }
function api_success($data=[]) { api_json(array_merge(['success'=>true], $data)); }

// Rate limit basit: IP başına dakikada 60 istek
function api_rate_limit($pdo) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'api_rate_' . md5($ip);
    // Basit dosya tabanlı değil, login_attempts benzeri tablo kullanmıyoruz; session/cache yoksa pas geç
    // İstersen burada Redis/file ekleyebilirsin. Şimdilik sadece log.
}

// Token doğrulama (Bearer token veya web session fallback)
function api_auth($pdo) {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $header, $m)) {
        $token = $m[1];
    } else if (isset($_SESSION['user'])) {
        // Web session ile gelen istek (PWA)
        return $_SESSION['user'];
    } else {
        api_error('Yetkisiz: Bearer token gerekli', 401);
    }
    if (empty($token) && isset($_SESSION['user'])) return $_SESSION['user'];
    try {
        $stmt = $pdo->prepare("SELECT at.*, u.* FROM api_tokens at JOIN users u ON at.user_id=u.id WHERE at.token=? AND at.expires_at > NOW() LIMIT 1");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        if (!$row) api_error('Geçersiz veya süresi dolmuş token', 401);
        // last_used güncelle
        $pdo->prepare("UPDATE api_tokens SET last_used_at=NOW(), ip=? WHERE id=?")->execute([$_SERVER['REMOTE_ADDR'] ?? null, $row['id']]);
        return $row; // user + token bilgisi karışık, user alanları öncelikli
    } catch(PDOException $e){ api_error('Auth hatası: '.$e->getMessage(), 500); }
}

function api_ensure_table($pdo){
    try{ $pdo->exec("CREATE TABLE IF NOT EXISTS api_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(128) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, last_used_at DATETIME NULL, ip VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); }catch(PDOException $e){}
}
api_ensure_table($pdo);
