<?php
// index.php – Giriş Sayfası
require_once 'includes/config.php';
require_once 'includes/functions.php';
track_visit($pdo,'giris');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
$mins  = $_GET['mins'] ?? 0;
$csrf  = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RESİDA PRO – Giriş</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0f172a">
<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
<style>
.login-page { background: radial-gradient(ellipse at 60% 30%, #1e1b4b 0%, #0f172a 55%, #000 100%); }
.particles { position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }
.particle {
  position: absolute; border-radius: 50%;
  background: radial-gradient(circle, rgba(99,102,241,.4), transparent);
  animation: float 8s ease-in-out infinite;
}
@keyframes float {
  0%,100% { transform: translateY(0) scale(1); opacity:.6; }
  50% { transform: translateY(-40px) scale(1.1); opacity:1; }
}
.login-card { position: relative; z-index: 1; }
</style>
</head>
<body>
<div class="login-page">
  <div class="particles" aria-hidden="true">
    <div class="particle" style="width:300px;height:300px;top:10%;left:5%;animation-delay:0s"></div>
    <div class="particle" style="width:200px;height:200px;top:60%;left:70%;animation-delay:2s"></div>
    <div class="particle" style="width:150px;height:150px;top:30%;left:85%;animation-delay:4s;background:radial-gradient(circle,rgba(139,92,246,.3),transparent)"></div>
    <div class="particle" style="width:180px;height:180px;top:75%;left:15%;animation-delay:3s;background:radial-gradient(circle,rgba(6,182,212,.2),transparent)"></div>
  </div>

  <div class="login-card fade-up">
    <img src="assets/img/resida-pro-logo.png" alt="Resida Pro" style="display: block; margin: 0 auto 20px auto; max-width:300px; height:auto;">

    <?php if ($error): ?>
    <div class="alert alert-danger mb-4">
      <i class="fa-solid fa-triangle-exclamation me-2"></i>
      <?php
        if ($error === 'rate_limited') {
            echo 'Çok fazla hatalı giriş denemesi. Lütfen ' . (int)$mins . ' dakika sonra tekrar deneyin.';
        } elseif ($error === 'timeout') {
            echo 'Oturumunuz zaman aşımına uğradı. Lütfen tekrar giriş yapın.';
        } elseif ($error === 'csrf') {
            echo 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.';
        } else {
            echo 'Kullanıcı adı veya şifre hatalı.';
        }
      ?>
    </div>
    <?php endif; ?>

    <form method="post" action="dashboard.php">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <div class="mb-3">
        <label class="form-label">Kullanıcı Adı</label>
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.08);color:#94a3b8;">
            <i class="fa-solid fa-user"></i>
          </span>
          <input type="text" name="username" class="form-control" placeholder="kullanici_adi" required autofocus>
        </div>
      </div>
      <div class="mb-4">
        <label class="form-label">Şifre</label>
        <div class="input-group">
          <span class="input-group-text" style="background:rgba(255,255,255,.05);border-color:rgba(255,255,255,.08);color:#94a3b8;">
            <i class="fa-solid fa-lock"></i>
          </span>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary w-100 py-2" style="font-size:.95rem;font-weight:600;">
        <i class="fa-solid fa-right-to-bracket me-2"></i>Giriş Yap
      </button>
    </form>

     <button id="installBtn2" class="btn btn-outline-light btn-sm w-100 mt-3" style="display:none;border-color:rgba(255,255,255,.2);color:#cbd5e1"><i class="fa-solid fa-download me-1"></i>Uygulamayı Yükle</button>
     <div id="iosHint2" class="mt-2 small text-center" style="display:none;color:#94a3b8">📱 iPhone: Paylaş → Ana Ekrana Ekle</div>
     <p class="text-center mt-3" style="font-size:.70rem;color:#64748b;">
      <a href="landing.php" style="color:#94a3b8;text-decoration:underline">Tanıtım & Paketler</a> · <a href="kvkk.php" target="_blank" style="color:#94a3b8;text-decoration:underline">KVKK</a> · <a href="api/README.md" target="_blank" style="color:#94a3b8;text-decoration:underline">API</a>
    </p>
    <script>
    let d2=null;const b2=document.getElementById('installBtn2'),h2=document.getElementById('iosHint2');
    const ios2=/iPad|iPhone|iPod/.test(navigator.userAgent);
    if(window.matchMedia('(display-mode: standalone)').matches) b2.style.display='none';
    else if(ios2) h2.style.display='block';
    window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();d2=e;b2.style.display='block';});
    b2.addEventListener('click',async()=>{if(!d2) return; d2.prompt(); await d2.userChoice; d2=null; b2.style.display='none';});
    </script>
    <p class="text-center mt-1" style="font-size:.70rem;color:#475569;">
      © <?= date('Y') ?> RESİDA PRO · Apartman Yönetim Sistemi
    </p>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('service-worker.js').catch(()=>{});}</script>
</body>
</html>