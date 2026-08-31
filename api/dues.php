<?php
// GET /api/dues.php  -> kendi aidatları (resident) veya site aidatları (manager/admin)
require_once __DIR__ . '/config.php';
$user = api_auth($pdo);

if ($user['role'] === 'resident') {
    $stmt = $pdo->prepare("SELECT d.*, s.penalty_enabled, s.penalty_rate, s.penalty_grace_days FROM dues d JOIN sites s ON d.site_id=s.id WHERE d.resident_id=? ORDER BY d.due_date DESC");
    $stmt->execute([$user['id']]);
    $dues = $stmt->fetchAll();
    $penaltySettings = getPenaltySettings($pdo, $user['site_id']);
    foreach($dues as &$d){
        $pen = calculatePenalty($d, $penaltySettings);
        $d['penalty'] = $pen;
        $d['total'] = (float)$d['amount'] + $pen;
        $d['days_overdue'] = getDaysOverdue($d, $penaltySettings);
    }
    api_success(['dues'=>$dues, 'penalty_settings'=>$penaltySettings]);
} else {
    // manager/admin: site filtreli
    $siteId = $_GET['site_id'] ?? $user['site_id'];
    if ($user['role']==='manager' && (int)$siteId !== (int)$user['site_id']) api_error('Yetkisiz site',403);
    $filter = $_GET['filter'] ?? 'all';
    $dues = getDuesBySite($pdo, $siteId, $filter);
    $penaltySettings = getPenaltySettings($pdo, $siteId);
    foreach($dues as &$d){ $d['penalty']=calculatePenalty($d,$penaltySettings); $d['total']=(float)$d['amount']+$d['penalty']; }
    api_success(['dues'=>$dues, 'penalty_settings'=>$penaltySettings]);
}
