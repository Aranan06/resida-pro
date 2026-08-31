<?php
// POST /api/logout.php -> token iptal
require_once __DIR__ . '/config.php';
$user = api_auth($pdo);
$header=$_SERVER['HTTP_AUTHORIZATION']??'';
preg_match('/Bearer\s+(\S+)/i',$header,$m);
$token=$m[1]??'';
$pdo->prepare("DELETE FROM api_tokens WHERE token=?")->execute([$token]);
api_success(['message'=>'Çıkış yapıldı']);
