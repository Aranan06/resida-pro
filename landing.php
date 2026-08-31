<?php require_once 'includes/config.php';
try{ $plans=$pdo->query("SELECT * FROM subscription_plans WHERE is_active=1 ORDER BY price_monthly")->fetchAll(); }catch(Exception $e){ $plans=[]; }
if(!$plans){ $plans=[
  ['name'=>'Mini','max_residents'=>20,'price_monthly'=>149,'price_yearly'=>1490,'features'=>'["20 daireye kadar","Temel aidat takibi","WhatsApp hatırlatma","Dekont yükleme"]'],
  ['name'=>'Standart','max_residents'=>100,'price_monthly'=>349,'price_yearly'=>3490,'features'=>'["100 daireye kadar","Otomatik aidat oluşturma","Gider & kasa takibi","Gecikme faizi otomatiği","Blok/Cadde hiyerarşisi","Mobil API"]'],
  ['name'=>'Pro','max_residents'=>0,'price_monthly'=>599,'price_yearly'=>5990,'features'=>'["Sınırsız daire & site","Öncelikli destek","Gelişmiş rapor & PDF","iyzico pazaryeri","Çoklu yönetici","KVKK loglama"]'],
]; }
$stats = ['sites'=>'1.200+','daire'=>'85.000+','tahsilat'=>'%98','destek'=>'7/24'];
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RESIDA PRO – Apartman & Site Aidat Yönetimi | Blok, Faiz, Dekont, iyzico</title>
<meta name="description" content="Apartman ve site aidatını tek panelde yönet: blok hiyerarşisi, otomatik faiz, site IBAN'ına tahsilat, dekont onayı, WhatsApp hatırlatma ve mobil API.">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#0f172a">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&family=JetBrains+Mono:wght@600&display=swap" rel="stylesheet">
<style>
:root{--indigo:#6366f1;--indigo2:#4f46e5;--dark:#0f172a;--dark2:#1e1b4b;--muted:#64748b}
body{font-family:Inter,sans-serif;background:#f8fafc;color:#0f172a;overflow-x:hidden}
.navbar{backdrop-filter:blur(10px);background:rgba(15,23,42,.92)!important;border-bottom:1px solid rgba(255,255,255,.06)}
.hero{background:radial-gradient(1200px 600px at 70% -10%, #3730a3 0%, transparent 60%), radial-gradient(900px 500px at 0% 0%, #1e1b4b 0%, #0f172a 55%, #020617 100%);color:white;padding:42px 0 0;position:relative;overflow:hidden}
.hero::after{content:"";position:absolute;inset:auto -20% -40% -20%;height:55%;background:linear-gradient(180deg, transparent, rgba(255,255,255,.04));pointer-events:none}
.hero h1{font-weight:900;font-size:clamp(2rem,4.5vw,3.2rem);line-height:1.05;letter-spacing:-.03em}
.hero h1 span{background:linear-gradient(90deg,#a5b4fc,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.hero .lead{color:#cbd5e1;font-size:1.06rem}
.btn-primary{background:var(--indigo);border-color:var(--indigo);box-shadow:0 10px 28px rgba(99,102,241,.35)}
.btn-primary:hover{background:var(--indigo2);border-color:var(--indigo2)}
.badge-soft{background:rgba(99,102,241,.14);border:1px solid rgba(99,102,241,.28);color:#c7d2fe;border-radius:999px;padding:6px 12px;font-size:.78rem}
.mock-wrap{position:relative;filter:drop-shadow(0 30px 60px rgba(0,0,0,.45));transform:rotate(-.6deg)}
.mock{border-radius:22px;overflow:hidden;background:#0b1224;border:1px solid rgba(255,255,255,.08);box-shadow:inset 0 1px 0 rgba(255,255,255,.08)}
.mock-top{height:42px;background:linear-gradient(180deg,#141e3a,#0f172a);border-bottom:1px solid rgba(255,255,255,.07);display:flex;align-items:center;gap:8px;padding:0 14px}
.dot{width:9px;height:9px;border-radius:50%}
.mock-body{padding:14px;background:linear-gradient(180deg,#0f172a,#0b1224)}
.kpi{border-radius:16px;background:linear-gradient(180deg,#111c36,#0e1a33);border:1px solid rgba(255,255,255,.06);padding:14px}
.kpi small{color:#94a3b8;letter-spacing:.04em}
.kpi b{font-family:JetBrains Mono,monospace}
.trow{border-radius:14px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);padding:10px 12px}
.pill{border-radius:999px;padding:4px 8px;font-size:.72rem;border:1px solid rgba(255,255,255,.12)}
.trust{border-top:1px solid rgba(255,255,255,.06);border-bottom:1px solid rgba(255,255,255,.06);background:rgba(255,255,255,.02)}
.feature-icon{width:44px;height:44px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg,#6366f1,#22d3ee);color:white;box-shadow:0 8px 20px rgba(99,102,241,.35)}
.feature-card{border-radius:20px;background:white;border:1px solid #e2e8f0;box-shadow:0 10px 30px rgba(15,23,42,.06);transition:.2s;height:100%}
.feature-card:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(15,23,42,.10)}
.step{border-radius:20px;background:white;border:1px solid #e2e8f0;padding:20px;position:relative}
.step-num{position:absolute;top:-12px;left:20px;background:#0f172a;color:white;border-radius:999px;padding:4px 10px;font-weight:800;font-size:.75rem;letter-spacing:.04em}
.price-card{border-radius:20px;box-shadow:0 12px 36px rgba(15,23,42,.08);transition:.22s;background:white;border:1px solid #e2e8f0;position:relative;overflow:hidden}
.price-card:hover{transform:translateY(-5px)}
.price-card.featured{border-color:#6366f1;transform:scale(1.02);box-shadow:0 18px 44px rgba(99,102,241,.18)}
.price-card.featured::before{content:"";position:absolute;inset:0 0 auto 0;height:4px;background:linear-gradient(90deg,#6366f1,#22d3ee)}
.badge-pop{position:absolute;top:14px;right:14px;background:#0f172a;color:white;border-radius:999px;padding:5px 10px;font-size:.7rem;letter-spacing:.04em}
.section-title{font-weight:900;letter-spacing:-.02em}
.muted{color:var(--muted)}
.divider{height:1px;background:linear-gradient(90deg,transparent,#e2e8f0,transparent)}
.faq .accordion-button{font-weight:700}
.faq .accordion-button:not(.collapsed){background:#eef2ff;color:#3730a3}
.cta{border-radius:28px;background:radial-gradient(900px 300px at 80% 0%, #4338ca 0%, transparent 60%), linear-gradient(180deg,#0f172a,#0b1224);color:white;border:1px solid rgba(255,255,255,.08);overflow:hidden;position:relative}
.cta::after{content:"";position:absolute;inset:auto -20% -45% -20%;height:55%;background:linear-gradient(180deg,transparent,rgba(255,255,255,.05))}
@media(max-width:991px){.mock-wrap{transform:none;margin-top:18px}}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center" href="landing.php"><img src="assets/img/resida-pro-logo2.png" style="height:28px" class="me-2" onerror="this.style.display='none'">RESIDA PRO</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
    <div id="nav" class="collapse navbar-collapse">
      <div class="navbar-nav mx-auto gap-lg-2 small">
        <a class="nav-link" href="#features">Özellikler</a>
        <a class="nav-link" href="#how">Nasıl Çalışır</a>
        <a class="nav-link" href="#pricing">Paketler</a>
        <a class="nav-link" href="#faq">SSS</a>
        <a class="nav-link" href="kvkk.php">KVKK</a>
      </div>
      <div class="ms-auto d-flex gap-2">
        <a href="index.php" class="btn btn-outline-light btn-sm px-3"><i class="fa-solid fa-right-to-bracket me-1"></i>Giriş</a>
        <a href="index.php" class="btn btn-primary btn-sm px-3">Ücretsiz Dene</a>
      </div>
    </div>
  </div>
</nav>

<section class="hero">
  <div class="container position-relative" style="z-index:1">
    <div class="row align-items-center g-4 py-4 py-lg-5">
      <div class="col-lg-6">
        <span class="badge-soft d-inline-flex align-items-center gap-2 mb-3"><i class="fa-solid fa-sparkles"></i> Yeni — iyzico pazaryeri + site IBAN tahsilat</span>
        <h1>Aidat takibini <span>otomatikleştir</span>, tahsilatı garantile</h1>
        <p class="lead mt-3">Blok/Cadde hiyerarşisi, tek tık faiz, dekont onayı, WhatsApp hatırlatma ve sakin mobil API — tek panelde. Para sende toplanmaz, doğrudan <strong>site IBAN'ına</strong> gider.</p>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a href="index.php" class="btn btn-primary btn-lg px-4"><i class="fa-solid fa-rocket me-2"></i>Hemen Başla — Ücretsiz</a>
          <a href="#pricing" class="btn btn-outline-light btn-lg px-4">Paketleri Gör</a>
          <button id="installBtn" class="btn btn-light btn-lg px-3"><i class="fa-solid fa-download me-1"></i> Uygulamayı Yükle</button>
        </div>
        <div class="d-flex flex-wrap gap-3 mt-4 small">
          <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-shield-halved text-success"></i> KVKK uyumlu</span>
          <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-lock text-info"></i> 256-bit</span>
          <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-database text-warning"></i> Günlük yedek</span>
          <span class="d-flex align-items-center gap-2"><i class="fa-solid fa-mobile-screen text-primary"></i> PWA hazır</span>
        </div>
        <div id="iosHint" class="mt-3 small" style="display:none;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);padding:10px 12px;border-radius:12px;max-width:460px">📱 iPhone: Paylaş <i class="fa-solid fa-arrow-up-from-bracket"></i> → <strong>Ana Ekrana Ekle</strong> ile yükle</div>
        <div id="insecureHint" class="mt-3 small text-start" style="display:none;background:#fef3c7;border:1px solid #fde68a;padding:12px;border-radius:12px;max-width:520px;color:#92400e">
          <div class="fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i> Yerel ağda uygulama gibi kurulum için 1 adımlık izin gerekli</div>
          <div class="mt-1">Chrome bu sayfayı <code>http://</code> IP’den dolayı kısayol sayıyor. Aşağıdaki butona basıp açılan <code>chrome://flags</code> sayfasında <b>Insecure origins treated as secure</b> satırına <code id="flagOrigin"></code> yapıştırıp <b>Enabled</b> → <b>Relaunch</b> yap. Sonra tekrar <b>Uygulamayı Yükle</b> de — rozetsiz standalone kurulur.</div>
          <div class="d-flex gap-2 mt-2 flex-wrap">
            <a id="openFlags" href="chrome://flags/#unsafely-treat-insecure-origin-as-secure" class="btn btn-dark btn-sm"><i class="fa-solid fa-external-link me-1"></i> chrome://flags’ı aç</a>
            <button id="copyOrigin" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-copy me-1"></i> Adresi kopyala</button>
          </div>
          <div class="small mt-2" style="color:#78350f"><i class="fa-solid fa-circle-info me-1"></i> Canlıda <code>https://</code> olunca bu adım kalkar, tek tıkla uygulama gibi kurulur.</div>
        </div>
        <div class="row g-2 mt-4">
          <div class="col-6 col-md-3"><div class="kpi"><small>SİTE</small><div class="fw-bold fs-5"><?= $stats['sites'] ?></div><div class="small text-success"><i class="fa-solid fa-arrow-up me-1"></i>aktif</div></div></div>
          <div class="col-6 col-md-3"><div class="kpi"><small>DAİRE</small><div class="fw-bold fs-5"><?= $stats['daire'] ?></div><div class="small muted">yönetiliyor</div></div></div>
          <div class="col-6 col-md-3"><div class="kpi"><small>TAHSİLAT</small><div class="fw-bold fs-5"><?= $stats['tahsilat'] ?></div><div class="small text-info">zamanında</div></div></div>
          <div class="col-6 col-md-3"><div class="kpi"><small>DESTEK</small><div class="fw-bold fs-5"><?= $stats['destek'] ?></div><div class="small muted">yanınızda</div></div></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="mock-wrap">
          <div class="mock">
            <div class="mock-top"><span class="dot" style="background:#ef4444"></span><span class="dot" style="background:#f59e0b"></span><span class="dot" style="background:#22c55e"></span><span class="ms-2 small text-white-50">manager_panel — A Blok • Nisan 2026</span><span class="ms-auto pill text-white-50"><i class="fa-solid fa-circle text-success me-1" style="font-size:7px"></i> Canlı</span></div>
            <div class="mock-body">
              <div class="row g-2 mb-3">
                <div class="col-4"><div class="kpi py-2"><small>Tahsilat</small><div class="d-flex align-items-baseline gap-1"><b>₺284.600</b><span class="small text-success">+12%</span></div><div class="progress mt-2" style="height:6px"><div class="progress-bar" style="width:78%"></div></div></div></div>
                <div class="col-4"><div class="kpi py-2"><small>Gecikme</small><div><b>23</b> <span class="small muted">daire</span></div><span class="badge bg-warning text-dark mt-1">Faiz açık</span></div></div>
                <div class="col-4"><div class="kpi py-2"><small>Bekleyen</small><div><b>8</b> <span class="small muted">dekont</span></div><span class="badge bg-info mt-1">Onay bekliyor</span></div></div>
              </div>
              <div class="trow d-flex align-items-center gap-3 mb-2">
                <div class="feature-icon" style="width:36px;height:36px;border-radius:10px"><i class="fa-solid fa-building"></i></div>
                <div class="flex-grow-1"><div class="fw-bold small">A Blok — Daire 12 • Ayşe Yılmaz</div><div class="small muted">Nisan aidat • Son gün: 10.04.2026 • 1.250 ₺</div></div>
                <span class="badge bg-success">Ödendi</span>
              </div>
              <div class="trow d-flex align-items-center gap-3 mb-2">
                <div class="feature-icon" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#f59e0b,#ef4444)"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div class="flex-grow-1"><div class="fw-bold small">B Blok — Daire 7 • Mehmet Demir</div><div class="small muted">Mart aidat • 18 gün gecikme • 1.250 ₺ + 47 ₺ faiz</div></div>
                <span class="badge bg-danger">Gecikti</span>
              </div>
              <div class="trow d-flex align-items-center gap-3">
                <div class="feature-icon" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#06b6d4,#6366f1)"><i class="fa-solid fa-receipt"></i></div>
                <div class="flex-grow-1"><div class="fw-bold small">Dekont yüklendi — C Blok D:4</div><div class="small muted">TR00 0000 • 1.250 ₺ • Havale • 2 dk önce</div></div>
                <a class="btn btn-sm btn-primary">Onayla</a>
              </div>
              <div class="d-flex gap-2 mt-3">
                <span class="pill text-white-50"><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp hatırlatma</span>
                <span class="pill text-white-50"><i class="fa-solid fa-credit-card me-1"></i> iyzico</span>
                <span class="pill text-white-50"><i class="fa-solid fa-file-pdf me-1"></i> PDF rapor</span>
              </div>
            </div>
          </div>
          <div class="position-absolute d-none d-lg-block" style="right:-10px;bottom:-14px;background:white;border-radius:16px;padding:10px 12px;border:1px solid #e2e8f0;box-shadow:0 16px 40px rgba(0,0,0,.18)">
            <div class="small fw-bold"><i class="fa-solid fa-mobile-screen me-1 text-primary"></i> Sakin görünümü</div>
            <div class="small muted">Borç • 2.500 ₺ — <span class="text-success fw-bold">Öde</span> • Dekont yükle</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="trust py-3 mt-4">
    <div class="container d-flex flex-wrap gap-3 justify-content-between align-items-center small text-white-50">
      <span><i class="fa-solid fa-check me-1 text-success"></i> 1.200+ site RESIDA kullanıyor</span>
      <span class="d-none d-md-inline">—</span>
      <span><i class="fa-solid fa-building me-1"></i> Blok/Cadde tam hiyerarşi</span>
      <span><i class="fa-solid fa-percent me-1"></i> Tek tık faiz otomatiği</span>
      <span><i class="fa-solid fa-building-columns me-1"></i> Site IBAN'ına tahsilat</span>
      <span class="ms-auto d-none d-lg-inline-flex align-items-center gap-2"><i class="fa-solid fa-star text-warning"></i> 4.8/5 memnuniyet</span>
    </div>
  </div>
</section>

<script>
if('serviceWorker' in navigator){ navigator.serviceWorker.register('service-worker.js').catch(()=>{}); }
let deferredPrompt=null;
const installBtn=document.getElementById('installBtn');
const iosHint=document.getElementById('iosHint');
const isIos=/iPad|iPhone|iPod/.test(navigator.userAgent);
const isStandalone=window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone;
if(isStandalone){ installBtn.style.display='none'; }
else if(isIos){ iosHint.style.display='block'; }
window.addEventListener('beforeinstallprompt',e=>{e.preventDefault();deferredPrompt=e;installBtn.style.display='inline-block';installBtn.classList.remove('btn-light');installBtn.classList.add('btn-warning');});
const insecureHint=document.getElementById('insecureHint');
const isInsecure = location.protocol==='http:' && location.hostname!=='localhost' && location.hostname!=='127.0.0.1';
const flagOriginEl=document.getElementById('flagOrigin');
const copyBtn=document.getElementById('copyOrigin');
if(flagOriginEl) flagOriginEl.textContent = location.origin;
if(copyBtn) copyBtn.addEventListener('click',()=>{ const o=location.origin; navigator.clipboard?.writeText(o); copyBtn.innerHTML='<i class="fa-solid fa-check me-1"></i> Kopyalandı'; setTimeout(()=>copyBtn.innerHTML='<i class="fa-solid fa-copy me-1"></i> Adresi kopyala',1500); });
installBtn.addEventListener('click',async()=>{
  if(deferredPrompt){ deferredPrompt.prompt(); const c=await deferredPrompt.userChoice; deferredPrompt=null; if(c.outcome==='accepted') installBtn.style.display='none'; return; }
  if(isIos){ iosHint.style.display='block'; iosHint.scrollIntoView({behavior:'smooth',block:'center'}); return; }
  if(isInsecure && insecureHint){ insecureHint.style.display='block'; insecureHint.scrollIntoView({behavior:'smooth',block:'center'}); return; }
  alert('Android Chrome: sağ üst ⋮ → "Uygulamayı yükle" veya "Ana ekrana ekle"ye dokunun.\n\nNot: http://192.168.x.x ile rozet görünebilir, canlı https:// olduğunda uygulama gibi (adressesiz) açılır.');
});
window.addEventListener('appinstalled',()=>{installBtn.style.display='none';});
</script>

<section id="features" class="py-5">
  <div class="container">
    <div class="text-center mb-4">
      <span class="badge rounded-pill" style="background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe">Özellikler</span>
      <h2 class="section-title mt-2">Yönetim için her şey — tek panel</h2>
      <p class="muted mx-auto" style="max-width:720px">Excel'i bırak. Aidat, gider, duyuru, faiz, tahsilat ve rapor — hepsi otomatik, hepsi denetlenebilir. KVKK loglu, iyzico hazır.</p>
    </div>
    <div class="row g-3 g-md-4">
      <div class="col-md-6 col-lg-4"><div class="feature-card p-4"><div class="feature-icon"><i class="fa-solid fa-layer-group"></i></div><h5 class="fw-bold mt-3">Blok / Cadde / Kat</h5><p class="small muted">A/B/C blok, cadde-sokak ve kat/daire hiyerarşisi. Filtrele, raporla, blok bazlı aidat kes.</p><div class="small"><span class="badge bg-light text-dark border">A Blok</span> <span class="badge bg-light text-dark border">B Blok</span> <span class="badge bg-light text-dark border">Cadde</span></div></div></div>
      <div class="col-md-6 col-lg-4"><div class="feature-card p-4"><div class="feature-icon" style="background:linear-gradient(135deg,#f59e0b,#ef4444)"><i class="fa-solid fa-percent"></i></div><h5 class="fw-bold mt-3">Gecikme faizi — tek tık</h5><p class="small muted">Müdür açar, oran ve hoşgörü günü girer. Gece yarısı cron faizi işletir, PDF’e yansır.</p><div class="small muted"><i class="fa-solid fa-clock me-1"></i> Son gün + 5 gün hoşgörü</div></div></div>
      <div class="col-md-6 col-lg-4"><div class="feature-card p-4"><div class="feature-icon" style="background:linear-gradient(135deg,#10b981,#06b6d4)"><i class="fa-solid fa-building-columns"></i></div><h5 class="fw-bold mt-3">Site IBAN’ına tahsilat</h5><p class="small muted">Para sende toplanmaz. Havale → dekont onayı veya iyzico pazaryeri ile doğrudan site hesabına.</p><span class="badge" style="background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0">iyzico subMerchant</span></div></div>
      <div class="col-md-6 col-lg-4"><div class="feature-card p-4"><div class="feature-icon" style="background:linear-gradient(135deg,#8b5cf6,#ec4899)"><i class="fa-solid fa-receipt"></i></div><h5 class="fw-bold mt-3">Dekont onayı</h5><p class="small muted">Sakin dekont yükler (JPG/PNG/PDF), yönetici tek tık onaylar — borç kapanır, log tutulur.</p><div class="small muted"><i class="fa-solid fa-check-double me-1 text-success"></i> Onay → otomatik mahsup</div></div></div>
      <div class="col-md-6 col-lg-4"><div class="feature-card p-4"><div class="feature-icon" style="background:linear-gradient(135deg,#22c55e,#16a34a)"><i class="fa-brands fa-whatsapp"></i></div><h5 class="fw-bold mt-3">WhatsApp hatırlatma</h5><p class="small muted">Aidat oluşturulunca ve son güne 2 gün kala otomatik şablon mesaj — tek tık gönder.</p><span class="small muted">wa.me/?text= şablonu</span></div></div>
      <div class="col-md-6 col-lg-4"><div class="feature-card p-4"><div class="feature-icon" style="background:linear-gradient(135deg,#0ea5e9,#6366f1)"><i class="fa-solid fa-mobile-screen"></i></div><h5 class="fw-bold mt-3">Sakin paneli + API</h5><p class="small muted">Sakin borç görür, ekstre indirir, dekont yükler. Mobil API + token hazır.</p><code class="small">/api/dues.php?token=...</code></div></div>
    </div>
    <div class="row g-3 mt-2">
      <div class="col-md-4"><div class="p-3 rounded-4 bg-white border d-flex gap-3 align-items-center"><i class="fa-solid fa-file-shield text-primary fs-4"></i><div><div class="fw-bold small">KVKK & log</div><div class="small muted">Erişim, onay, silme — her işlem loglu</div></div></div></div>
      <div class="col-md-4"><div class="p-3 rounded-4 bg-white border d-flex gap-3 align-items-center"><i class="fa-solid fa-file-pdf text-danger fs-4"></i><div><div class="fw-bold small">PDF & rapor</div><div class="small muted">Aidat ekstre, gider özet tek tık PDF</div></div></div></div>
      <div class="col-md-4"><div class="p-3 rounded-4 bg-white border d-flex gap-3 align-items-center"><i class="fa-solid fa-chart-line text-success fs-4"></i><div><div class="fw-bold small">Kasa özeti</div><div class="small muted">Aylık tahsilat / gecikme / bekleyen</div></div></div></div>
    </div>
  </div>
</section>

<section id="how" class="py-5 bg-white border-top border-bottom">
  <div class="container">
    <div class="row align-items-end mb-4">
      <div class="col-lg-7"><h2 class="section-title">3 adımda kur, 2 dakikada tahsil et</h2><p class="muted mb-0">Şirketsiz. E-imza yok. Sadece siteyi ekle, sakinleri içe aktar, aidatı oluştur.</p></div>
      <div class="col-lg-5 text-lg-end mt-3 mt-lg-0"><a href="index.php" class="btn btn-dark px-4">Canlı demo — giriş yap</a></div>
    </div>
    <div class="row g-4">
      <div class="col-md-4"><div class="step h-100"><span class="step-num">01 — KUR</span><div class="feature-icon mt-2"><i class="fa-solid fa-building"></i></div><h5 class="fw-bold mt-3">Siteyi & blokları ekle</h5><p class="small muted">Site adı, bloklar, daireler. Excel’den toplu içe aktar. Yöneticiyi ata.</p><div class="small bg-light border rounded-3 p-2"><i class="fa-solid fa-file-csv me-1"></i> sakinler.xlsx → içe aktar</div></div></div>
      <div class="col-md-4"><div class="step h-100"><span class="step-num">02 — KES</span><div class="feature-icon mt-2" style="background:linear-gradient(135deg,#f59e0b,#ef4444)"><i class="fa-solid fa-coins"></i></div><h5 class="fw-bold mt-3">Aidatı oluştur</h5><p class="small muted">Aylık toplu oluştur, son gün belirle, isterse faizi aç. Tek tıkla tüm dairelere kesilir.</p><div class="small bg-light border rounded-3 p-2"><i class="fa-solid fa-calendar me-1"></i> Nisan 2026 • Son gün: 10.04</div></div></div>
      <div class="col-md-4"><div class="step h-100"><span class="step-num">03 — TAHSİL ET</span><div class="feature-icon mt-2" style="background:linear-gradient(135deg,#10b981,#06b6d4)"><i class="fa-solid fa-hand-holding-dollar"></i></div><h5 class="fw-bold mt-3">Onayla & raporla</h5><p class="small muted">Sakin havale yapar → dekont yükler → sen onayla. iyzico’da otomatik kapanır. PDF al.</p><div class="small bg-light border rounded-3 p-2"><i class="fa-solid fa-check me-1 text-success"></i> Onaylandı → borç kapandı</div></div></div>
    </div>
    <div class="row g-3 mt-4">
      <div class="col-lg-7"><div class="p-4 rounded-4" style="background:#f1f5f9;border:1px solid #e2e8f0">
        <h6 class="fw-bold"><i class="fa-solid fa-desktop me-2"></i> Yönetici paneli önizleme</h6>
        <div class="row g-2 mt-2 small">
          <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Dashboard</div><div class="muted">Tahsilat, gecikme, bekleyen</div></div></div>
          <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Aidatlar</div><div class="muted">Toplu oluştur, faiz, WhatsApp</div></div></div>
          <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Giderler</div><div class="muted">Kategori, makbuz, kasa</div></div></div>
          <div class="col-6"><div class="bg-white border rounded-3 p-2"><div class="fw-bold">Sakinler</div><div class="muted">Blok/daire, borç, duyuru</div></div></div>
        </div>
        <div class="small muted mt-2"><i class="fa-solid fa-circle-info me-1"></i> Gerçek ekranlar — <code>manager_panel.php</code> & <code>resident_panel.php</code></div>
      </div></div>
      <div class="col-lg-5"><div class="p-4 rounded-4 bg-dark text-white h-100">
        <h6 class="fw-bold"><i class="fa-solid fa-quote-left me-2 text-warning"></i> Yöneticiler ne diyor?</h6>
        <div class="mt-3">
          <div class="small" style="color:#cbd5e1">“Excel’den 3 saat süren iş 4 dakikaya indi. Faiz ve dekont onayı hayat kurtardı.”</div>
          <div class="small fw-bold mt-2">— A Blok Yöneticisi, 64 daire</div>
          <hr style="border-color:rgba(255,255,255,.12)">
          <div class="small" style="color:#cbd5e1">“Para artık bizde toplanmıyor, site IBAN’ına gidiyor. Şeffaf, denetlenebilir.”</div>
          <div class="small fw-bold mt-2">— Site Başkanlığı, 210 daire</div>
        </div>
        <div class="mt-3 d-flex gap-2"><span class="pill" style="border-color:rgba(255,255,255,.18)"><i class="fa-solid fa-star text-warning me-1"></i> 4.8/5</span><span class="pill" style="border-color:rgba(255,255,255,.18)">1.200+ site</span></div>
      </div></div>
    </div>
  </div>
</section>

<section id="pricing" class="py-5">
  <div class="container">
    <div class="text-center mb-2">
      <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a"><i class="fa-solid fa-crown me-1"></i> Paketler — dilediğinde yükselt</span>
      <h2 class="section-title mt-2">İhtiyacına göre seç, aylık öde</h2>
      <p class="muted">Aylık havale → admin onayıyla aktif. iyzico otomatik tahsilat hazır.</p>
    </div>
    <div class="d-flex justify-content-center gap-2 mb-4">
      <span class="small fw-bold">Aylık</span>
      <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="billToggle"><label class="form-check-label small muted" for="billToggle">Yıllık — 2 ay bedava</label></div>
    </div>
    <div class="row g-4">
      <?php foreach($plans as $i=>$p):
        $feat=json_decode($p['features']??'[]',true); if(!is_array($feat)) $feat=[];
        $isFeat=($i==1); $monthly=(float)$p['price_monthly']; $yearly=(float)($p['price_yearly'] ?? $monthly*10);
      ?>
      <div class="col-md-4"><div class="price-card p-4 h-100 <?= $isFeat?'featured':'' ?>">
        <?php if($isFeat): ?><span class="badge-pop"><i class="fa-solid fa-star me-1"></i>En Popüler</span><?php endif; ?>
        <h5 class="fw-bold"><?= htmlspecialchars($p['name']) ?><?php if($isFeat): ?> <span class="badge" style="background:#eef2ff;color:#4338ca">Önerilen</span><?php endif; ?></h5>
        <div class="small muted"><?= $p['max_residents']>0 ? (int)$p['max_residents'].' daireye kadar' : 'Sınırsız daire & site' ?></div>
        <div class="my-3">
          <div class="d-flex align-items-baseline gap-2">
            <span class="fs-2 fw-bold price-m" data-m="<?= number_format($monthly,0,',','.') ?>" data-y="<?= number_format($yearly,0,',','.') ?>"><?= number_format($monthly,0,',','.') ?> ₺</span><span class="muted price-suf">/ay</span>
          </div>
          <div class="small muted price-sub" data-m="Aylık faturalama" data-y="Yıllık peşin — ayda <?= number_format($yearly/12,0,',','.') ?> ₺'ye gelir">Aylık faturalama</div>
        </div>
        <ul class="small muted ps-3 mb-3"><?php foreach($feat as $f): ?><li><?= htmlspecialchars($f) ?></li><?php endforeach; ?></ul>
        <a href="index.php" class="btn <?= $isFeat?'btn-primary':'btn-outline-primary' ?> w-100"><i class="fa-solid fa-arrow-right me-1"></i>Başla</a>
        <div class="small muted text-center mt-2"><i class="fa-solid fa-lock me-1"></i> İstediğin zaman iptal</div>
      </div></div>
      <?php endforeach; ?>
    </div>
    <div class="text-center small muted mt-3">Fiyatlara KDV dahil değildir. Kurumsal fatura kesilir. Yıllıkta 2 ay avantajı otomatik uygulanır.</div>
    <div class="divider my-4"></div>
    <div class="row g-3 small">
      <div class="col-md-4"><div class="bg-white border rounded-4 p-3 h-100"><div class="fw-bold"><i class="fa-solid fa-building-columns me-1"></i> Tahsilat nereye gider?</div><div class="muted mt-1">Havale: site IBAN’ına. iyzico pazaryeri: para doğrudan site subMerchant’ına, RESIDA’da toplanmaz.</div></div></div>
      <div class="col-md-4"><div class="bg-white border rounded-4 p-3 h-100"><div class="fw-bold"><i class="fa-solid fa-shield-halved me-1"></i> KVKK & güvenlik</div><div class="muted mt-1">Yetki matrisi, oturum timeout, CSRF, audit log, günlük yedek. Veriler TR lokasyonda.</div></div></div>
      <div class="col-md-4"><div class="bg-white border rounded-4 p-3 h-100"><div class="fw-bold"><i class="fa-solid fa-headset me-1"></i> Kurulum desteği</div><div class="muted mt-1">Excel içe aktarımda yardımcı oluyoruz. Canlıya geçişte 30 dk onboarding.</div></div></div>
    </div>
  </div>
</section>

<section id="faq" class="py-5 bg-white border-top">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-5">
        <span class="badge rounded-pill" style="background:#eef2ff;color:#4338ca">SSS</span>
        <h3 class="fw-bold mt-2">Merak edilenler</h3>
        <p class="muted">En çok sorulan 6 soru. Daha fazlası için <a href="kvkk.php">KVKK</a> ve <a href="api/README.md">API dokümanı</a>.</p>
        <div class="p-3 rounded-4 mt-3" style="background:#f8fafc;border:1px solid #e2e8f0">
          <div class="fw-bold small"><i class="fa-solid fa-envelope me-1"></i> Hala sorun var mı?</div>
          <div class="small muted">info@residapro.com — 1 iş günü içinde dönüş</div>
          <div class="small muted">0532 XXX XX XX — Hafta içi 09:00-18:00</div>
        </div>
      </div>
      <div class="col-lg-7 faq">
        <div class="accordion" id="faqAcc">
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#f1">Şirket kurmam gerekiyor mu?</button></h2><div id="f1" class="accordion-collapse collapse show" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Hayır. RESIDA PRO’yu bireysel yönetici olarak kullanabilirsin. Tahsilat site IBAN’ına gider, fatura site adına kesilir. iyzico pazaryeri ile para sende toplanmaz.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f2">Aidat ve gecikme faizi nasıl işliyor?</button></h2><div id="f2" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Yönetici aidatı toplu oluşturur, son gün ve faiz oranını girer. Cron her gece hoşgörü günü sonrası faizi işletir. PDF ve kasa özetine yansır.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f3">Sakinler nasıl ödeme yapıyor?</button></h2><div id="f3" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">İki yol: havale/EFT → dekont yükle → yönetici onayla; veya iyzico kart → otomatik kapanır. Her ikisinde de para doğrudan site hesabına gider.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f4">Blok ve cadde desteği var mı?</button></h2><div id="f4" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">Evet. A/B/C blok, cadde-sokak, kat/daire hiyerarşisi tam. Filtre, rapor ve aidat kesimi blok bazlı yapılabilir.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f5">Veriler güvende mi?</button></h2><div id="f5" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">KVKK uyumlu, 256-bit, HttpOnly + SameSite=Lax çerez, CSRF token, oturum timeout ve audit log. Günlük yedek alınır.</div></div></div>
          <div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#f6">Mobil uygulama var mı?</button></h2><div id="f6" class="accordion-collapse collapse" data-bs-parent="#faqAcc"><div class="accordion-body small muted">PWA hazır — Android’de “Yükle”, iPhone’da “Ana Ekrana Ekle”. Sakinler borç görür, dekont yükler. Native API de mevcut.</div></div></div>
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
          <h3 class="fw-bold mb-2">Bugün kur, bu ay tahsil et</h3>
          <p class="mb-0" style="color:#cbd5e1">Excel’i kapat. 5 dakikada siteyi ekle, aidatı kes, WhatsApp ile hatırlat. İlk ay risksiz dene — memnun kalmazsan iptal et.</p>
          <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="index.php" class="btn btn-primary btn-lg px-4"><i class="fa-solid fa-rocket me-1"></i>Ücretsiz Başla</a>
            <a href="kvkk.php" class="btn btn-outline-light px-4">KVKK’yı İncele</a>
          </div>
          <div class="small mt-3" style="color:#94a3b8"><i class="fa-solid fa-check me-1 text-success"></i>Kredi kartı gerekmez • <i class="fa-solid fa-check ms-2 me-1 text-success"></i>Kurulum desteği • <i class="fa-solid fa-check ms-2 me-1 text-success"></i>Aynı gün aktif</div>
        </div>
        <div class="col-lg-5">
          <div class="bg-white rounded-4 p-3 text-dark">
            <div class="fw-bold"><i class="fa-solid fa-phone me-2 text-primary"></i> Demo iste</div>
            <div class="small muted">15 dakikada canlı tanıtım — sorularını yanıtlayalım.</div>
            <div class="mt-3 small"><div><i class="fa-solid fa-envelope me-2 muted"></i>info@residapro.com</div><div><i class="fa-solid fa-phone me-2 muted"></i>0532 XXX XX XX</div></div>
            <a href="index.php" class="btn btn-dark w-100 mt-3">Giriş yap & dene</a>
            <div class="small muted text-center mt-2">veya doğrudan kaydol</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<footer class="py-4 border-top bg-white">
  <div class="container d-flex flex-wrap gap-3 justify-content-between small muted">
    <span>© <?= date('Y') ?> RESIDA PRO · Tüm hakları saklıdır.</span>
    <span class="d-flex gap-3"><a href="kvkk.php" class="link-secondary text-decoration-none">KVKK</a><a href="api/README.md" class="link-secondary text-decoration-none">API</a><a href="index.php" class="link-secondary text-decoration-none">Giriş</a></span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('billToggle')?.addEventListener('change', function(){
  const yearly=this.checked;
  document.querySelectorAll('.price-m').forEach(el=> el.textContent = yearly ? el.dataset.y : el.dataset.m);
  document.querySelectorAll('.price-sub').forEach(el=> el.textContent = yearly ? el.dataset.y : el.dataset.m);
  document.querySelectorAll('.price-suf').forEach(el=> el.textContent = yearly ? '/ay (yıllık)' : '/ay');
});
</script>
</body>
</html>
