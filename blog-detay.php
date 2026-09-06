<?php require_once 'includes/config.php';
require_once 'includes/functions.php';
track_visit($pdo,'blog');
$cats=['aidat'=>'Aidat','yonetici'=>'Yönetici','yazilim'=>'Yazılım','odeme'=>'Ödeme'];
$slug=trim($_GET['yazi']??'');
$post=null;
if($slug!==''){
  try{
    $st=$pdo->prepare("SELECT * FROM blog_posts WHERE slug=? AND is_published=1 LIMIT 1"); $st->execute([$slug]); $post=$st->fetch();
    if($post) $pdo->prepare("UPDATE blog_posts SET views=views+1 WHERE id=?")->execute([$post['id']]);
  }catch(Exception $e){ $post=null; }
}
if(!$post){ http_response_code(404); }
$related=[];
if($post){
  try{
    $st=$pdo->prepare("SELECT slug,title,category,published_at FROM blog_posts WHERE category=? AND id!=? AND is_published=1 ORDER BY published_at DESC LIMIT 3"); $st->execute([$post['category'],$post['id']]); $related=$st->fetchAll();
  }catch(Exception $e){}
}
$title=$post?($post['meta_title']?:$post['title']):'Yazı bulunamadı';
$desc=$post?($post['meta_desc']?:$post['excerpt']):'';
$url='https://residapro.com/blog-detay.php?yazi='.urlencode($slug);
?><!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?> | RESIDA PRO Blog</title>
<meta name="description" content="<?= htmlspecialchars($desc) ?>">
<link rel="canonical" href="<?= htmlspecialchars($url) ?>">
<meta property="og:type" content="article">
<meta property="og:title" content="<?= htmlspecialchars($title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
<meta property="og:url" content="<?= htmlspecialchars($url) ?>">
<meta name="twitter:card" content="summary">
<?php if($post): ?><script type="application/ld+json">{"@context":"https://schema.org","@type":"Article","headline":<?= json_encode($post['title'],JSON_UNESCAPED_UNICODE) ?>,"description":<?= json_encode($post['excerpt']??'',JSON_UNESCAPED_UNICODE) ?>,"datePublished":"<?= htmlspecialchars($post['published_at']??$post['created_at']) ?>","author":{"@type":"Organization","name":"RESIDA PRO"}}</script><?php endif; ?>
<link rel="icon" href="favicon.ico" type="image/x-icon">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{font-family:Inter,system-ui,sans-serif;background:#f8fafc;color:#0f172a;line-height:1.75}
.navbar{background:rgba(15,23,42,.94)!important}
.article{max-width:780px;margin:40px auto;background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:clamp(20px,4vw,48px)}
.article h1{font-weight:900;letter-spacing:-.02em;font-size:clamp(1.5rem,3.4vw,2.2rem)}
.article h2{font-size:1.25rem;font-weight:800;margin-top:32px}
.article h3{font-size:1.05rem;font-weight:700;margin-top:24px}
.article ul{padding-left:20px}
.article li{margin-bottom:6px}
.cat-badge{font-size:.72rem;font-weight:800;border-radius:999px;padding:4px 12px}
.cat-aidat{background:#dcfce7;color:#14532d}.cat-yonetici{background:#eef2ff;color:#4338ca}.cat-yazilim{background:#fef3c7;color:#92400e}.cat-odeme{background:#e0f2fe;color:#075985}
.cta-box{background:linear-gradient(180deg,#0f172a,#1e1b4b);color:#fff;border-radius:20px;padding:28px}
.muted{color:#64748b}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-bold" href="landing.php"><img src="assets/img/resida-pro-logo2.png" alt="RESIDA PRO" style="height:26px" class="me-2" onerror="this.style.display='none'">RESIDA PRO</a>
    <div class="ms-auto d-flex gap-2">
      <a href="blog.php" class="btn btn-outline-light btn-sm">Blog</a>
      <a href="index.php" class="btn btn-primary btn-sm">Giriş</a>
    </div>
  </div>
</nav>
<div class="container">
<?php if($post): ?>
<article class="article">
  <a href="blog.php" class="small text-decoration-none">← Tüm yazılar</a>
  <div class="mt-2 mb-2"><span class="cat-badge cat-<?= htmlspecialchars($post['category']) ?>"><?= htmlspecialchars($cats[$post['category']]??$post['category']) ?></span></div>
  <h1><?= htmlspecialchars($post['title']) ?></h1>
  <div class="small muted mb-4"><i class="fa-solid fa-calendar me-1"></i><?= htmlspecialchars($post['published_at']?:date('d.m.Y',strtotime($post['created_at']))) ?> · <i class="fa-solid fa-eye me-1"></i><?= (int)$post['views'] ?> görüntülenme</div>
  <div><?= $post['content'] ?></div>
  <div class="cta-box mt-5 text-center">
    <h4 class="fw-bold">Excel'i bırakın, aidatı otomatikleştirin.</h4>
    <p class="mb-3" style="color:#cbd5e1">RESIDA PRO ile aidat, gider ve tahsilatı tek panelden yönetin.</p>
    <a href="landing.php" class="btn btn-primary px-4">Ücretsiz Başlayın</a>
  </div>
</article>
<?php if($related): ?>
<div style="max-width:780px;margin:0 auto 40px">
  <h5 class="fw-bold mb-3">İlgili yazılar</h5>
  <div class="row g-3">
    <?php foreach($related as $r): ?>
    <div class="col-md-4"><a href="blog-detay.php?yazi=<?= htmlspecialchars($r['slug']) ?>" class="text-decoration-none text-dark"><div class="bg-white border rounded-4 p-3 h-100"><div class="small fw-bold" style="color:#6366f1"><?= htmlspecialchars($cats[$r['category']]??'') ?></div><div class="fw-bold small mt-1"><?= htmlspecialchars($r['title']) ?></div></div></a></div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<?php else: ?>
<div class="text-center py-5"><h3 class="fw-bold mt-5">Yazı bulunamadı</h3><p class="muted">Aradığınız yazı yayından kaldırılmış olabilir.</p><a href="blog.php" class="btn btn-primary">Blog'a dön</a></div>
<?php endif; ?>
</div>
<footer class="py-4 border-top bg-white mt-4"><div class="container d-flex flex-wrap gap-3 justify-content-between small muted">
<span>© <?= date('Y') ?> RESIDA PRO</span>
<span class="d-flex gap-3"><a href="landing.php" class="link-secondary text-decoration-none">Ana Sayfa</a><a href="kvkk.php" class="link-secondary text-decoration-none">KVKK</a></span>
</div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
