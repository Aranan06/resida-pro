<?php
// GET /api/notifications.php  -> kendi bildirimleri
// POST /api/notifications.php {title, body, site_id?} -> manager/admin broadcast
require_once __DIR__ . '/config.php';
$user = api_auth($pdo);

if($_SERVER['REQUEST_METHOD']==='GET'){
    $stmt=$pdo->prepare("SELECT * FROM notifications WHERE (user_id=? OR user_id IS NULL AND site_id=?) AND site_id=? ORDER BY created_at DESC LIMIT 30");
    $stmt->execute([$user['id'],$user['site_id'],$user['site_id']]);
    $list=$stmt->fetchAll();
    // unread count
    $cnt=$pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id=? OR user_id IS NULL AND site_id=?) AND site_id=? AND is_read=0");
    $cnt->execute([$user['id'],$user['site_id'],$user['site_id']]);
    api_success(['notifications'=>$list,'unread'=>$cnt->fetchColumn()]);
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    if($user['role']!=='manager' && $user['role']!=='admin') api_error('Sadece yönetici',403);
    $in=json_decode(file_get_contents('php://input'),true); if(!$in) $in=$_POST;
    $title=trim($in['title']??''); $body=trim($in['body']??''); $siteId=$user['site_id'] ?? (int)($in['site_id']??0);
    if(!$title||!$body) api_error('title ve body zorunlu',422);
    if($user['role']==='manager') $siteId=$user['site_id'];
    // Her sakine tek kayıt (broadcast) -> site_id ile, user_id NULL
    $pdo->prepare("INSERT INTO notifications (site_id, user_id, title, body, type) VALUES (?,?,?,?,'system')")->execute([$siteId, null, $title, $body]);
    // Ayrıca push_subscriptions olanlara Web Push gönder (mock - log)
    // Gerçek gönderim için: composer require minishlink/web-push + VAPID
    // Burada sadece logluyoruz, cron veya anında gönderim için ayrı worker gerekir
    api_success(['message'=>'Bildirim gönderildi (in-app). Push için VAPID ile worker ekleyin.']);
}
