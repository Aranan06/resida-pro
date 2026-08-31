<?php
// GET /api/events.php
require_once __DIR__ . '/config.php';
$user = api_auth($pdo);
$siteId = $user['site_id'] ?? ($_GET['site_id'] ?? null);
if(!$siteId) api_error('Site bulunamadı',404);
if($user['role']==='manager' && (int)$siteId !== (int)$user['site_id']) api_error('Yetkisiz',403);
$list = getEventsBySite($pdo, $siteId);
api_success(['events'=>$list]);
