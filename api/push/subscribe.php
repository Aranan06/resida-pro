<?php
// POST /api/push/subscribe.php  {endpoint, keys:{p256dh, auth}}
require_once __DIR__ . '/../config.php';
$user = api_auth($pdo);
if($_SERVER['REQUEST_METHOD']!=='POST') api_error('Sadece POST',405);
$input = json_decode(file_get_contents('php://input'), true);
if(!$input) $input=$_POST;
$endpoint = trim($input['endpoint'] ?? '');
$keys = $input['keys'] ?? [];
$p256dh = $keys['p256dh'] ?? $input['p256dh'] ?? '';
$auth = $keys['auth'] ?? $input['auth'] ?? '';
if(!$endpoint) api_error('endpoint zorunlu',422);
// Aynı endpoint varsa güncelle
try{
    $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint=?")->execute([$endpoint]);
    $pdo->prepare("INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth) VALUES (?,?,?,?)")->execute([$user['id'],$endpoint,$p256dh,$auth]);
}catch(PDOException $e){ api_error('Kayıt hatası: '.$e->getMessage(),500); }
api_success(['message'=>'Push aboneliği kaydedildi']);
