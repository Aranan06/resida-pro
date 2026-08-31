<?php
// POST /api/pay.php  {due_id, note}  -> dekont bildirimi (resident)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/PaymentGateway.php';
$user = api_auth($pdo);
if($user['role'] !== 'resident') api_error('Sadece sakinler ödeme bildirebilir',403);
if($_SERVER['REQUEST_METHOD']!=='POST') api_error('Sadece POST',405);
$input = json_decode(file_get_contents('php://input'), true);
if(!$input) $input=$_POST;
$dueId = (int)($input['due_id'] ?? 0);
$note = trim($input['note'] ?? '');
if(!$dueId) api_error('due_id zorunlu',422);

// Aidat kontrol
$chk=$pdo->prepare("SELECT * FROM dues WHERE id=? AND resident_id=? AND site_id=? AND paid=0");
$chk->execute([$dueId,$user['id'],$user['site_id']]);
$due=$chk->fetch();
if(!$due) api_error('Aidat bulunamadı veya ödenmiş',404);

// Mükerrer
$dup=$pdo->prepare("SELECT COUNT(*) FROM payments WHERE due_id=? AND status='pending'"); $dup->execute([$dueId]);
if($dup->fetchColumn()>0) api_error('Zaten bekleyen bildiriminiz var',409);

$penaltySettings = getPenaltySettings($pdo, $user['site_id']);
$pen = calculatePenalty($due, $penaltySettings);
$total = (float)$due['amount'] + $pen;

// Dosya yükleme API üzerinden base64 ile gelebilir: receipt_base64
$receiptPath = null;
if(!empty($input['receipt_base64'])){
    $data = $input['receipt_base64'];
    // data:image/png;base64,...  veya direkt base64
    if(strpos($data, ',')!==false) $data = substr($data, strpos($data, ',')+1);
    $bin = base64_decode($data, true);
    if($bin && strlen($bin) < 5*1024*1024){
        $dir = __DIR__ . '/../uploads/receipts';
        if(!is_dir($dir)) @mkdir($dir,0775,true);
        $fname = 'receipt_'.$dueId.'_'.time().'_'.bin2hex(random_bytes(4)).'.jpg';
        if(@file_put_contents($dir.'/'.$fname, $bin)) $receiptPath='uploads/receipts/'.$fname;
    }
}

$gw=getPaymentGateway($pdo,'manual');
$res=$gw->createPayment([
    'site_id'=>$user['site_id'],'user_id'=>$user['id'],'due_id'=>$dueId,'amount'=>$total,
    'note'=>$note . ($pen>0 ? " (Faiz: ".money($pen)." TL dahil)" : ''),
    'receipt_path'=>$receiptPath
]);
if(!$res['success']) api_error($res['message'],500);
api_success(['payment_id'=>$res['payment_id'],'amount'=>$total,'penalty'=>$pen,'message'=>$res['message']]);
