<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/PaymentGateway.php';
if (!isManager()) { header('Location: dashboard.php'); exit; }
$page = $_GET['page'] ?? 'dashboard';
$mySiteId = $user['site_id'];
$error = $success = '';
$siteStmt = $pdo->prepare("SELECT name, max_residents, iban, bank_name, iban_holder, penalty_enabled, penalty_rate, penalty_grace_days, iyzico_submerchant_key FROM sites WHERE id = ?");
$siteStmt->execute([$mySiteId]);
$siteData = $siteStmt->fetch(PDO::FETCH_ASSOC);
$siteName = $siteData['name'] ?? 'Bilinmeyen Site';
$maxResidents = (int)($siteData['max_residents'] ?? 0);
$siteIban = $siteData['iban'] ?? '';
$siteBank = $siteData['bank_name'] ?? '';
$siteHolder = $siteData['iban_holder'] ?? '';
$penaltyEnabled = (int)($siteData['penalty_enabled'] ?? 0);
$penaltyRate = (float)($siteData['penalty_rate'] ?? 5);
$penaltyGrace = (int)($siteData['penalty_grace_days'] ?? 5);
$subMerchantKey = $siteData['iyzico_submerchant_key'] ?? null;

// Subscription check
$subStmt = $pdo->prepare("SELECT ss.*, p.name as plan_name FROM site_subscriptions ss JOIN subscription_plans p ON ss.plan_id=p.id WHERE ss.site_id=? AND ss.status='active' AND ss.current_period_end >= CURDATE() ORDER BY ss.current_period_end DESC LIMIT 1");
$subStmt->execute([$mySiteId]);
$mySubscription = $subStmt->fetch();
$isSubscriptionActive = !empty($mySubscription);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Yönetici Paneli - <?= htmlspecialchars($siteName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if(!$isSubscriptionActive && $page !== 'myplan'): ?>
<div class="d-flex align-items-center justify-content-center" style="min-height:60vh">
  <div class="card text-center p-5 shadow" style="max-width:520px;width:100%">
    <div style="width:80px;height:80px;background:#fef2f2;color:#dc2626;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px">!</div>
    <h4 class="fw-bold">Abonelik Gerekli</h4>
    <p class="text-muted">Bu sitenin RESIDA PRO aboneliği aktif değil veya süresi doldu. Lütfen paket alın, onay sonrası tüm işlemler açılacak.</p>
    <?php if($mySubscription && $mySubscription['status']==='pending'): ?>
      <div class="alert alert-warning small">Ödemeniz alındı, admin onayı bekleniyor.</div>
    <?php endif; ?>
    <a href="?page=myplan" class="btn btn-primary btn-lg mt-2">Paket Al / Yenile</a>
  </div>
</div>
<?php elseif($page==='dashboard'): ?>
<h1>Hoş Geldiniz, <?= htmlspecialchars($user['name']) ?></h1>
<p><?= htmlspecialchars($siteName) ?> site yönetim paneli - Abonelik: <?= $isSubscriptionActive ? 'Aktif' : 'Pasif' ?></p>
<a href="?page=myplan" class="btn btn-warning">Paketim</a>
<?php elseif($page==='myplan'): ?>
<h1>Paketim & Abonelik</h1>
<p>RESIDA PRO aboneliğini buradan yönet</p>
<?php if($mySubscription): ?>
<p>Mevcut plan: <?= htmlspecialchars($mySubscription['plan_name']) ?> - Bitiş: <?= htmlspecialchars($mySubscription['current_period_end']) ?></p>
<?php else: ?>
<p>Henüz aktif aboneliğiniz yok.</p>
<?php endif; ?>
<a href="?page=dashboard" class="btn btn-secondary">Geri</a>
<?php else: ?>
<p>Sayfa: <?= htmlspecialchars($page) ?> - (Diğer sayfalar orijinal dosyadan geri yüklenecek)</p>
<?php endif; ?>
</div>
</body>
</html>
