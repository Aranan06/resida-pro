<?php
// GET /api/me.php -> profil
require_once __DIR__ . '/config.php';
$user = api_auth($pdo);
$siteName=null; $blockName=null;
if($user['site_id']){ $s=$pdo->prepare("SELECT name FROM sites WHERE id=?"); $s->execute([$user['site_id']]); $siteName=$s->fetchColumn(); }
if(!empty($user['block_id'])){ $blockName=getBlockName($pdo,$user['block_id']); }
api_success(['user'=>[
    'id'=>$user['id'],'name'=>$user['name'],'username'=>$user['username'],'role'=>$user['role'],
    'site_id'=>$user['site_id'],'site_name'=>$siteName,
    'block_id'=>$user['block_id']??null,'block_name'=>$blockName,'floor'=>$user['floor'],'apartment_no'=>$user['apartment_no'],
    'phone'=>$user['phone'],'email'=>$user['email']
]]);
