<?php
// POST /api/login.php  {username, password}
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_error('Sadece POST', 405);
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
if (!$username || !$password) api_error('Kullanıcı adı ve şifre zorunlu', 422);

// Brute force aynı login_attempts tablosu
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (isRateLimited($pdo, $ip, 10, 15)) api_error('Çok fazla deneme, 15 dk sonra deneyin', 429);

$stmt = $pdo->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();
$valid = $user && password_verify($password, $user['password']);
recordLoginAttempt($pdo, $ip, $username, $valid?1:0);
if (!$valid) api_error('Kullanıcı adı veya şifre hatalı', 401);

// Eski tokenları temizle (kullanıcı başına max 3)
$pdo->prepare("DELETE FROM api_tokens WHERE user_id=? AND expires_at < NOW()")->execute([$user['id']]);
$cnt = $pdo->prepare("SELECT COUNT(*) FROM api_tokens WHERE user_id=?"); $cnt->execute([$user['id']]);
if($cnt->fetchColumn() >= 5){
    $pdo->prepare("DELETE FROM api_tokens WHERE user_id=? ORDER BY created_at ASC LIMIT 1")->execute([$user['id']]);
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 days'));
$pdo->prepare("INSERT INTO api_tokens (user_id, token, expires_at, ip) VALUES (?,?,?,?)")->execute([$user['id'], $token, $expires, $ip]);

// Site bilgisi
$siteName = null;
if($user['site_id']){
    $s=$pdo->prepare("SELECT name FROM sites WHERE id=?"); $s->execute([$user['site_id']]); $siteName=$s->fetchColumn();
}

api_success([
    'token' => $token,
    'expires_at' => $expires,
    'user' => [
        'id'=>$user['id'],'name'=>$user['name'],'username'=>$user['username'],'role'=>$user['role'],
        'site_id'=>$user['site_id'],'site_name'=>$siteName,
        'block_id'=>$user['block_id']??null,'floor'=>$user['floor'],'apartment_no'=>$user['apartment_no']
    ]
]);
