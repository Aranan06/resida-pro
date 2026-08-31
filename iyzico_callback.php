<?php
// iyzico_callback.php – iyzico dönüşü
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/PaymentGateway.php';
if (file_exists(__DIR__ . '/vendor/autoload.php')) require_once __DIR__ . '/vendor/autoload.php';

$pid = (int)($_GET['pid'] ?? 0);
$token = $_POST['token'] ?? $_GET['token'] ?? null;

if (!$pid) die("Geçersiz ödeme.");
if (!$token) {
    // iyzico bazen token'ı POST eder, yoksa en son token'ı DB'den al
    $s=$pdo->prepare("SELECT gateway_ref FROM payments WHERE id=?"); $s->execute([$pid]); $token=$s->fetchColumn();
}
if (!$token) die("Token bulunamadı.");

$apiKey = $_ENV['IYZICO_API_KEY'] ?? getenv('IYZICO_API_KEY') ?: '';
$secret = $_ENV['IYZICO_SECRET_KEY'] ?? getenv('IYZICO_SECRET_KEY') ?: '';
$baseUrl = $_ENV['IYZICO_BASE_URL'] ?? 'https://sandbox-api.iyzipay.com';

try {
    $options = new \Iyzipay\Options();
    $options->setApiKey($apiKey);
    $options->setSecretKey($secret);
    $options->setBaseUrl($baseUrl);

    $req = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
    $req->setLocale(\Iyzipay\Model\Locale::TR);
    $req->setConversationId('RESIDA-'.$pid);
    $req->setToken($token);
    $result = \Iyzipay\Model\CheckoutForm::retrieve($req, $options);

    $stmt=$pdo->prepare("SELECT * FROM payments WHERE id=?"); $stmt->execute([$pid]); $pay=$stmt->fetch();
    if(!$pay) throw new Exception("Ödeme kaydı bulunamadı");

    if($result->getPaymentStatus() === 'SUCCESS' && $result->getStatus() === 'success'){
        // Onayla
        if($pay['status'] !== 'approved'){
            approvePayment($pdo, $pid, $pay['user_id']);
            $pdo->prepare("UPDATE payments SET gateway_ref=? WHERE id=?")->execute([$token, $pid]);
        }
        $msg = "Ödemeniz başarıyla alındı!";
        $ok = true;
    } else {
        $err = $result->getErrorMessage() ?: 'Ödeme başarısız';
        $pdo->prepare("UPDATE payments SET status='rejected', note=CONCAT(COALESCE(note,''), ' | ', ?) WHERE id=?")->execute([$err, $pid]);
        $msg = "Ödeme başarısız: ".$err;
        $ok = false;
    }
} catch(Exception $e){
    $msg = "Hata: ".$e->getMessage(); $ok=false;
}
?>
<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Ödeme Sonucu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"></head>
<body style="background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px">
<div class="card shadow" style="max-width:500px;width:100%">
  <div class="card-body text-center p-4">
    <?php if($ok): ?>
      <div style="width:70px;height:70px;background:#10b981;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px"><i class="fa-solid fa-check"></i></div>
      <h4 class="text-success">Ödeme Başarılı</h4>
      <p class="text-muted"><?= htmlspecialchars($msg) ?></p>
      <p class="small">Makbuzunuz <a href="receipt.php?id=<?= $pay['due_id'] ?>" target="_blank">buradan</a> indirilebilir.</p>
    <?php else: ?>
      <div style="width:70px;height:70px;background:#ef4444;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px"><i class="fa-solid fa-xmark"></i></div>
      <h4 class="text-danger">Ödeme Başarısız</h4>
      <p class="text-muted"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>
    <a href="resident_panel.php" class="btn btn-primary mt-3"><i class="fa-solid fa-house me-1"></i>Panele Dön</a>
    <a href="index.php" class="btn btn-outline-secondary mt-3">Giriş</a>
  </div>
</div>
</body></html>
