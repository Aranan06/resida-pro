<?php require_once 'includes/config.php';
require_once 'includes/functions.php';
track_visit($pdo,'blog');
$cats=['aidat'=>'Aidat','yonetici'=>'Yönetici','yazilim'=>'Yazılım','odeme'=>'Ödeme'];
$cat=$_GET['kategori']??'all'; $q=trim($_GET['q']??'');
$where=["is_published=1"]; $params=[];
if(isset($cats[$cat])){ $where[]="category=?"; $params[]=$cat; }
if($q!==''){ $where[]="(title LIKE ? OR content LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$sql="SELECT id,slug,category,title,excerpt,published_at,created_at,views FROM blog_posts WHERE ".implode(' AND ',$where)." ORDER BY published_at DESC, id DESC";
try{ $st=$pdo->prepare($sql); $st->execute($params); $posts=$st->fetchAll(); }catch(Exception $e){ $posts=[]; }
function readingTime($html){ $w=str_word_count(strip_tags($html)); return max(1,(int)ceil($w/200)); }
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog – Apartman ve Site Yönetimi Rehberi | RESIDA PRO</title>
<meta name="description" content="Aidat takibi, apartman yönetimi, site yönetim programı ve online aidat ödeme hakkında rehber yazılar.">
<meta name="keywords" content="apartman yönetim programı, site yönetim programı, aidat takip programı, apartman aidatı, site aidat takip">
<link rel="canonical" href="https://residapro.com/blog.php">
<meta property="og:type" content="website">
<meta property="og:title" content="RESIDA PRO Blog – Apartman ve Site Yönetimi Rehberi">
<meta property="og:description" content="Aidat, yönetici, yazılım ve ödeme konularında pratik rehberler.">
<meta property="og:url" content="https://residapro.com/blog.php">
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;color:#0f172a}
.navbar{background:rgba(15,23,42,.94)!important}
.hero-sm{background:linear-gradient(180deg,#0f172a,#1e1b4b);color:#fff;padding:56px 0 44px}
.section-title{font-weight:900;letter-spacing:-.02em}
.post-card{background:#fff;border:1px solid #e2e8f0;border-radius:18px;overflow:hidden;height:100%;transition:.2s;display:flex;flex-direction:column}
.post-card:hover{transform:translateY(-3px);box-shadow:0 14px 34px rgba(15,23,42,.1)}
.post-body{padding:20px;display:flex;flex-direction:column;flex-grow:1}
.cat-badge{font-size:.7rem;font-weight:800;border-radius:999px;padding:4px 10px;letter-spacing:.03em}
.cat-aidat{background:#dcfce7;color:#14532d}.cat-yonetici{background:#eef2ff;color:#4338ca}.cat-yazilim{background:#fef3c7;color:#92400e}.cat-odeme{background:#e0f2fe;color:#075985}
.filter-pill{border-radius:999px;padding:7px 16px;font-size:.85rem;font-weight:600;border:1px solid #e2e8f0;background:#fff;color:#475569;text-decoration:none}
.filter-pill.active{background:#0f172a;color:#fff;border-color:#0f172a}
.muted{color:#64748b}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="landing.php"><img src="assets/img/resida-pro-logo2.png" alt="RESIDA PRO" style="height:26px" class="me-2" onerror="this.style.display='none'">RESIDA PRO</a>
    <div class="ms-auto d-flex gap-2">
      <a href="landing.php" class="btn btn-outline-light btn-sm">Ana Sayfa</a>
      <a href="index.php" class="btn btn-primary btn-sm">Giriş</a>
    </div>
  </div>
</nav>
<header class="hero-sm">
  <div class="container text-center">
    <h1 class="section-title">Apartman ve Site Yönetimi Rehberi</h1>
    <p style="color:#cbd5e1" class="mx-auto" style="max-width:640px">Aidat, yönetici, yazılım ve ödeme konularında pratik, sade anlatımlı rehberler.</p>
    <form class="d-flex justify-content-center mt-3" method="get">
      <?php if(isset($cats[$cat])): ?><input type="hidden" name="kategori" value="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
      <input type="search" name="q" class="form-control" style="max-width:380px" placeholder="Yazı ara..." value="<?= htmlspecialchars($q) ?>">
      <button class="btn btn-primary ms-2"><i class="fa-solid fa-search"></i></button>
    </form>
  </div>
</header>
<section class="py-5"><div class="container">
  <div class="d-flex flex-wrap gap-2 justify-content-center mb-4">
    <a href="blog.php" class="filter-pill <?= $cat==='all'?'active':'' ?>">Tümü</a>
    <?php foreach($cats as $k=>$lbl): ?><a href="blog.php?kategori=<?= $k ?>" class="filter-pill <?= $cat===$k?'active':'' ?>"><?= $lbl ?></a><?php endforeach; ?>
  </div>
  <?php if($posts): ?>
  <div class="row g-4">
    <?php foreach($posts as $p): ?>
    <div class="col-md-6 col-lg-4"><a href="blog-detay.php?yazi=<?= htmlspecialchars($p['slug']) ?>" class="text-decoration-none text-dark"><div class="post-card">
      <div class="post-body">
        <div class="mb-2"><span class="cat-badge cat-<?= htmlspecialchars($p['category']) ?>"><?= htmlspecialchars($cats[$p['category']]??$p['category']) ?></span></div>
        <h5 class="fw-bold"><?= htmlspecialchars($p['title']) ?></h5>
        <p class="small muted flex-grow-1"><?= htmlspecialchars($p['excerpt']??'') ?></p>
        <div class="small muted d-flex gap-3"><span><i class="fa-solid fa-calendar me-1"></i><?= htmlspecialchars($p['published_at']?:date('d.m.Y',strtotime($p['created_at']))) ?></span><span><i class="fa-solid fa-clock me-1"></i><?= readingTime($p['excerpt']??'') ?> dk</span><span><i class="fa-solid fa-eye me-1"></i><?= (int)$p['views'] ?></span></div>
      </div>
    </div></a></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?><div class="text-center muted py-5"><i class="fa-solid fa-newspaper fs-1 d-block mb-3 opacity-50"></i>Henüz yazı yok.</div><?php endif; ?>
</div></section>
<footer class="py-4 border-top bg-white"><div class="container d-flex flex-wrap gap-3 justify-content-between small muted">
<span>© <?= date('Y') ?> RESIDA PRO</span>
<span class="d-flex gap-3"><a href="landing.php" class="link-secondary text-decoration-none">Ana Sayfa</a><a href="kvkk.php" class="link-secondary text-decoration-none">KVKK</a><a href="https://instagram.com/residapro" target="_blank" class="link-secondary text-decoration-none"><i class="fa-brands fa-instagram"></i></a></span>
</div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('service-worker.js').catch(()=>{});}</script>
</body>
</html>
