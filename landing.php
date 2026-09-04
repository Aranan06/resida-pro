<?php require_once 'includes/config.php';
require_once 'includes/functions.php';
$menus=landing_menus($pdo); $faqsDb=landing_faqs($pdo);
$T=function($k,$d='') use($pdo){ return landing_setting($pdo,$k,$d); };
try{ $plans=$pdo->query("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY price_monthly")->fetchAll(); }catch(Exception $e){ $plans=[]; }
if(!$plans){ $plans=[
  ['name'=>'Mini','max_residents'=>20,'price_monthly'=>149,'price_yearly'=>1490,'features'=>'["20 daireye kadar","Temel aidat takibi","WhatsApp bildirimleri","Dekont yükleme","Excel dosyasından hızlı aktarım"]'],
  ['name'=>'Standart','max_residents'=>100,'price_monthly'=>349,'price_yearly'=>3490,'features'=>'["100 daireye kadar","Otomatik aidat oluşturma","Gider ve kasa takibi","Otomatik gecikme faizi","Blok ve cadde yapısı","Sakin mobil paneli"]'],
  ['name'=>'Pro','max_residents'=>0,'price_monthly'=>599,'price_yearly'=>5990,'features'=>'["Sınırsız daire ve site","Öncelikli destek","Gelişmiş raporlar","Kartla ödeme","Çoklu yönetici","KVKK uyumlu kayıtlar"]'],
]; }
$demoSuccess=''; $demoError='';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_type']??'')==='demo_request'){
  $name=trim($_POST['demo_name']??''); $company=trim($_POST['demo_company']??''); $phone=trim($_POST['demo_phone']??''); $email=trim($_POST['demo_email']??''); $msg=trim($_POST['demo_msg']??'');
  if(!$name || !$phone || !$email){ $demoError='Ad, telefon ve e-posta zorunlu.'; }
  elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $demoError='Geçerli bir e-posta girin.'; }
  else {
    $subject="RESIDA Demo Talebi - ". $name . ($company ? " / $company" : "");
    $body="Ad Soyad: $name\nFirma/Site: $company\nTelefon: $phone\nE-posta: $email\nMesaj: $msg\nTarih: ".date('d.m.Y H:i')."\nIP: ".($_SERVER['REMOTE_ADDR']??'')."\nKaynak: landing.php";
    @file_put_contents(__DIR__.'/backups/demo_requests.log', date('Y-m-d H:i:s')." | $name | $company | $phone | $email | ".str_replace("\n"," ",$msg)."\n", FILE_APPEND);
    $sent=false;
    if(file_exists(__DIR__.'/vendor/autoload.php')){
      require_once __DIR__.'/vendor/autoload.php';
      try{
        $mail=new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host='smtp.gmail.com'; $mail->Timeout=12; $mail->SMTPAuth=true; $mail->Username='burakkaraefe0@gmail.com'; $mail->Password='rgzz mzuc cclb hgry'; $mail->SMTPSecure=PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS; $mail->Port=587;
        $mail->CharSet='UTF-8'; $mail->setFrom('burakkaraefe0@gmail.com','RESIDA PRO'); $mail->addAddress('info@residapro.com'); $mail->addReplyTo($email,$name);
        $mail->Subject=$subject; $mail->Body=$body; $mail->send(); $sent=true;
      }catch(Exception $e){ $sent=false; @file_put_contents(__DIR__.'/backups/demo_mail_error.log', date('Y-m-d H:i:s')." ".$e->getMessage()."\n", FILE_APPEND); }
    } else {
      $headers="From: noreply@residapro.com\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
      $sent=@mail('info@residapro.com',$subject,$body,$headers);
    }
    if($sent) $demoSuccess='Talebiniz alındı. En kısa sürede sizinle iletişime geçeceğiz.';
    else $demoSuccess='Talebiniz alındı. Ekibimiz en kısa sürede sizi arayacak. (info@residapro.com)';
  }
}
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RESIDA PRO | Apartman ve Site Yönetim Programı – Aidat Takip Programı</title>
<meta name="description" content="Apartman ve site yönetimini Excel'den kurtarın. Apartman yönetim programı, site yönetim programı ve aidat takip programı ile aidat, gider, tahsilat, dekont ve duyuruları tek panelden yönetin.">
<meta name="keywords" content="apartman yönetim programı, site yönetim programı, aidat takip programı, apartman aidat takip, site aidat takip, apartman yönetim yazılımı, site yönetim yazılımı">
<meta property="og:title" content="RESIDA PRO – Apartman ve Site Yönetim Programı">
<meta property="og:description" content="Apartman ve site yönetimini Excel'den kurtarın. Aidat, gider, tahsilat ve sakin yönetimini tek panelden yönetin.">
<meta property="og:type" content="website">
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/icon-96.png">
<link rel="icon" type="image/png" sizes="192x192" href="assets/img/icon-192.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0f172a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--indigo:#6366f1;--indigo2:#4f46e5;--dark:#0f172a;--muted:#64748b;--bg:#f8fafc}
body{font-family:Inter,system-ui,sans-serif;background:var(--bg);color:#0f172a;overflow-x:hidden;line-height:1.6}
.navbar{background:rgba(15,23,42,.94)!important;border-bottom:1px solid rgba(255,255,255,.06)}
.hero{background:radial-gradient(1100px 520px at 72% -8%, #3730a3 0%, transparent 60%),linear-gradient(180deg,#0f172a,#0b1224);color:#fff;padding:64px 0 56px}
.hero h1{font-weight:900;font-size:clamp(2.1rem,4.6vw,3.4rem);line-height:1.05;letter-spacing:-.03em}
.hero h1 span{background:linear-gradient(90deg,#a5b4fc,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero .lead{color:#cbd5e1;font-size:1.1rem;max-width:640px}
.badge-soft{background:rgba(99,102,241,.14);border:1px solid rgba(99,102,241,.28);color:#c7d2fe;border-radius:999px;padding:6px 12px;font-size:.78rem}
.check-list{list-style:none;padding:0;margin:18px 0 0;display:grid;grid-template-columns:1fr 1fr;gap:8px 14px}
.check-list li{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:9px 12px;font-size:.92rem}
.btn-primary{background:var(--indigo);border-color:var(--indigo);box-shadow:0 10px 28px rgba(99,102,241,.35);font-weight:700}
.btn-primary:hover{background:var(--indigo2);border-color:var(--indigo2)}
.section{padding:72px 0}
.section-title{font-weight:900;letter-spacing:-.02em;font-size:clamp(1.5rem,3vw,2.2rem)}
.muted{color:var(--muted)}
.card-soft{background:#fff;border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 10px 28px rgba(15,23,42,.06)}
.feature-icon{width:46px;height:46px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,#6366f1,#22d3ee);color:#fff;font-size:1.1rem}
.flow-arrow{text-align:center;color:#94a3b8;font-size:1.4rem}
.shot{background:#fff;border:1px solid #e2e8f0;border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.1)}
.shot-top{height:38px;background:#0f172a;display:flex;align-items:center;gap:8px;padding:0 14px}
.dot{width:9px;height:9px;border-radius:50%}
.pay-box{background:#0f172a;color:#fff;border-radius:20px;padding:28px}
.pay-step{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:14px;padding:14px;text-align:center}
.price-card{border-radius:20px;background:#fff;border:1px solid #e2e8f0;position:relative;overflow:hidden;height:100%}
.price-card.featured{border-color:#6366f1;box-shadow:0 18px 44px rgba(99,102,241,.18)}
.price-card.featured::before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,#6366f1,#22d3ee)}
.badge-pop{position:absolute;top:14px;right:14px;background:#0f172a;color:#fff;border-radius:999px;padding:5px 11px;font-size:.7rem}
.faq .accordion-button{font-weight:700}
.faq .accordion-button:not(.collapsed){background:#eef2ff;color:#3730a3}
.cta{background:linear-gradient(180deg,#0f172a,#0b1224);color:#fff;border-radius:28px;border:1px solid rgba(255,255,255,.08)}
.phone{width:190px;margin:0 auto;background:#0f172a;border-radius:30px;padding:12px;border:1px solid rgba(255,255,255,.15)}
.phone-screen{background:#fff;border-radius:20px;padding:14px;color:#0f172a;min-height:300px}
@media(max-width:767px){.check-list{grid-template-columns:1fr}.section{padding:52px 0}}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="landing.php"><img src="<?= htmlspecialchars($T('nav_logo','assets/img/resida-pro-logo2.png')) ?>" alt="RESIDA PRO" style="height:28px" class="me-2" onerror="this.style.display='none'">RESIDA PRO</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div id="nav" class="collapse navbar-collapse">
      <div class="navbar-nav mx-auto gap-lg-2 small">
        <?php $navItems=$menus?:[['label'=>'Çözüm','url'=>'#cozum'],['label'=>'Ödeme','url'=>'#odeme'],['label'=>'Ekranlar','url'=>'#ekranlar'],['label'=>'Fiyatlar','url'=>'#fiyatlar'],['label'=>'SSS','url'=>'#sss']]; foreach($navItems as $nv): ?>
        <a class="nav-link" href="<?= htmlspecialchars($nv['url']) ?>"><?= htmlspecialchars($nv['label']) ?></a>
        <?php endforeach; ?>
      </div>
      <div class="ms-auto d-flex gap-2">
        <a href="index.php" class="btn btn-outline-light btn-sm px-3">Giriş</a>
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#demoModal">Ücretsiz Deneyin</button>
      </div>
    </div>
  </div>
</nav>

<header class="hero">
  <div class="container">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <?php $heroBadge=$T('hero_badge',''); if($heroBadge): ?><span class="badge-soft d-inline-flex align-items-center gap-2 mb-3"><i class="fa-solid fa-bolt me-1"></i><?= htmlspecialchars($heroBadge) ?></span><?php endif; ?>
        <h1><?= htmlspecialchars($T('hero_title_a','Apartman ve site yönetimini')) ?> <span><?= htmlspecialchars($T('hero_title_b',"Excel'den kurtarın.")) ?></span></h1>
        <p class="lead mt-3"><?= htmlspecialchars($T('hero_subtitle','Aidat, gider, tahsilat ve sakin yönetimini tek panelden yönetin.')) ?></p>
        <ul class="check-list">
          <li><i class="fa-solid fa-check text-success me-2"></i>Aidat takibi</li>
          <li><i class="fa-solid fa-check text-success me-2"></i>Otomatik gecikme faizi</li>
          <li><i class="fa-solid fa-check text-success me-2"></i>Kartla ödeme</li>
          <li><i class="fa-solid fa-check text-success me-2"></i>Dekont yönetimi</li>
          <li><i class="fa-solid fa-check text-success me-2"></i>WhatsApp bildirimleri</li>
          <li><i class="fa-solid fa-check text-success me-2"></i>Sakin mobil paneli</li>
        </ul>
        <?php if($demoSuccess): ?><div class="alert alert-success mt-3"><?= htmlspecialchars($demoSuccess) ?></div><?php endif; ?><?php if($demoError): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($demoError) ?></div><?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#demoModal"><?= htmlspecialchars($T('hero_primary_btn','Ücretsiz Başlayın')) ?></button>
          <a href="#ekranlar" class="btn btn-outline-light btn-lg px-4"><?= htmlspecialchars($T('hero_secondary_btn','İncele')) ?></a>
        </div>
        <div class="small mt-3" style="color:#94a3b8"><?= htmlspecialchars($T('hero_note','Kredi kartı gerekmez • 10 dakikada kurulum • Aynı gün tahsilat')) ?></div>
      </div>
      <div class="col-lg-6">
        <?php $heroImg=$T('hero_image',''); if($heroImg): ?><img src="<?= htmlspecialchars($heroImg) ?>" alt="RESIDA panel" class="img-fluid rounded-4 shadow"><?php else: ?>
        <div class="shot">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">RESIDA yönetici paneli – canlı görünüm</span></div>
          <div class="p-3" style="background:#0f172a">
            <div class="row g-2">
              <div class="col-4"><div class="p-2 rounded" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)"><div class="small" style="color:#94a3b8">Tahsilat</div><div class="fw-bold" style="color:#fff">₺284.600</div></div></div>
              <div class="col-4"><div class="p-2 rounded" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)"><div class="small" style="color:#94a3b8">Gecikme</div><div class="fw-bold" style="color:#fff">23 daire</div></div></div>
              <div class="col-4"><div class="p-2 rounded" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08)"><div class="small" style="color:#94a3b8">Bekleyen</div><div class="fw-bold" style="color:#fff">8 dekont</div></div></div>
            </div>
            <div class="mt-2 p-2 rounded d-flex align-items-center gap-2" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
              <i class="fa-solid fa-building text-info"></i>
              <div class="small"><div class="fw-bold" style="color:#fff">A Blok – Daire 12</div><div style="color:#94a3b8">Nisan aidatı • 1.250 ₺ • Ödendi</div></div>
              <span class="badge bg-success ms-auto">Tamam</span>
            </div>
            <div class="mt-2 p-2 rounded d-flex align-items-center gap-2" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08)">
              <i class="fa-solid fa-building-columns text-warning"></i>
              <div class="small"><div class="fw-bold" style="color:#fff">Ödeme site hesabına yönlendirildi</div><div style="color:#94a3b8">1.250 ₺ • Dekont onaylandı</div></div>
            </div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</header>

<section class="section" id="problem">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title"><?= htmlspecialchars($T('problem_title',"Excel, WhatsApp ve banka dekontları arasında kaybolmayın.")) ?></h2>
      <p class="muted mx-auto" style="max-width:720px"><?= htmlspecialchars($T('problem_subtitle','Site yönetim programı kullanmayan yöneticiler her ay aynı sorunlarla uğraşıyor.')) ?></p>
    </div>
    <div class="row g-3">
      <div class="col-md-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3"><i class="fa-solid fa-file-excel"></i></div><h5 class="fw-bold">Excel'de aidat takibi</h5><ul class="small muted ps-3 mb-0"><li>Manuel hesaplama</li><li>Hatalı kayıt riski</li><li>Sürekli dosya güncelleme</li></ul></div></div>
      <div class="col-md-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#22c55e,#16a34a)"><i class="fa-brands fa-whatsapp"></i></div><h5 class="fw-bold">WhatsApp'tan dekont toplama</h5><ul class="small muted ps-3 mb-0"><li>Mesaj karmaşası</li><li>Eski dekontları bulamama</li><li>Manuel kontrol zorunluluğu</li></ul></div></div>
      <div class="col-md-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#f59e0b,#ef4444)"><i class="fa-solid fa-building-columns"></i></div><h5 class="fw-bold">Banka hesaplarını takip etme</h5><ul class="small muted ps-3 mb-0"><li>Kimin ödediğini tek tek kontrol etme</li><li>Borçlu sakinleri bulma</li><li>Ay sonu mutabakat stresi</li></ul></div></div>
    </div>
    <div class="text-center mt-4"><div class="d-inline-block px-4 py-2 rounded-pill" style="background:#eef2ff;color:#3730a3;font-weight:700">RESIDA bütün bu işlemleri tek yerde toplar.</div></div>
  </div>
</section>

<section class="section bg-white border-top border-bottom" id="cozum">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title"><?= htmlspecialchars($T('solution_title','Yönetmeniz gereken her şey tek panelde.')) ?></h2>
      <p class="muted"><?= htmlspecialchars($T('solution_subtitle','Aidat takip programı olarak günlük işlerinizi sadeleştirir.')) ?></p>
    </div>
    <div class="row g-3">
      <div class="col-md-6 col-lg-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3"><i class="fa-solid fa-coins"></i></div><h5 class="fw-bold">Aidat Yönetimi</h5><p class="small muted mb-0">Aidatları otomatik oluşturun ve takip edin.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#f59e0b,#ef4444)"><i class="fa-solid fa-receipt"></i></div><h5 class="fw-bold">Gider Yönetimi</h5><p class="small muted mb-0">Site giderlerini kayıt altına alın ve takip edin.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#10b981,#06b6d4)"><i class="fa-solid fa-hand-holding-dollar"></i></div><h5 class="fw-bold">Tahsilat</h5><p class="small muted mb-0">Ödemeleri kolayca takip edin.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#8b5cf6,#ec4899)"><i class="fa-solid fa-file-circle-check"></i></div><h5 class="fw-bold">Dekont Yönetimi</h5><p class="small muted mb-0">Sakinlerin yüklediği dekontları tek dokunuşla onaylayın.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><i class="fa-solid fa-percent"></i></div><h5 class="fw-bold">Gecikme Faizi</h5><p class="small muted mb-0">Gecikme faizlerini otomatik hesaplayın.</p></div></div>
      <div class="col-md-6 col-lg-4"><div class="card-soft p-4 h-100"><div class="feature-icon mb-3" style="background:linear-gradient(135deg,#14b8a6,#0ea5e9)"><i class="fa-solid fa-chart-line"></i></div><h5 class="fw-bold">Raporlar</h5><p class="small muted mb-0">Kasa, gelir-gider ve tahsilat raporlarını kolayca oluşturun.</p></div></div>
    </div>
  </div>
</section>

<section class="section" id="odeme">
  <div class="container">
    <div class="pay-box">
      <div class="row align-items-center g-4">
        <div class="col-lg-6">
          <h2 class="section-title" style="color:#fff"><?= htmlspecialchars($T('payment_title',"Ödeme RESIDA'da tutulmaz.")) ?></h2>
          <p style="color:#cbd5e1"><?= htmlspecialchars($T('payment_text','Sakin kartla ödeme yaptığında para doğrudan sitenin belirlediği banka hesabına yönlendirilir.')) ?></p>
          <div class="row g-2 mt-3">
            <div class="col-4"><div class="pay-step"><i class="fa-solid fa-user"></i><div class="fw-bold small mt-1">SAKİN</div><div class="small" style="color:#94a3b8">Ödemeyi yapar</div></div></div>
            <div class="col-4"><div class="pay-step"><i class="fa-solid fa-shield-halved"></i><div class="fw-bold small mt-1">RESIDA</div><div class="small" style="color:#94a3b8">Güvenle iletir</div></div></div>
            <div class="col-4"><div class="pay-step"><i class="fa-solid fa-building-columns"></i><div class="fw-bold small mt-1">SİTE HESABI</div><div class="small" style="color:#94a3b8">Para hesaba geçer</div></div></div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="bg-white rounded-4 p-4 text-dark">
            <div class="fw-bold"><i class="fa-solid fa-lock me-1 text-success"></i>Neden güvenli?</div>
            <ul class="small muted mt-2 mb-0 ps-3">
              <li>Site IBAN'ı yönetici panelinden tanımlanır.</li>
              <li>Havale dekontu tek dokunuşla onaylanır.</li>
              <li>Kartlı ödemede tutar doğrudan site hesabına gider.</li>
              <li>Tüm işlemler kayıt altında tutulur.</li>
            </ul>
            <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#demoModal">Güveni Yerinde Görün</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-white border-top border-bottom" id="gecis">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title"><?= htmlspecialchars($T('migration_title',"Excel'den RESIDA'ya geçmek düşündüğünüzden kolay.")) ?></h2>
      <p class="muted"><?= htmlspecialchars($T('migration_subtitle',"Yıllardır kullandığınız verileri kaybetmeden RESIDA'ya geçin.")) ?></p>
    </div>
    <div class="row g-3">
      <div class="col-md-4"><div class="card-soft p-4 h-100 text-center"><div class="badge bg-dark mb-2">1</div><h5 class="fw-bold">Excel dosyanızı hazırlayın</h5><p class="small muted">Daire, sakin ve borç listenizi mevcut dosyadan alın.</p></div></div>
      <div class="col-md-4"><div class="card-soft p-4 h-100 text-center"><div class="badge bg-dark mb-2">2</div><h5 class="fw-bold">RESIDA'ya aktarın</h5><p class="small muted">Siteyi kurun, sakinleri dakikalar içinde içeri alın.</p></div></div>
      <div class="col-md-4"><div class="card-soft p-4 h-100 text-center"><div class="badge bg-dark mb-2">3</div><h5 class="fw-bold">Aidat takibine başlayın</h5><p class="small muted">İlk aidatı oluşturun, tahsilatı anında görün.</p></div></div>
    </div>
  </div>
</section>

<section class="section" id="ekranlar">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title"><?= htmlspecialchars($T('screens_title','Aidatları, sakinleri ve raporları tek ekrandan yönetin.')) ?></h2>
      <p class="muted"><?= htmlspecialchars($T('screens_subtitle','Modern SaaS tasarımıyla hazırlanmış yönetici paneli.')) ?></p>
    </div>
    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <div class="shot">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">Yönetici paneli – aidat takip programı</span></div>
          <div class="p-3" style="background:#f8fafc">
            <div class="row g-2 small">
              <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Aidatlar</div><div class="muted">Otomatik oluştur, faizi işlet</div></div></div>
              <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Sakinler</div><div class="muted">Blok ve daireye göre listele</div></div></div>
              <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Giderler</div><div class="muted">Kategori ve makbuzla takip et</div></div></div>
              <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Raporlar</div><div class="muted">Kasa ve tahsilat özeti</div></div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-5">
        <h3 class="fw-bold"><?= htmlspecialchars($T('screens_side_title','Sakinler de her şeyi telefonundan takip etsin.')) ?></h3>
        <p class="muted"><?= htmlspecialchars($T('screens_side_text','Sakinler aidat borçlarını, ödemelerini, dekontlarını ve site duyurularını tek yerden takip edebilir.')) ?></p>
        <div class="phone">
          <div class="phone-screen">
            <div class="small muted">Aidat Borcu</div>
            <div class="fs-4 fw-bold">1.250 TL</div>
            <button class="btn btn-primary btn-sm w-100 mt-2">Ödeme Yap</button>
            <div class="small mt-3 fw-bold">Hızlı işlemler</div>
            <div class="small muted">Ödeme Geçmişi • Dekont Yükle • Duyurular • Ekstre</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="fiyatlar">
  <div class="container">
    <div class="text-center mb-3">
      <h2 class="section-title"><?= htmlspecialchars($T('pricing_title','Size uygun paketi seçin')) ?></h2>
      <p class="muted"><?= htmlspecialchars($T('pricing_subtitle','Mevcut fiyatlandırma korunur. İstediğiniz zaman yükseltebilirsiniz.')) ?></p>
    </div>
    <div class="d-flex justify-content-center gap-2 mb-4">
      <span class="small fw-bold">Aylık</span>
      <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="billToggle"><label class="form-check-label small muted" for="billToggle">Yıllık — 2 ay bedava</label></div>
    </div>
    <div class="row g-4">
      <?php foreach($plans as $i=>$p):
        $feat=json_decode($p['features']??'[]',true); if(!is_array($feat)) $feat=[];
        $isFeat=($i==1); $monthly=(float)$p['price_monthly']; $yearly=(float)($p['price_yearly'] ?? $monthly*10);
        $fit=['Küçük apartmanlar','Orta büyüklükte siteler','Profesyonel site yönetimleri']; $fitText=$fit[$i] ?? '';
      ?>
      <div class="col-md-4"><div class="price-card p-4 <?= $isFeat?'featured':'' ?>">
        <?php if($isFeat): ?><span class="badge-pop">EN POPÜLER</span><?php endif; ?>
        <div class="small fw-bold" style="color:#6366f1"><?= htmlspecialchars($fitText) ?></div>
        <h5 class="fw-bold mt-1"><?= htmlspecialchars(strtoupper($p['name'])) ?></h5>
        <div class="small muted"><?= $p['max_residents']>0 ? (int)$p['max_residents'].' daireye kadar' : 'Sınırsız kullanım' ?></div>
        <div class="my-3"><span class="fs-2 fw-bold price-m" data-m="<?= number_format($monthly,0,',','.') ?>" data-y="<?= number_format($yearly,0,',','.') ?>"><?= number_format($monthly,0,',','.') ?> TL</span><span class="muted price-suf">/ay</span>
        <div class="small muted price-sub" data-m="Aylık ödeme" data-y="Yıllık peşin — ayda <?= number_format($yearly/12,0,',','.') ?> TL'ye gelir">Aylık ödeme</div></div>
        <ul class="small muted ps-3 mb-3"><?php foreach($feat as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
        <button class="btn <?= $isFeat?'btn-primary':'btn-outline-primary' ?> w-100" data-bs-toggle="modal" data-bs-target="#demoModal">Ücretsiz Başlayın</button>
      </div></div>
      <?php endforeach; ?>
    </div>
    <div class="text-center small muted mt-3"><?= htmlspecialchars($T('pricing_note','Mini küçük apartmanlar • Standart orta büyüklükte siteler • Pro profesyonel site yönetimleri içindir.')) ?></div>
  </div>
</section>

<section class="section bg-white border-top" id="sss">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <h2 class="section-title"><?= htmlspecialchars($T('faq_title','Sık sorulan sorular')) ?></h2>
        <p class="muted"><?= htmlspecialchars($T('faq_subtitle','Apartman yönetim programı hakkında merak edilenler.')) ?></p>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#demoModal">Canlı Demo İsteyin</button>
        <div class="small muted mt-3"><i class="fa-solid fa-envelope me-1"></i><?= htmlspecialchars($T('contact_email','info@residapro.com')) ?><br><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($T('contact_phone','0532 XXX XX XX')) ?></div>
      </div>
      <div class="col-lg-8 faq">
        <div class="accordion" id="faqAcc">
          <?php $fi=0; foreach($faqsDb as $fq): $fi++; $fid='fq'.$fq['id']; ?>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button <?= $fi>1?'collapsed':'' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $fid ?>"><?= htmlspecialchars($fq['question']) ?></button></h2><div id="<?= $fid ?>" class="accordion-collapse collapse <?= $fi===1?'show':'' ?>" data-bs-parent="#faqAcc"><div class="accordion-body small muted"><?= nl2br(htmlspecialchars($fq['answer'])) ?></div></div></div>
          <?php endforeach; ?>
          <?php if(!$faqsDb): ?>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#f1">RESIDA nedir?</button></h2><div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc"><div class="accordion-body small muted">RESIDA, apartman ve siteler için aidat takip programıdır.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">Ödeme nereye gider?</button></h2><div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Doğrudan site hesabına. RESIDA'da tutulmaz.</div></div></div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta p-4 p-lg-5 text-center">
      <h2 class="fw-bold"><?= htmlspecialchars($T('cta_title','Site yönetimini bugün kolaylaştırın.')) ?></h2>
      <p style="color:#cbd5e1"><?= htmlspecialchars($T('cta_text','Aidat, gider, tahsilat ve sakin yönetimini RESIDA ile tek panelden yönetin.')) ?></p>
      <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
        <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#demoModal"><?= htmlspecialchars($T('cta_primary_btn','Ücretsiz Başlayın')) ?></button>
        <a href="#ekranlar" class="btn btn-outline-light btn-lg px-4">İncele</a>
      </div>
    </div>
  </div>
</section>

<footer class="py-4 border-top bg-white">
  <div class="container d-flex flex-wrap gap-3 justify-content-between small muted">
    <span>© <?= date('Y') ?> <?= htmlspecialchars($T('footer_text','RESIDA PRO • Apartman ve site yönetim programı')) ?></span>
    <span class="d-flex gap-3"><a href="kvkk.php" class="link-secondary text-decoration-none">KVKK</a><a href="index.php" class="link-secondary text-decoration-none">Giriş</a></span>
  </div>
</footer>

<div class="modal fade" id="demoModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="form_type" value="demo_request"><div class="modal-header"><h5 class="modal-title">Ücretsiz Deneme Talebi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">İsim Soyisim *</label><input type="text" name="demo_name" class="form-control" required></div><div class="mb-3"><label class="form-label">Firma / Site Adı</label><input type="text" name="demo_company" class="form-control"></div><div class="row g-3"><div class="col-md-6"><label class="form-label">Telefon *</label><input type="tel" name="demo_phone" class="form-control" required></div><div class="col-md-6"><label class="form-label">E-posta *</label><input type="email" name="demo_email" class="form-control" required></div></div><div class="mb-3 mt-3"><label class="form-label">Mesaj</label><textarea name="demo_msg" class="form-control" rows="3"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Gönder</button></div></form></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('billToggle')?.addEventListener('change', function(){
  const yearly=this.checked;
  document.querySelectorAll('.price-m').forEach(el=> el.textContent = yearly ? el.dataset.y : el.dataset.m);
  document.querySelectorAll('.price-sub').forEach(el=> el.textContent = yearly ? el.dataset.y : el.dataset.m);
  document.querySelectorAll('.price-suf').forEach(el=> el.textContent = yearly ? '/ay (yıllık)' : '/ay');
});
</script>
<script>
if('serviceWorker' in navigator){ navigator.serviceWorker.register('service-worker.js').catch(()=>{}); }
let deferredPrompt=null;
const installBtn=document.getElementById('installBtn');
const iosHint=document.getElementById('iosHint');
const isIos=/iPad|iPhone|iPod/.test(navigator.userAgent);
const isStandalone=window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
if(installBtn && isStandalone){ installBtn.style.display='none'; } else if(iosHint && isIos){ iosHint.style.display='block'; }
window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredPrompt=e;if(installBtn){installBtn.style.display='inline-block';}});
const isWindows = /Win/.test(navigator.platform) || /Windows/.test(navigator.userAgent);
if(installBtn){ installBtn.addEventListener('click',async()=>{
  if(deferredPrompt){ deferredPrompt.prompt(); const c=await deferredPrompt.userChoice; deferredPrompt=null; if(c.outcome==='accepted') installBtn.style.display='none'; return; }
  if(isIos && iosHint){ iosHint.style.display='block'; iosHint.scrollIntoView({behavior:'smooth',block:'center'}); return; }
  if(isWindows){ alert('Windows Chrome/Edge: adres çubuğu sağındaki "Yükle" ikonuna veya sağ üst menüden "Uygulamayı yükle" seçeneğine dokunun.'); } else { alert('Android Chrome: sağ üst menüden "Uygulamayı yükle" seçeneğine dokunun.'); }
});}
window.addEventListener('appinstalled',()=>{ if(installBtn) installBtn.style.display='none';});
</script>
</body>
</html>
