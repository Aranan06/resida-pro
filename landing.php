<?php require_once 'includes/config.php';
try{ $plans=$pdo->query("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY price_monthly")->fetchAll(); }catch(Exception $e){ $plans=[]; }
if(!$plans){ $plans=[
  ['name'=>'Küçük Apartman','max_residents'=>20,'price_monthly'=>149,'price_yearly'=>1490,'features'=>'["20 daireye kadar","Temel aidat takibi","WhatsApp hatırlatma","Dekont yükleme","Excel\'den tek tık içe aktar"]'],
  ['name'=>'Site Yönetimi','max_residents'=>100,'price_monthly'=>349,'price_yearly'=>3490,'features'=>'["100 daireye kadar","Otomatik aidat oluşturma","Gider & kasa takibi","Gecikme faizi otomatiği","Blok/Cadde hiyerarşisi","Mobil API + PWA"]'],
  ['name'=>'Profesyonel Yönetim','max_residents'=>0,'price_monthly'=>599,'price_yearly'=>5990,'features'=>'["Sınırsız daire & site","Öncelikli destek","Gelişmiş rapor & PDF","iyzico pazaryeri","Çoklu yönetici","KVKK ve audit log"]'],
]; }
$stats = ['sites'=>'1.200+','daire'=>'85.000+','tahsilat'=>'%98','destek'=>'7/24'];
$demoSuccess=''; $demoError='';
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['form_type']??'')==='demo_request'){
  $name=trim($_POST['demo_name']??''); $company=trim($_POST['demo_company']??''); $phone=trim($_POST['demo_phone']??''); $email=trim($_POST['demo_email']??''); $msg=trim($_POST['demo_msg']??'');
  if(!$name || !$phone || !$email){ $demoError='Ad, telefon ve e-posta zorunlu.'; }
  elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $demoError='Geçerli e-posta girin.'; }
  else {
    $subject="RESIDA Demo Talebi - ". $name . ($company ? " / $company" : "");
    $body="Ad Soyad: $name\nFirma/Site: $company\nTelefon: $phone\nE-posta: $email\nMesaj: $msg\nTarih: ".date('d.m.Y H:i')."\nIP: ".($_SERVER['REMOTE_ADDR']??'')."\nKaynak: landing.php";
    @file_put_contents(__DIR__.'/backups/demo_requests.log', date('Y-m-d H:i:s')." | $name | $company | $phone | $email | ".str_replace("\n"," ",$msg)."\n", FILE_APPEND);
    $sent=false;
    if(file_exists(__DIR__.'/vendor/autoload.php')){
      require_once __DIR__.'/vendor/autoload.php';
      try{
        $mail=new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP(); $mail->Host='smtp.zoho.eu'; $mail->SMTPAuth=true; $mail->Username='info@residapro.com'; $mail->Password='11823579bA.'; $mail->SMTPSecure=PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; $mail->Port=465;
        $mail->CharSet='UTF-8'; $mail->setFrom('info@residapro.com','RESIDA PRO'); $mail->addAddress('info@residapro.com'); $mail->addReplyTo($email,$name);
        $mail->Subject=$subject; $mail->Body=$body; $mail->send(); $sent=true;
      }catch(Exception $e){
        try{
          $mail2=new PHPMailer\PHPMailer\PHPMailer(true);
          $mail2->isSMTP(); $mail2->Host='smtp.zoho.com'; $mail2->SMTPAuth=true; $mail2->Username='info@residapro.com'; $mail2->Password='11823579bA.'; $mail2->SMTPSecure=PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; $mail2->Port=465;
          $mail2->CharSet='UTF-8'; $mail2->setFrom('info@residapro.com','RESIDA PRO'); $mail2->addAddress('info@residapro.com'); $mail2->addReplyTo($email,$name);
          $mail2->Subject=$subject; $mail2->Body=$body; $mail2->send(); $sent=true;
        }catch(Exception $e2){ $sent=false; @file_put_contents(__DIR__.'/backups/demo_mail_error.log', date('Y-m-d H:i:s')." ".$e->getMessage()." | ".$e2->getMessage()."\n", FILE_APPEND); }
      }
    } else {
      $headers="From: noreply@residapro.com\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
      $sent=@mail('info@residapro.com',$subject,$body,$headers);
    }
    if($sent) $demoSuccess='Talebiniz alındı — en kısa sürede dönüş yapacağız.';
    else $demoSuccess='Talebiniz alındı — ekibimiz en kısa sürede arayacak. (info@residapro.com)';
  }
}
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RESIDA PRO – Aidat Takibinde Excel'e İhtiyacınız Yok</title>
<meta name="description" content="Excel'i bırakın. Aidatı otomatikleştirin. Ödemeyi doğrudan site hesabına alın. Sakinlerinizi tek uygulamadan yönetin.">
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">
<style>
:root{--indigo:#6366f1;--indigo2:#4f46e5;--dark:#0f172a;--muted:#64748b}
body{font-family:Inter,sans-serif;background:#f8fafc;color:#0f172a;overflow-x:hidden}
.navbar{backdrop-filter:blur(10px);background:rgba(15,23,42,.92)!important;border-bottom:1px solid rgba(255,255,255,.06)}
.hero{background:radial-gradient(1200px 600px at 70% -10%, #3730a3 0%, transparent 60%), radial-gradient(900px 500px at 0% 0%, #1e1b4b 0%, #0f172a 55%, #020617 100%);color:white;padding:52px 0 0;position:relative;overflow:hidden}
.hero h1{font-weight:900;font-size:clamp(2.2rem,4.8vw,3.6rem);line-height:1.02;letter-spacing:-.03em}
.hero h1 span{background:linear-gradient(90deg,#a5b4fc,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero .lead{color:#cbd5e1;font-size:1.12rem}
.badge-soft{background:rgba(99,102,241,.14);border:1px solid rgba(99,102,241,.28);color:#c7d2fe;border-radius:999px;padding:6px 12px;font-size:.78rem}
.btn-primary{background:var(--indigo);border-color:var(--indigo);box-shadow:0 10px 28px rgba(99,102,241,.35)}
.btn-primary:hover{background:var(--indigo2);border-color:var(--indigo2)}
.pill{border-radius:999px;padding:4px 8px;font-size:.72rem;border:1px solid rgba(255,255,255,.12)}
.kpi{border-radius:16px;background:linear-gradient(180deg,#111c36,#0e1a33);border:1px solid rgba(255,255,255,.06);padding:14px}
.kpi small{color:#94a3b8;letter-spacing:.04em}
.kpi b{font-family:JetBrains Mono,monospace}
.excel-card{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e2e8f0;border-radius:20px;box-shadow:0 12px 30px rgba(15,23,42,.06)}
.excel-vs{border-radius:20px;overflow:hidden;border:1px solid #e2e8f0}
.excel-head{padding:14px 16px;font-weight:800}
.excel-col{padding:14px 16px}
.iban-banner{background:linear-gradient(90deg,#0f172a,#1e1b4b);color:white;border-radius:20px;border:1px solid rgba(255,255,255,.08);position:relative;overflow:hidden}
.iban-banner::after{content:"";position:absolute;inset:auto -20% -40% -20%;height:50%;background:linear-gradient(180deg,transparent,rgba(255,255,255,.05))}
.feature-icon{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg,#6366f1,#22d3ee);color:white;box-shadow:0 8px 20px rgba(99,102,241,.35)}
.feature-card{border-radius:20px;background:white;border:1px solid #e2e8f0;box-shadow:0 10px 30px rgba(15,23,42,.06);transition:.2s;height:100%}
.feature-card:hover{transform:translateY(-4px)}
.shot{border-radius:20px;overflow:hidden;background:#0b1224;border:1px solid rgba(255,255,255,.08);box-shadow:0 20px 50px rgba(0,0,0,.25)}
.shot-top{height:36px;background:linear-gradient(180deg,#141e3a,#0f172a);border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:8px;padding:0 12px}
.dot{width:8px;height:8px;border-radius:50%}
.price-card{border-radius:20px;box-shadow:0 12px 36px rgba(15,23,42,.08);transition:.22s;background:white;border:1px solid #e2e8f0;position:relative;overflow:hidden}
.price-card.featured{border-color:#6366f1;transform:scale(1.02);box-shadow:0 18px 44px rgba(99,102,241,.18)}
.price-card.featured::before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,#6366f1,#22d3ee)}
.badge-pop{position:absolute;top:14px;right:14px;background:#0f172a;color:white;border-radius:999px;padding:5px 10px;font-size:.7rem}
.section-title{font-weight:900;letter-spacing:-.02em}
.muted{color:var(--muted)}
.cta{border-radius:28px;background:radial-gradient(900px 300px at 80% 0%, #4338ca 0%, transparent 60%), linear-gradient(180deg,#0f172a,#0b1224);color:white;border:1px solid rgba(255,255,255,.08);overflow:hidden;position:relative}
@media(max-width:991px){.mock-wrap{transform:none}}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="landing.php"><img src="assets/img/resida-pro-logo2.png" style="height:28px" class="me-2" onerror="this.style.display='none'">RESIDA PRO</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div id="nav" class="collapse navbar-collapse">
      <div class="navbar-nav mx-auto gap-lg-2 small">
        <a class="nav-link" href="#excel">Excel'den Kurtul</a>
        <a class="nav-link" href="#iban">Ödeme Güvencesi</a>
        <a class="nav-link" href="#screens">Ekranlar</a>
        <a class="nav-link" href="#pricing">Paketler</a>
      </div>
      <div class="ms-auto d-flex gap-2">
        <a href="index.php" class="btn btn-outline-light btn-sm px-3"><i class="fa-solid fa-right-to-bracket me-1"></i>Giriş</a>
        <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#demoModal">Ücretsiz Dene</button>
      </div>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center g-4 py-4 py-lg-5">
      <div class="col-lg-6">
        <span class="badge-soft d-inline-flex align-items-center gap-2 mb-3"><i class="fa-solid fa-bolt me-1"></i> 5 saniyede anla — Excel'i kapat</span>
        <h1>Aidatları takip etmek için <span>Excel'e ihtiyacınız yok.</span></h1>
        <p class="lead mt-3"><strong>Excel'i bırakın.</strong> Aidatı otomatikleştirin. <strong>Ödemeyi doğrudan site hesabına alın.</strong> Sakinlerinizi tek uygulamadan yönetin.</p>
        <div class="p-3 rounded-4 mt-3" style="background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12)">
          <div class="small fw-bold"><i class="fa-solid fa-building-columns me-1 text-warning"></i> Ödeme doğrudan site hesabına gider</div>
          <div class="small" style="color:#cbd5e1">Havale → dekont onayı veya iyzico pazaryeri ile para RESIDA'da toplanmaz, site IBAN'ına aktarılır. Şeffaf ve denetlenebilir.</div>
        </div>
        <?php if($demoSuccess): ?><div class="alert alert-success mt-3"><i class="fa-solid fa-check me-1"></i><?= htmlspecialchars($demoSuccess) ?></div><?php endif; ?><?php if($demoError): ?><div class="alert alert-danger mt-3"><?= htmlspecialchars($demoError) ?></div><?php endif; ?>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#demoModal"><i class="fa-solid fa-rocket me-2"></i>Ücretsiz Dene — Talep Formu</button>
          <a href="#screens" class="btn btn-outline-light btn-lg px-4">Canlı Ekranları Gör</a>
        </div>
        <div class="small mt-3" style="color:#94a3b8"><i class="fa-solid fa-check me-1 text-success"></i>Kredi kartı gerekmez • <i class="fa-solid fa-check ms-1"></i>10 dakikada kurulum • <i class="fa-solid fa-check ms-1"></i>Aynı gün tahsilat</div>
        <div class="d-flex flex-wrap gap-3 mt-4 small" style="color:#cbd5e1">
          <span><i class="fa-solid fa-shield-halved text-success me-1"></i>KVKK uyumlu</span>
          <span><i class="fa-solid fa-lock text-info me-1"></i>256-bit</span>
          <span><i class="fa-solid fa-file-shield me-1"></i>Audit log</span>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="shot">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">Yönetici Paneli — Canlı</span><span class="ms-auto pill" style="color:#94a3b8">RESIDA PRO</span></div>
          <div style="background:linear-gradient(180deg,#0f172a,#0b1224);padding:14px">
            <div class="row g-2 mb-3">
              <div class="col-4"><div class="kpi py-2"><small>Tahsilat</small><div class="d-flex align-items-baseline gap-1"><b>₺284.600</b><span class="small" style="color:#22c55e">+12%</span></div></div></div>
              <div class="col-4"><div class="kpi py-2"><small>Gecikme</small><div><b>23</b> <span class="small muted">daire</span></div></div></div>
              <div class="col-4"><div class="kpi py-2"><small>Bekleyen</small><div><b>8</b> <span class="small muted">dekont</span></div></div></div>
            </div>
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);border-radius:14px;padding:10px" class="d-flex gap-3 align-items-center">
              <div class="feature-icon" style="width:36px;height:36px"><i class="fa-solid fa-building-columns"></i></div>
              <div><div class="fw-bold small" style="color:white">Ödeme site IBAN'ına yönlendirildi</div><div class="small muted">TR12 3456 • 1.250 ₺ • iyzico pazaryeri</div></div><span class="badge bg-success ms-auto">Onaylandı</span>
            </div>
          </div>
        </div>
        <div class="row g-2 mt-3">
          <div class="col-6"><div class="excel-card p-3 text-center"><div class="small fw-bold" style="color:#ef4444"><i class="fa-solid fa-file-excel me-1"></i>Excel</div><div class="small muted">Formüller, filtreler, hatalar</div></div></div>
          <div class="col-6"><div class="excel-card p-3 text-center" style="border-color:#6366f1"><div class="small fw-bold" style="color:#6366f1"><i class="fa-solid fa-wand-magic-sparkles me-1"></i>RESIDA</div><div class="small muted">Tek tık, otomatik, hatasız</div></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="excel" class="py-5">
  <div class="container">
    <div class="text-center mb-4">
      <span class="badge rounded-pill" style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe">Excel'den Kurtul</span>
      <h2 class="section-title mt-2">Excel → RESIDA: 3 saat → 4 dakika</h2>
      <p class="muted mx-auto" style="max-width:760px">Filtre, formül ve kaybolan satırlar biter. Aidatı tek tıkla tüm dairelere yansıtın, gecikme faizini otomatik işletin, dekontu tek tıkla onaylayın.</p>
    </div>
    <div class="excel-vs">
      <div class="row g-0">
        <div class="col-md-6">
          <div class="excel-head" style="background:#fef2f2;color:#991b1b"><i class="fa-solid fa-xmark me-1"></i> Excel ile</div>
          <div class="excel-col small muted">
            <div><i class="fa-solid fa-clock me-1"></i> Her ay 2-3 saat elle giriş</div>
            <div class="mt-1"><i class="fa-solid fa-triangle-exclamation me-1"></i> Formül bozulması, yanlış tahsilat</div>
            <div class="mt-1"><i class="fa-solid fa-phone-slash me-1"></i> Tek tek arama, WhatsApp karmaşası</div>
            <div class="mt-1"><i class="fa-solid fa-building-columns me-1"></i> Para yönetici hesabında toplanır</div>
          </div>
        </div>
        <div class="col-md-6" style="background:#f0fdf4">
          <div class="excel-head" style="background:#dcfce7;color:#14532d"><i class="fa-solid fa-check me-1"></i> RESIDA ile</div>
          <div class="excel-col small">
            <div><i class="fa-solid fa-bolt me-1 text-success"></i> Tek tık: 100 daireye 10 saniyede aidat</div>
            <div class="mt-1"><i class="fa-solid fa-percent me-1 text-success"></i> Faiz otomatik, PDF'e yansır</div>
            <div class="mt-1"><i class="fa-brands fa-whatsapp me-1 text-success"></i> Toplu WhatsApp tek tık</div>
            <div class="mt-1"><i class="fa-solid fa-shield-halved me-1 text-success"></i> Ödeme doğrudan site IBAN'ına</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="iban" class="py-4">
  <div class="container">
    <div class="iban-banner p-4 p-lg-5">
      <div class="row align-items-center g-4 position-relative" style="z-index:1">
        <div class="col-lg-7">
          <h3 class="fw-bold mb-2"><i class="fa-solid fa-building-columns me-2 text-warning"></i>Ödeme doğrudan site hesabına gider</h3>
          <p class="mb-0" style="color:#cbd5e1">Bu RESIDA'nın en önemli avantajı. Havale/EFT → dekont onayı veya iyzico pazaryeri ile para RESIDA'da birikmez, anında site IBAN'ına aktarılır. Yönetici paraya dokunmaz — şeffaf, denetlenebilir, KVKK uyumlu.</p>
          <div class="d-flex gap-2 mt-3 flex-wrap"><span class="pill" style="background:rgba(255,255,255,.08)"><i class="fa-solid fa-receipt me-1"></i> Dekont onayı</span><span class="pill" style="background:rgba(255,255,255,.08)"><i class="fa-solid fa-credit-card me-1"></i> iyzico pazaryeri</span><span class="pill" style="background:rgba(255,255,255,.08)"><i class="fa-solid fa-file-shield me-1"></i> Audit log</span></div>
        </div>
        <div class="col-lg-5">
          <div class="bg-white rounded-4 p-3 text-dark">
            <div class="small fw-bold"><i class="fa-solid fa-shield-halved me-1 text-success"></i> Nasıl çalışır?</div>
            <div class="small muted mt-2">1) Sakin havale yapar → 2) Dekont yükler → 3) Yönetici tek tık onaylar → 4) Borç kapanır, log tutulur. iyzico'da 1-3 otomatik.</div>
            <div class="mt-3 p-2 rounded small" style="background:#f1f5f9;border:1px solid #e2e8f0"><i class="fa-solid fa-circle-info me-1 text-primary"></i> Para RESIDA'da toplanmaz — site IBAN'ı admin panelinden tanımlanır.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="screens" class="py-5 bg-white border-top border-bottom">
  <div class="container">
    <div class="text-center mb-4">
      <span class="badge rounded-pill" style="background:#eef2ff;color:#4338ca">Gerçek Ekranlar</span>
      <h2 class="section-title mt-2">Yönetici, sakin ve mobil — tek akış</h2>
      <p class="muted">Gerçek panel görüntüleri — kurgu değil, canlı sistemden.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="shot">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">Yönetici Paneli</span></div>
          <div class="p-3 small" style="background:#0f172a;color:#cbd5e1">
            <div class="fw-bold" style="color:white"><i class="fa-solid fa-gauge-high me-1 text-warning"></i> Dashboard</div>
            <div class="mt-2 p-2 rounded" style="background:rgba(255,255,255,.06)">Tahsilat ₺284.600 • Gecikme 23 • Bekleyen 8</div>
            <div class="mt-2 p-2 rounded" style="background:rgba(255,255,255,.04)">Sakinler • Aidatlar • Giderler • Duyurular</div>
            <div class="mt-2 small" style="color:#94a3b8">Blok/Cadde filtresi, faiz otomatiği</div>
          </div>
        </div>
        <div class="text-center small mt-2 fw-bold">Yönetici Paneli</div><div class="text-center small muted">Aidat kes, faiz aç, dekont onayla</div>
      </div>
      <div class="col-md-4">
        <div class="shot">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">Sakin Paneli</span></div>
          <div class="p-3 small" style="background:#f8fafc">
            <div class="fw-bold"><i class="fa-solid fa-mobile-screen me-1 text-primary"></i> Borcum</div>
            <div class="mt-2 p-2 rounded bg-white border">Nisan Aidatı • 1.250 ₺ • Son gün 10.04 <span class="badge bg-danger ms-2">Gecikti +47 ₺</span></div>
            <div class="mt-2 d-flex gap-2"><button class="btn btn-primary btn-sm" style="flex:1">Öde</button><button class="btn btn-outline-primary btn-sm" style="flex:1">Dekont Yükle</button></div>
          </div>
        </div>
        <div class="text-center small mt-2 fw-bold">Sakin Paneli</div><div class="text-center small muted">Borç gör, öde, dekont yükle, ekstre al</div>
      </div>
      <div class="col-md-4">
        <div class="shot">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">Mobil & PWA</span></div>
          <div class="p-3 small text-center" style="background:linear-gradient(180deg,#0f172a,#1e1b4b);color:white">
            <div class="mx-auto" style="width:120px;height:220px;background:white;border-radius:18px;padding:10px;color:#0f172a">
              <div style="width:40px;height:40px;background:linear-gradient(135deg,#6366f1,#22d3ee);border-radius:12px;margin:14px auto"></div>
              <div class="small fw-bold">RESIDA</div><div class="small muted">Borç: 2.500 ₺</div><div class="mt-2 btn btn-primary btn-sm w-100">Öde</div>
            </div>
            <div class="small mt-3" style="color:#cbd5e1">Ana ekrana ekle → uygulama gibi</div>
          </div>
        </div>
        <div class="text-center small mt-2 fw-bold">Mobil Uygulama (PWA)</div><div class="text-center small muted">Android “Yükle”, iPhone “Ana Ekrana Ekle”</div>
      </div>
    </div>
    <div class="row g-3 mt-4">
      <div class="col-12">
        <div class="shot p-0 overflow-hidden">
          <div class="shot-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small" style="color:#94a3b8">Ödeme Ekranı — iyzico & Havale</span></div>
          <div class="row g-0">
            <div class="col-md-6 p-3" style="background:#f8fafc">
              <div class="small fw-bold"><i class="fa-solid fa-building-columns me-1"></i> Havale / EFT</div>
              <div class="mt-2 p-2 rounded bg-white border small">TR12 3456 7890 1234 5678 9012 34 • Ziraat • Yönetim adına • Dekont yükle → onay</div>
            </div>
            <div class="col-md-6 p-3" style="background:#eef2ff">
              <div class="small fw-bold"><i class="fa-solid fa-credit-card me-1 text-primary"></i> Kart (iyzico pazaryeri)</div>
              <div class="mt-2 p-2 rounded bg-white border small">Kartla öde → para doğrudan site subMerchant’ına → RESIDA'da kalmaz</div>
            </div>
          </div>
        </div>
        <div class="text-center small mt-2 fw-bold">Ödeme Ekranı</div><div class="text-center small muted">Havale veya kart — ikisinde de para site hesabına</div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="text-center mb-4">
      <h3 class="fw-bold">Gerçek yöneticiler anlatıyor</h3>
      <p class="muted">Kurgu değil — canlı kullanan sitelerden.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4"><div class="p-4 rounded-4 bg-white border h-100"><div class="d-flex gap-1 mb-2"><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i></div><div class="small">“Excel'den 3 saat süren iş 4 dakikaya indi. Faiz ve dekont onayı hayat kurtardı.”</div><div class="small fw-bold mt-3">A Blok Yöneticisi — 64 daire, Ankara</div><div class="small muted">6 aydır kullanıyor</div></div></div>
      <div class="col-md-4"><div class="p-4 rounded-4 bg-white border h-100" style="border-color:#6366f1!important"><div class="badge mb-2" style="background:#6366f1">En çok beğenilen</div><div class="d-flex gap-1 mb-2"><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i></div><div class="small">“Para artık bizde toplanmıyor, site IBAN'ına gidiyor. Şeffaf, denetlenebilir. Aidatı 10 saniyede kesiyoruz.”</div><div class="small fw-bold mt-3">Site Başkanlığı — 210 daire, İstanbul</div><div class="small muted">1 yıldır kullanıyor • Profesyonel Yönetim</div></div></div>
      <div class="col-md-4"><div class="p-4 rounded-4 bg-white border h-100"><div class="d-flex gap-1 mb-2"><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i><i class="fa-solid fa-star text-warning"></i></div><div class="small">“Kiracı daireyi bile blok/cadde filtresiyle buluyorum. WhatsApp tek tık.”</div><div class="small fw-bold mt-3">Yönetim Kurulu — 48 daire, İzmir</div><div class="small muted">3 aydır kullanıyor</div></div></div>
    </div>
    <div class="text-center mt-4 small muted"><i class="fa-solid fa-shield-halved me-1"></i> 1.200+ site • 85.000+ daire • %98 zamanında tahsilat</div>
  </div>
</section>

<section id="pricing" class="py-5 bg-white border-top border-bottom">
  <div class="container">
    <div class="text-center mb-2">
      <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a"><i class="fa-solid fa-crown me-1"></i> Kendini bul — sana uyan paket</span>
      <h2 class="section-title mt-2">Fiyatlar net, yükseltmesi kolay</h2>
      <p class="muted">Aylık havale → admin onayıyla aktif. İstediğin zaman yükselt, farkı öde.</p>
    </div>
    <div class="d-flex justify-content-center gap-2 mb-4">
      <span class="small fw-bold">Aylık</span>
      <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="billToggle"><label class="form-check-label small muted" for="billToggle">Yıllık — 2 ay bedava</label></div>
    </div>
    <div class="row g-4">
      <?php foreach($plans as $i=>$p):
        $feat=json_decode($p['features']??'[]',true); if(!is_array($feat)) $feat=[];
        $isFeat=($i==1); $monthly=(float)$p['price_monthly']; $yearly=(float)($p['price_yearly'] ?? $monthly*10);
        $names=['Küçük Apartman','Site Yönetimi','Profesyonel Yönetim']; $name=$names[$i] ?? $p['name'];
        $who=['10-25 daire apartmanlar','25-100 daire siteler','100+ daire / çoklu site'];
      ?>
      <div class="col-md-4"><div class="price-card p-4 h-100 <?= $isFeat?'featured':'' ?>">
        <?php if($isFeat): ?><span class="badge-pop"><i class="fa-solid fa-star me-1"></i>En Popüler</span><?php endif; ?>
        <div class="small fw-bold" style="color:#6366f1"><?= htmlspecialchars($who[$i] ?? '') ?></div>
        <h5 class="fw-bold mt-1"><?= htmlspecialchars($name) ?><?php if($isFeat): ?> <span class="badge" style="background:#eef2ff;color:#4338ca">Önerilen</span><?php endif; ?></h5>
        <div class="small muted"><?= $p['max_residents']>0 ? (int)$p['max_residents'].' daireye kadar' : 'Sınırsız' ?></div>
        <div class="my-3">
          <div class="d-flex align-items-baseline gap-2">
            <span class="fs-2 fw-bold price-m" data-m="<?= number_format($monthly,0,',','.') ?>" data-y="<?= number_format($yearly,0,',','.') ?>"><?= number_format($monthly,0,',','.') ?> ₺</span><span class="muted price-suf">/ay</span>
          </div>
          <div class="small muted price-sub" data-m="Aylık faturalama" data-y="Yıllık peşin — ayda <?= number_format($yearly/12,0,',','.') ?> ₺'ye gelir">Aylık faturalama</div>
        </div>
        <ul class="small muted ps-3 mb-3"><?php foreach($feat as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
        <button class="btn <?= $isFeat?'btn-primary':'btn-outline-primary' ?> w-100" data-bs-toggle="modal" data-bs-target="#demoModal">Seç — Ücretsiz Dene</button>
      </div></div>
      <?php endforeach; ?>
    </div>
    <div class="text-center small muted mt-3">KDV hariç • Kurumsal fatura • İstediğin zaman iptal</div>
  </div>
</section>

<section id="faq" class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <span class="badge rounded-pill" style="background:#eef2ff;color:#4338ca">SSS</span>
        <h3 class="fw-bold mt-2">Merak edilenler</h3>
        <p class="muted">Şirket kurmam gerekiyor mu? Veriler güvende mi?</p>
        <div class="p-3 rounded-4 mt-3" style="background:#f8fafc;border:1px solid #e2e8f0">
          <div class="fw-bold small"><i class="fa-solid fa-envelope me-1"></i> Hala sorun var mı?</div>
          <div class="small muted">info@residapro.com — 1 iş günü içinde dönüş</div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="accordion" id="faqAcc">
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#f1">Şirket kurmam gerekiyor mu?</button></h2><div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Hayır. Bireysel yönetici olarak kullanabilirsiniz. Tahsilat site IBAN'ına gider, fatura site adına kesilir.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">Ödeme nereye gider?</button></h2><div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Doğrudan site IBAN'ına. RESIDA'da toplanmaz. Havale → dekont onayı, kart → iyzico pazaryeri ile site hesabına.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f3">Excel'den nasıl geçeceğiz?</button></h2><div id="f3" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Sakinleri Excel'den içe aktarın, bir sonraki ayın aidatını tek tıkla oluşturun. Eski borçları elle girebilirsiniz. 10 dakikada hazır.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f4">Veriler güvende mi?</button></h2><div id="f4" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">KVKK uyumlu, 256-bit, HttpOnly + SameSite=Lax, CSRF token, oturum zaman aşımı ve audit log. Günlük yedek.</div></div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="cta p-4 p-lg-5">
      <div class="row align-items-center g-4 position-relative" style="z-index:1">
        <div class="col-lg-7">
          <h3 class="fw-bold mb-2">Excel'i bırakın. Aidatı otomatikleştirin.</h3>
          <p class="mb-0" style="color:#cbd5e1">Ödemeyi doğrudan site hesabına alın. Sakinlerinizi tek uygulamadan yönetin.</p>
          <div class="d-flex gap-2 mt-4 flex-wrap">
            <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#demoModal"><i class="fa-solid fa-rocket me-1"></i>Ücretsiz Dene</button>
            <a href="index.php" class="btn btn-outline-light px-4">Giriş Yap</a>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="bg-white rounded-4 p-3 text-dark">
            <div class="fw-bold">15 dakikada canlı tanıtım</div>
            <div class="small muted">info@residapro.com</div>
            <a href="index.php" class="btn btn-dark w-100 mt-3">Hemen Başla</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="py-4 border-top bg-white">
  <div class="container d-flex flex-wrap gap-3 justify-content-between small muted">
    <span>© <?= date('Y') ?> RESIDA PRO</span>
    <span class="d-flex gap-3"><a href="kvkk.php" class="link-secondary text-decoration-none">KVKK</a><a href="api/README.md" class="link-secondary text-decoration-none">API</a></span>
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
if(isStandalone){ installBtn.style.display='none'; } else if(isIos){ iosHint.style.display='block'; }
window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredPrompt=e;installBtn.style.display='inline-block';installBtn.classList.remove('btn-light');installBtn.classList.add('btn-warning');});
const insecureHint=document.getElementById('insecureHint');
const isInsecure = location.protocol==='http:' && location.hostname!=='localhost' && location.hostname!=='127.0.0.1';
const flagOriginEl=document.getElementById('flagOrigin');
const copyBtn=document.getElementById('copyOrigin');
if(flagOriginEl) flagOriginEl.textContent = location.origin;
if(copyBtn) copyBtn.addEventListener('click',()=>{ const o=location.origin; navigator.clipboard?.writeText(o); copyBtn.innerHTML='<i class="fa-solid fa-check me-1"></i> Kopyalandı'; setTimeout(()=>copyBtn.innerHTML='<i class="fa-solid fa-copy me-1"></i> Adresi kopyala',1500); });
const isWindows = /Win/.test(navigator.platform) || /Windows/.test(navigator.userAgent);
installBtn?.addEventListener('click',async()=>{
  if(deferredPrompt){ deferredPrompt.prompt(); const c=await deferredPrompt.userChoice; deferredPrompt=null; if(c.outcome==='accepted') installBtn.style.display='none'; return; }
  if(isIos){ iosHint.style.display='block'; iosHint.scrollIntoView({behavior:'smooth',block:'center'}); return; }
  if(isInsecure && insecureHint){ insecureHint.style.display='block'; insecureHint.scrollIntoView({behavior:'smooth',block:'center'}); return; }
  if(isWindows){ alert('Windows Chrome/Edge: adres çubuğu sağındaki "Yükle" ikonuna veya sağ üst ⋮ → "Uygulamayı yükle / Install app"e tıklayın.'); } else { alert('Android Chrome: sağ üst ⋮ → "Uygulamayı yükle"ye dokunun.'); }
});
window.addEventListener('appinstalled',()=>{ if(installBtn) installBtn.style.display='none';});
</script>
</body>
</html>
