<?php
// admin_panel.php – Admin Paneli (dashboard.php tarafından çağrılır)
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (!isAdmin()) { header('Location: index.php'); exit; }

$page  = $_GET['page'] ?? 'dashboard';
$error = $success = '';

// ──────────────────────────────────────────────────────────
// POST İşlemleri
// ──────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // GÜVENLİK KONTROLÜ (Sadece bu satırı ekleyin)
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'];

  // Site Ekle
    if ($action === 'add_site') {
        $name = trim($_POST['site_name'] ?? '');
        $addr = trim($_POST['site_address'] ?? '');
        $max_residents = (int)($_POST['max_residents'] ?? 0);
        $bank_name = trim($_POST['bank_name'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $iban_holder = trim($_POST['iban_holder'] ?? '');
        if ($name) {
            $pdo->prepare("INSERT INTO sites (name, address, created_by, max_residents, bank_name, iban, iban_holder) VALUES (?,?,?,?,?,?,?)")
                ->execute([$name, $addr, $user['id'], $max_residents, $bank_name, $iban, $iban_holder]);
            $success = "\"$name\" sitesi başarıyla eklendi.";
        } else { $error = 'Site adı zorunludur.'; }

    // Site Düzenle
    } elseif ($action === 'edit_site') {
        $id   = (int)$_POST['site_id'];
        $name = trim($_POST['site_name'] ?? '');
        $addr = trim($_POST['site_address'] ?? '');
        $max_residents = (int)($_POST['max_residents'] ?? 0);
        $bank_name = trim($_POST['bank_name'] ?? '');
        $iban = trim($_POST['iban'] ?? '');
        $iban_holder = trim($_POST['iban_holder'] ?? '');
        if ($id && $name) {
            $pdo->prepare("UPDATE sites SET name=?, address=?, max_residents=?, bank_name=?, iban=?, iban_holder=? WHERE id=?")
                ->execute([$name, $addr, $max_residents, $bank_name, $iban, $iban_holder, $id]);
            $success = 'Site bilgileri güncellendi.';
        } else { $error = 'Site adı boş olamaz.'; }
    // Site Sil
    } elseif ($action === 'delete_site') {
        $id = (int)$_POST['site_id'];
        try {
            $pdo->prepare("DELETE FROM sites WHERE id=?")->execute([$id]);
            $success = 'Site silindi.';
        } catch (PDOException $e) {
            $error = 'Bu siteye bağlı yönetici veya sakin var. Önce onları silin.';
        }

    // Yönetici Ekle
    } elseif ($action === 'add_manager') {
        $siteId   = (int)($_POST['site_id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        if ($siteId && $name && $username && $password) {
            try {
                $pdo->prepare("INSERT INTO users (username,password,role,name,site_id,phone,email) VALUES (?,?,'manager',?,?,?,?)")
                    ->execute([$username, password_hash($password, PASSWORD_BCRYPT), $name, $siteId, $phone, $email]);
                $success = "$name yöneticisi oluşturuldu.";
            } catch (PDOException $e) {
                $error = $e->getCode() == 23000 ? 'Bu kullanıcı adı zaten kullanımda.' : 'Hata: '.$e->getMessage();
            }
        } else { $error = 'Tüm zorunlu alanları doldurun.'; }

    // Yönetici Düzenle
    } elseif ($action === 'edit_manager') {
        $id       = (int)$_POST['manager_id'];
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $siteId   = (int)$_POST['site_id'];
        $phone    = trim($_POST['phone'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        try {
            if (!empty($_POST['password'])) {
                $pdo->prepare("UPDATE users SET name=?,username=?,site_id=?,phone=?,email=?,password=? WHERE id=? AND role='manager'")
                    ->execute([$name, $username, $siteId, $phone, $email, password_hash($_POST['password'], PASSWORD_BCRYPT), $id]);
            } else {
                $pdo->prepare("UPDATE users SET name=?,username=?,site_id=?,phone=?,email=? WHERE id=? AND role='manager'")
                    ->execute([$name, $username, $siteId, $phone, $email, $id]);
            }
            $success = 'Yönetici bilgileri güncellendi.';
        } catch (PDOException $e) {
            $error = $e->getCode() == 23000 ? 'Bu kullanıcı adı zaten kullanımda.' : 'Hata: '.$e->getMessage();
        }

    // Yönetici Sil
    } elseif ($action === 'delete_manager') {
        $id = (int)$_POST['manager_id'];
        $pdo->prepare("DELETE FROM users WHERE id=? AND role='manager'")->execute([$id]);
        $success = 'Yönetici silindi.';

    // Paket Ekle / Düzenle / Sil
    } elseif ($action === 'add_plan') {
        $name=trim($_POST['plan_name']??''); $maxR=(int)($_POST['max_residents']??0); $priceM=$_POST['price_monthly']??0; $priceY=$_POST['price_yearly']??null;
        if($name&&$priceM){ $pdo->prepare("INSERT INTO subscription_plans (name,max_residents,price_monthly,price_yearly) VALUES (?,?,?,?)")->execute([$name,$maxR,$priceM,$priceY?:null]); $success='Paket eklendi.'; } else $error='Paket adı ve aylık fiyat zorunlu.';
    } elseif ($action === 'edit_plan') {
        $id=(int)$_POST['plan_id']; $name=trim($_POST['plan_name']??''); $maxR=(int)($_POST['max_residents']??0); $priceM=$_POST['price_monthly']??0; $priceY=$_POST['price_yearly']??null;
        if($id&&$name){ $pdo->prepare("UPDATE subscription_plans SET name=?, max_residents=?, price_monthly=?, price_yearly=? WHERE id=?")->execute([$name,$maxR,$priceM,$priceY?:null,$id]); $success='Paket güncellendi.'; }
    } elseif ($action === 'delete_plan') {
        $id=(int)$_POST['plan_id']; try{ $pdo->prepare("DELETE FROM subscription_plans WHERE id=?")->execute([$id]); $success='Paket silindi.'; }catch(PDOException $e){ $error='Bu paketi kullanan abonelik var, silemezsiniz.'; }
    } elseif ($action === 'activate_subscription') {
        $siteId=(int)$_POST['site_id']; $planId=(int)$_POST['plan_id'];
        $start=date('Y-m-d'); $end=date('Y-m-d', strtotime('+1 month'));
        // Eski aktif abonelikleri kapat
        $pdo->prepare("UPDATE site_subscriptions SET status='expired' WHERE site_id=? AND status='active'")->execute([$siteId]);
        $pdo->prepare("INSERT INTO site_subscriptions (site_id, plan_id, status, current_period_start, current_period_end) VALUES (?,?,?,?,?)")->execute([$siteId,$planId,'active',$start,$end]);
        $success='Paket siteye tanımlandı ve abonelik aktifleştirildi.';
    } elseif ($action === 'assign_plan_to_site') {
        $siteId=(int)$_POST['site_id']; $planId=(int)$_POST['plan_id'];
        $start=date('Y-m-d'); $end=date('Y-m-d', strtotime('+1 month'));
        $pdo->prepare("UPDATE site_subscriptions SET status='expired' WHERE site_id=? AND status='active'")->execute([$siteId]);
        $pdo->prepare("INSERT INTO site_subscriptions (site_id, plan_id, status, current_period_start, current_period_end) VALUES (?,?,?,?,?)")->execute([$siteId,$planId,'active',$start,$end]);
        $success='Paket siteye tanımlandı.';
    } elseif ($action === 'delete_subscription') {
        $sid=(int)($_POST['subscription_id']??0);
        if($sid){ $pdo->prepare("DELETE FROM site_subscriptions WHERE id=?")->execute([$sid]); $success='Abonelik silindi.'; }
    } elseif ($action === 'extend_subscription') {
        $sid=(int)($_POST['subscription_id']??0); $days=(int)($_POST['extend_days']??30);
        if($sid && $days>0){ $pdo->prepare("UPDATE site_subscriptions SET current_period_end=DATE_ADD(current_period_end, INTERVAL ? DAY), status='active', updated_at=NOW() WHERE id=?")->execute([$days,$sid]); $success="Abonelik $days gün uzatıldı."; }
    } elseif ($action === 'approve_payment') {
        $pid=(int)($_POST['payment_id']??0);
        if($pid){ require_once 'includes/PaymentGateway.php'; try{ approvePayment($pdo,$pid,$user['id']); $success='Ödeme onaylandı, abonelik aktif.'; }catch(Exception $e){ $error=$e->getMessage(); } }
    } elseif ($action === 'reject_payment') {
        $pid=(int)($_POST['payment_id']??0); $reason=trim($_POST['reason']??'');
        if($pid){ require_once 'includes/PaymentGateway.php'; rejectPayment($pdo,$pid,$user['id'],$reason); $success='Ödeme reddedildi.'; }
    } elseif ($action === 'landing_save_settings') {
        foreach(($_POST['settings']??[]) as $k=>$v){
            $k=preg_replace('/[^a-z0-9_]/','',strtolower(trim($k))); if($k==='') continue;
            $pdo->prepare("INSERT INTO landing_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$k, is_string($v)?trim($v):$v]);
        }
        $upDir=__DIR__.'/assets/img/landing'; if(!is_dir($upDir)) mkdir($upDir,0777,true);
        foreach(['nav_logo','hero_image'] as $f){
            if(!empty($_FILES[$f]['tmp_name']) && empty($_FILES[$f]['error'])){
                $ext=strtolower(pathinfo($_FILES[$f]['name'],PATHINFO_EXTENSION));
                if(in_array($ext,['jpg','jpeg','png','webp']) && $_FILES[$f]['size']<=2*1024*1024){
                    foreach(glob($upDir.'/'.$f.'.*') as $old) @unlink($old);
                    $dest=$upDir.'/'.$f.'.'.$ext;
                    if(move_uploaded_file($_FILES[$f]['tmp_name'],$dest)){
                        $pdo->prepare("INSERT INTO landing_settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)")->execute([$f,'assets/img/landing/'.$f.'.'.$ext]);
                    }
                } else { $error='Görsel JPG/PNG/WEBP ve en fazla 2MB olmalı.'; }
            }
        }
        if(!$error) $success='Site içeriği güncellendi.';
    } elseif ($action === 'landing_menu_add') {
        $lb=trim($_POST['label']??''); $url=trim($_POST['url']??''); $so=(int)($_POST['sort_order']??0);
        if($lb&&$url){ $pdo->prepare("INSERT INTO landing_menu (label,url,sort_order,is_active) VALUES (?,?,?,1)")->execute([$lb,$url,$so]); $success='Menü eklendi.'; } else $error='Menü adı ve bağlantı zorunlu.';
    } elseif ($action === 'landing_menu_edit') {
        $id=(int)($_POST['menu_id']??0); $lb=trim($_POST['label']??''); $url=trim($_POST['url']??''); $so=(int)($_POST['sort_order']??0);
        if($id&&$lb&&$url){ $pdo->prepare("UPDATE landing_menu SET label=?,url=?,sort_order=? WHERE id=?")->execute([$lb,$url,$so,$id]); $success='Menü güncellendi.'; }
    } elseif ($action === 'landing_menu_delete') {
        $pdo->prepare("DELETE FROM landing_menu WHERE id=?")->execute([(int)($_POST['menu_id']??0)]); $success='Menü silindi.';
    } elseif ($action === 'landing_menu_toggle') {
        $pdo->prepare("UPDATE landing_menu SET is_active=1-is_active WHERE id=?")->execute([(int)($_POST['menu_id']??0)]); $success='Menü durumu değişti.';
    } elseif ($action === 'landing_faq_add') {
        $q=trim($_POST['question']??''); $a=trim($_POST['answer']??''); $so=(int)($_POST['sort_order']??0);
        if($q&&$a){ $pdo->prepare("INSERT INTO landing_faq (question,answer,sort_order,is_active) VALUES (?,?,?,1)")->execute([$q,$a,$so]); $success='SSS eklendi.'; } else $error='Soru ve cevap zorunlu.';
    } elseif ($action === 'landing_faq_edit') {
        $id=(int)($_POST['faq_id']??0); $q=trim($_POST['question']??''); $a=trim($_POST['answer']??''); $so=(int)($_POST['sort_order']??0);
        if($id&&$q&&$a){ $pdo->prepare("UPDATE landing_faq SET question=?,answer=?,sort_order=? WHERE id=?")->execute([$q,$a,$so,$id]); $success='SSS güncellendi.'; }
    } elseif ($action === 'landing_faq_delete') {
        $pdo->prepare("DELETE FROM landing_faq WHERE id=?")->execute([(int)($_POST['faq_id']??0)]); $success='SSS silindi.';
    } elseif ($action === 'landing_faq_toggle') {
        $pdo->prepare("UPDATE landing_faq SET is_active=1-is_active WHERE id=?")->execute([(int)($_POST['faq_id']??0)]); $success='SSS durumu değişti.';
    }
}

// ──────────────────────────────────────────────────────────
// Veri Çekme
// ──────────────────────────────────────────────────────────
$sites    = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM users WHERE site_id=s.id AND role='resident') AS resident_count FROM sites s ORDER BY s.name")->fetchAll();
$managers = getSiteManagers($pdo);
try{ $plans=$pdo->query("SELECT * FROM subscription_plans ORDER BY price_monthly")->fetchAll(); }catch(PDOException $e){ $plans=[]; }
try{ $subs=$pdo->query("SELECT ss.*, s.name as site_name, p.name as plan_name FROM site_subscriptions ss JOIN sites s ON ss.site_id=s.id JOIN subscription_plans p ON ss.plan_id=p.id ORDER BY ss.current_period_end DESC")->fetchAll(); }catch(PDOException $e){ $subs=[]; }
try{ $pendingPayments=$pdo->query("SELECT p.*, s.name as site_name, pl.name as plan_name, u.name as manager_name FROM payments p LEFT JOIN sites s ON p.site_id=s.id LEFT JOIN site_subscriptions ss ON p.subscription_id=ss.id LEFT JOIN subscription_plans pl ON ss.plan_id=pl.id LEFT JOIN users u ON p.user_id=u.id WHERE p.status='pending' AND p.subscription_id IS NOT NULL ORDER BY p.created_at DESC")->fetchAll(); }catch(PDOException $e){ $pendingPayments=[]; }
$demoLeads=[]; $leadLog=__DIR__.'/backups/demo_requests.log';
if(is_file($leadLog)){ $lines=array_filter(array_map('trim',file($leadLog))); foreach(array_reverse($lines) as $ln){ $p=array_map('trim',explode('|',$ln)); $demoLeads[]=['date'=>$p[0]??'','name'=>$p[1]??'','company'=>$p[2]??'','phone'=>$p[3]??'','email'=>$p[4]??'','msg'=>implode(' | ',array_slice($p,5))]; } }
$LS=landing_settings_all($pdo);
try{ $allLandingMenus=$pdo->query("SELECT * FROM landing_menu ORDER BY sort_order,id")->fetchAll(); }catch(PDOException $e){ $allLandingMenus=[]; }
try{ $allLandingFaqs=$pdo->query("SELECT * FROM landing_faq ORDER BY sort_order,id")->fetchAll(); }catch(PDOException $e){ $allLandingFaqs=[]; }

// İstatistikler
$totalSites    = count($sites);
$totalManagers = count($managers);
$totalResidents= $pdo->query("SELECT COUNT(*) FROM users WHERE role='resident'")->fetchColumn();
$totalPlans    = count($plans);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Paneli – RESİDA PRO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
/* --- Sidebar Toggle İçin Ek CSS --- */
.sidebar {
    transition: all 0.3s ease;
    overflow-x: hidden;
    white-space: nowrap;
}

/* Menü Kapalı Durumu */
body.sidebar-hidden .sidebar {
    width: 0 !important;
    min-width: 0 !important;
    padding-left: 0 !important;
    padding-right: 0 !important;
    opacity: 0;
    border: none !important;
}

/* Ana İçerik Geçişi */
.main-content {
    transition: all 0.3s ease;
    flex-grow: 1; /* Esnek yapıyı koru */
}

/* Menü kapandığında sola yasla ve tam alanı kapla */
body.sidebar-hidden .main-content {
    margin-left: 0 !important;
    width: 100% !important;
}
</style>
</head>
<body>
<div class="app-layout">

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="sidebar">
  <div class="sidebar-brand d-flex align-items-center gap-3">
     <div class="sidebar-logo">
    <a href="index.php">
        <img src="assets/img/resida-pro-logo2.png" alt="Resida Pro" style="max-width: 125%; height: auto; display: block;">
    </a>
</div>
    <div class="sidebar-brand-text">
      <h3>RESİDA PRO</h3>
      <small>Yönetim Sistemi</small>
    </div>
  </div>

  <div class="sidebar-user d-flex align-items-center gap-3">
    <div class="user-avatar"><?= avatarLetter($user['name']) ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($user['name']) ?></div>
      <div class="user-role"><span class="badge badge-admin">Sistem Admin</span></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section">Genel</div>
    <a href="dashboard.php" class="nav-link <?= $page==='dashboard'?'active':'' ?>">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <div class="sidebar-section">Yönetim</div>
    <a href="?page=sites" class="nav-link <?= $page==='sites'?'active':'' ?>">
      <i class="fa-solid fa-building"></i> Siteler
      <span class="ms-auto badge" style="background:rgba(99,102,241,.2);color:#a5b4fc;font-size:.68rem;"><?= $totalSites ?></span>
    </a>
    <a href="?page=managers" class="nav-link <?= $page==='managers'?'active':'' ?>">
      <i class="fa-solid fa-users-gear"></i> Yöneticiler
      <span class="ms-auto badge" style="background:rgba(16,185,129,.2);color:#34d399;font-size:.68rem;"><?= $totalManagers ?></span>
    </a>
    <a href="?page=plans" class="nav-link <?= $page==='plans'?'active':'' ?>">
      <i class="fa-solid fa-crown"></i> Paketler
      <span class="ms-auto badge" style="background:rgba(245,158,11,.2);color:#fcd34d;font-size:.68rem;"><?= $totalPlans ?></span>
    </a>
    <a href="?page=subscriptions" class="nav-link <?= $page==='subscriptions'?'active':'' ?>">
      <i class="fa-solid fa-credit-card"></i> Abonelikler
    </a>
    <a href="?page=payments" class="nav-link <?= $page==='payments'?'active':'' ?>">
      <i class="fa-solid fa-money-bill-transfer"></i> Ödemeler
    </a>
    <a href="?page=leads" class="nav-link <?= $page==='leads'?'active':'' ?>">
      <i class="fa-solid fa-envelope-open-text"></i> Demo Talepleri
      <?php if(!empty($demoLeads)): ?><span class="ms-auto badge" style="background:rgba(245,158,11,.2);color:#fcd34d;font-size:.68rem;"><?= count($demoLeads) ?></span><?php endif; ?>
    </a>
    <div class="sidebar-section">Site</div>
    <a href="?page=landing" class="nav-link <?= $page==='landing'?'active':'' ?>">
      <i class="fa-solid fa-globe"></i> Site İçeriği
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="logout.php" class="nav-link" style="color:#f87171 !important;">
      <i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap
    </a>
  </div>
</aside>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="main-content">
  
  <!-- DÜZENLENEN ÜST BAR: Hamburger butonu ve Başlık -->
  <header class="topbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
      <!-- Menü Aç/Kapa Butonu -->
      <button id="sidebarToggle" class="btn btn-light btn-sm border me-3 shadow-sm d-flex align-items-center gap-2" title="Menüyü Aç/Kapat">
        <i class="fa-solid fa-bars fs-6"></i> <span>Menü</span>
      </button>

      <span class="topbar-title">
        <?php
        $titles = ['dashboard'=>'Dashboard','sites'=>'Siteler','managers'=>'Yöneticiler','plans'=>'Paketler','subscriptions'=>'Abonelikler','payments'=>'Ödemeler','leads'=>'Demo Talepleri','landing'=>'Site İçeriği'];
        $iconMap = ['sites'=>'building','managers'=>'users-gear','plans'=>'crown','subscriptions'=>'credit-card','payments'=>'money-bill-transfer','leads'=>'envelope-open-text','landing'=>'globe'];
        echo '<i class="fa-solid fa-'. ($iconMap[$page] ?? 'gauge-high'). ' me-2 text-accent"></i>';
        echo $titles[$page] ?? 'Admin';
        ?>
      </span>
    </div>
    <div class="topbar-right">
      <span style="font-size:.8rem;color:#475569;"><?= date('d.m.Y') ?></span>
    </div>
  </header>

  <div class="content-body fade-in">

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
      <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($success) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
      <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- ─── DASHBOARD ─── -->
    <?php if ($page === 'dashboard'): ?>
    <div class="page-header">
      <h1>Hoş Geldiniz, <?= htmlspecialchars($user['name']) ?> 👋</h1>
      <p>Sistem genel durumuna göz atın.</p>
    </div>

    <div class="row g-4 mb-4">
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fa-solid fa-building"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalSites ?></div>
            <div class="stat-label">Kayıtlı Site</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fa-solid fa-users-gear"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalManagers ?></div>
            <div class="stat-label">Site Yöneticisi</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="stat-card">
          <div class="stat-icon cyan"><i class="fa-solid fa-people-roof"></i></div>
          <div class="stat-info">
            <div class="stat-value"><?= $totalResidents ?></div>
            <div class="stat-label">Toplam Sakin</div>
          </div>
        </div>
      </div>
    </div>
    <?php
      $subActive = count(array_filter($subs, fn($s)=>$s['status']==='active'));
      $subPending = count(array_filter($subs, fn($s)=>$s['status']==='pending'));
      $subPastDue = count(array_filter($subs, fn($s)=>in_array($s['status'],['past_due','expired','cancelled'])));
      $pendingPayCount = count($pendingPayments ?? []);
    ?>
    <div class="row g-4 mb-4">
      <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#10b981"><i class="fa-solid fa-circle-check"></i></div><div class="stat-info"><div class="stat-value"><?= $subActive ?></div><div class="stat-label">Aktif Abonelik</div></div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#f59e0b"><i class="fa-solid fa-clock"></i></div><div class="stat-info"><div class="stat-value"><?= $subPending ?></div><div class="stat-label">Pending (onay bekleyen)</div></div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#ef4444"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="stat-info"><div class="stat-value"><?= $subPastDue ?></div><div class="stat-label">Past Due / Expired</div></div></div></div>
      <div class="col-md-3"><div class="stat-card"><div class="stat-icon" style="background:#6366f1"><i class="fa-solid fa-money-bill-transfer"></i></div><div class="stat-info"><div class="stat-value"><?= $pendingPayCount ?></div><div class="stat-label">Bekleyen Ödeme</div><a href="?page=payments" class="small">Onayla →</a></div></div></div>
    </div>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-700"><i class="fa-solid fa-building me-2 text-accent"></i>Siteler</span>
            <a href="?page=sites" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
          </div>
          <div class="card-body p-0">
            <table class="table">
              <thead><tr><th>Site Adı</th><th class="text-end">Sakin</th></tr></thead>
              <tbody>
                <?php foreach(array_slice($sites,0,5) as $s): ?>
                <tr>
                  <td class="fw-700"><?= htmlspecialchars($s['name']) ?></td>
                  <td class="text-end"><span class="badge badge-resident"><?= $s['resident_count'] ?> kişi</span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(!$sites): ?>
                <tr><td colspan="2" class="text-center text-subtle py-4">Henüz site eklenmedi.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-700"><i class="fa-solid fa-users-gear me-2 text-success"></i>Yöneticiler</span>
            <a href="?page=managers" class="btn btn-sm btn-outline-primary">Tümünü Gör</a>
          </div>
          <div class="card-body p-0">
            <table class="table">
              <thead><tr><th>Ad Soyad</th><th>Site</th></tr></thead>
              <tbody>
                <?php foreach(array_slice($managers,0,5) as $m): ?>
                <tr>
                  <td class="fw-700"><?= htmlspecialchars($m['name']) ?></td>
                  <td><span class="badge badge-manager"><?= htmlspecialchars($m['site_name'] ?? '-') ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php if(!$managers): ?>
                <tr><td colspan="2" class="text-center text-subtle py-4">Henüz yönetici eklenmedi.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── SİTELER ─── -->
    <?php elseif ($page === 'sites'): ?>
    <div class="page-header d-flex justify-content-between align-items-start">
      <div>
        <h1><i class="fa-solid fa-building me-2 text-accent"></i>Siteler</h1>
        <p>Sistemde kayıtlı tüm apartman ve siteler</p>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSiteModal">
        <i class="fa-solid fa-plus me-2"></i>Yeni Site
      </button>
    </div>

    <div class="card">
      <div class="card-body p-0">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Site Adı</th>
              <th>Adres</th>
              <th>Sakin</th>
              <th>Kayıt Tarihi</th>
              <th class="text-end">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($sites): ?>
              <?php foreach ($sites as $i => $s): ?>
              <tr>
                <td class="text-subtle"><?= $i+1 ?></td>
                <td class="fw-700">
                  <?= htmlspecialchars($s['name']) ?> 
                  <small class="text-muted fw-normal">(Sınır: <?= (int)($s['max_residents'] ?? 0) > 0 ? htmlspecialchars($s['max_residents']) : 'Sınırsız' ?>)</small>
                </td>
                <td class="text-muted"><?= htmlspecialchars($s['address'] ?: '-') ?></td>
                <td><span class="badge badge-resident"><?= $s['resident_count'] ?> kişi</span></td>
                <td class="text-muted"><?= date_tr($s['created_at']) ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-secondary btn-icon me-1" data-bs-toggle="modal" data-bs-target="#editSiteModal<?= $s['id'] ?>" title="Düzenle">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Bu siteyi silmek istediğinize emin misiniz?')">
                    <input type="hidden" name="action" value="delete_site">
                    <input type="hidden" name="site_id" value="<?= $s['id'] ?>">
                    <button class="btn btn-sm btn-danger btn-icon" title="Sil"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              <!-- Edit Site Modal -->
              <div class="modal fade" id="editSiteModal<?= $s['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="post">
                      <input type="hidden" name="action" value="edit_site">
                      <input type="hidden" name="site_id" value="<?= $s['id'] ?>">
                      
                      <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-pen me-2 text-warning"></i>Siteyi Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      
<div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Site Adı <span class="text-danger">*</span></label>
            <input type="text" name="site_name" class="form-control" value="<?= htmlspecialchars($s['name'] ?? '') ?>" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Adres</label>
            <input type="text" name="site_address" class="form-control" value="<?= htmlspecialchars($s['address'] ?? '') ?>">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Maksimum Daire Sakini Sınırı</label>
            <input type="number" name="max_residents" class="form-control" min="0" value="<?= htmlspecialchars($s['max_residents'] ?? 0) ?>">
            <small class="text-muted">Sınır koymak istemiyorsanız 0 bırakın.</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Banka Adı</label>
            <input type="text" name="bank_name" class="form-control" value="<?= htmlspecialchars($s['bank_name'] ?? '') ?>" placeholder="Örn: Ziraat Bankası">
          </div>
          
          <div class="mb-3">
            <label class="form-label">IBAN</label>
            <input type="text" name="iban" class="form-control" value="<?= htmlspecialchars($s['iban'] ?? '') ?>" placeholder="TR00 0000 0000 0000 0000 0000 00">
            <small class="text-muted">Sakinlerin ödeme yapacağı site IBAN'ı</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Hesap Sahibi</label>
            <input type="text" name="iban_holder" class="form-control" value="<?= htmlspecialchars($s['iban_holder'] ?? '') ?>" placeholder="Site Yönetimi / Site Adı">
            <small class="text-muted">IBAN hesap sahibi adı</small>
          </div>
        </div>
                      
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-warning">Güncelle</button>
                      </div>
                      
                    </form>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">
                  <i class="fa-solid fa-building"></i>
                  <h4>Henüz site eklenmedi</h4>
                  <p>Sağ üstteki "Yeni Site" butonuyla ilk sitenizi ekleyin.</p>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ─── YÖNETİCİLER ─── -->
    <?php elseif ($page === 'managers'): ?>
    <div class="page-header d-flex justify-content-between align-items-start">
      <div>
        <h1><i class="fa-solid fa-users-gear me-2 text-success"></i>Yöneticiler</h1>
        <p>Site yöneticilerini buradan yönetin</p>
      </div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addManagerModal" <?= !$sites ? 'disabled title="Önce site ekleyin"' : '' ?>>
        <i class="fa-solid fa-user-plus me-2"></i>Yönetici Ekle
      </button>
    </div>

    <?php if (!$sites): ?>
    <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Yönetici eklemek için önce bir site oluşturun.</div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-0">
        <table class="table">
          <thead>
            <tr>
              <th>Ad Soyad</th>
              <th>Kullanıcı Adı</th>
              <th>Sorumlu Site</th>
              <th>Telefon</th>
              <th>E-posta</th>
              <th class="text-end">İşlemler</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($managers): ?>
              <?php foreach ($managers as $m): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:34px;height:34px;border-radius:10px;background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;color:white;">
                      <?= avatarLetter($m['name']) ?>
                    </div>
                    <span class="fw-700"><?= htmlspecialchars($m['name']) ?></span>
                  </div>
                </td>
                <td><span class="badge badge-manager"><?= htmlspecialchars($m['username']) ?></span></td>
                <td><?= htmlspecialchars($m['site_name'] ?? '—') ?></td>
                <td class="text-muted"><?= htmlspecialchars($m['phone'] ?: '—') ?></td>
                <td class="text-muted"><?= htmlspecialchars($m['email'] ?: '—') ?></td>
                <td class="text-end">
                  <button class="btn btn-sm btn-secondary btn-icon me-1" data-bs-toggle="modal" data-bs-target="#editMgrModal<?= $m['id'] ?>"><i class="fa-solid fa-pen"></i></button>
                  <form method="post" style="display:inline" onsubmit="return confirm('Bu yöneticiyi silmek istediğinize emin misiniz?')">
                    <input type="hidden" name="action" value="delete_manager">
                    <input type="hidden" name="manager_id" value="<?= $m['id'] ?>">
                    <button class="btn btn-sm btn-danger btn-icon"><i class="fa-solid fa-trash"></i></button>
                  </form>
                </td>
              </tr>
              <!-- Edit Manager Modal -->
              <div class="modal fade" id="editMgrModal<?= $m['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="post">
                      <input type="hidden" name="action" value="edit_manager">
                      <input type="hidden" name="manager_id" value="<?= $m['id'] ?>">
                      <div class="modal-header">
                        <h5 class="modal-title"><i class="fa-solid fa-user-pen me-2 text-warning"></i>Yöneticiyi Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($m['name']) ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($m['username']) ?>" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($m['phone'] ?? '') ?>">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($m['email'] ?? '') ?>">
                          </div>
                          <div class="col-12">
                            <label class="form-label">Sorumlu Site <span class="text-danger">*</span></label>
                            <select name="site_id" class="form-select" required>
                              <?php foreach ($sites as $s): ?>
                              <option value="<?= $s['id'] ?>" <?= $s['id'] == $m['site_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="col-12">
                            <label class="form-label">Yeni Şifre <small class="text-subtle">(değiştirmeyecekseniz boş bırakın)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-warning">Kaydet</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="6">
              <div class="empty-state">
                <i class="fa-solid fa-users-gear"></i>
                <h4>Yönetici bulunamadı</h4>
                <p>Önce site ekleyin, ardından yönetici atayın.</p>
              </div>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php elseif ($page === 'plans'): ?>
    <div class="page-header d-flex justify-content-between align-items-start">
      <div><h1><i class="fa-solid fa-crown me-2 text-warning"></i>Paketler</h1><p>Abonelik paketlerini yönetin</p></div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPlanModal"><i class="fa-solid fa-plus me-2"></i>Yeni Paket</button>
    </div>
    <div class="row g-4">
      <?php foreach($plans as $pl): ?>
      <div class="col-md-4">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="fw-700"><?= htmlspecialchars($pl['name']) ?></h5>
            <div class="mb-2"><span class="badge bg-primary"><?= $pl['max_residents']>0?$pl['max_residents'].' daire':'Sınırsız' ?></span></div>
            <div class="fs-4 fw-800 text-success"><?= money($pl['price_monthly']) ?> ₺<small class="fs-6 text-muted">/ay</small></div>
            <?php if($pl['price_yearly']): ?><div class="small text-muted">Yıllık: <?= money($pl['price_yearly']) ?> ₺</div><?php endif; ?>
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editPlan<?= $pl['id'] ?>"><i class="fa-solid fa-pen"></i></button>
              <form method="post" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="action" value="delete_plan"><input type="hidden" name="plan_id" value="<?= $pl['id'] ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>
            </div>
          </div>
        </div>
      </div>
      <div class="modal fade" id="editPlan<?= $pl['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="action" value="edit_plan"><input type="hidden" name="plan_id" value="<?= $pl['id'] ?>"><div class="modal-header"><h5 class="modal-title">Paketi Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Paket Adı</label><input type="text" name="plan_name" class="form-control" value="<?= htmlspecialchars($pl['name']) ?>" required></div><div class="mb-3"><label class="form-label">Max Daire (0=Sınırsız)</label><input type="number" name="max_residents" class="form-control" value="<?= $pl['max_residents'] ?>"></div><div class="mb-3"><label class="form-label">Aylık Fiyat</label><input type="number" step="0.01" name="price_monthly" class="form-control" value="<?= $pl['price_monthly'] ?>" required></div><div class="mb-3"><label class="form-label">Yıllık Fiyat</label><input type="number" step="0.01" name="price_yearly" class="form-control" value="<?= $pl['price_yearly'] ?>"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-warning">Güncelle</button></div></form></div></div></div>
      <?php endforeach; ?>
      <?php if(!$plans): ?><div class="col-12"><div class="empty-state"><i class="fa-solid fa-crown"></i><h4>Paket yok</h4></div></div><?php endif; ?>
    </div>

    <?php elseif ($page === 'subscriptions'): ?>
    <div class="page-header d-flex justify-content-between align-items-start">
      <div><h1><i class="fa-solid fa-credit-card me-2 text-success"></i>Abonelikler</h1><p>Site abonelikleri</p></div>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubModal"><i class="fa-solid fa-plus me-2"></i>Abonelik Aktifleştir</button>
    </div>
    <div class="card"><div class="card-body p-0"><table class="table"><thead><tr><th>Site</th><th>Paket</th><th>Durum</th><th>Dönem</th><th class="text-end">İşlem</th></tr></thead><tbody>
      <?php foreach($subs as $ss): ?>
      <tr>
        <td class="fw-700"><?= htmlspecialchars($ss['site_name']) ?></td>
        <td><?= htmlspecialchars($ss['plan_name']) ?></td>
        <td><span class="badge <?= $ss['status']==='active'?'bg-success':'bg-secondary' ?>"><?= htmlspecialchars($ss['status']) ?></span></td>
        <td class="small"><?= date_tr($ss['current_period_start']) ?> → <?= date_tr($ss['current_period_end']) ?></td>
        <td class="text-end" style="white-space:nowrap">
          <form method="post" style="display:inline" class="d-inline-flex align-items-center gap-1">
            <input type="hidden" name="action" value="extend_subscription">
            <input type="hidden" name="subscription_id" value="<?= $ss['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="number" name="extend_days" value="30" min="1" max="365" style="width:60px" class="form-control form-control-sm d-inline" title="Gün">
            <button class="btn btn-sm btn-warning" title="Süre Uzat"><i class="fa-solid fa-clock"></i></button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Abonelik silinsin mi? Site yönetimi kilitlenecek.')"><input type="hidden" name="action" value="delete_subscription"><input type="hidden" name="subscription_id" value="<?= $ss['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$subs): ?><tr><td colspan="5" class="text-center py-4 text-muted">Henüz abonelik yok</td></tr><?php endif; ?>
    </tbody></table></div></div>

    <?php elseif ($page === 'payments'): ?>
    <div class="page-header d-flex justify-content-between align-items-start">
      <div><h1><i class="fa-solid fa-money-bill-transfer me-2 text-warning"></i>Bekleyen Ödemeler</h1><p>Havale ve abonelik ödemeleri — onayla / reddet</p></div>
      <span class="badge bg-warning text-dark fs-6"><?= count($pendingPayments) ?> bekleyen</span>
    </div>
    <div class="card"><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Site</th><th>Yönetici</th><th>Paket</th><th>Tutar</th><th>Yöntem</th><th>Dekont</th><th>Tarih</th><th class="text-end">İşlem</th></tr></thead><tbody>
      <?php foreach($pendingPayments as $pp): ?>
      <tr>
        <td class="fw-700"><?= htmlspecialchars($pp['site_name'] ?? '-') ?></td>
        <td class="small"><?= htmlspecialchars($pp['manager_name'] ?? '-') ?></td>
        <td><?= htmlspecialchars($pp['plan_name'] ?? '-') ?></td>
        <td class="fw-700"><?= money($pp['amount']) ?> ₺</td>
        <td><span class="badge bg-secondary"><?= htmlspecialchars($pp['gateway']) ?></span></td>
        <td><?php if(!empty($pp['receipt_path'])): ?><a href="<?= htmlspecialchars($pp['receipt_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-file me-1"></i>Gör</a><?php else: ?><span class="text-muted small">—</span><?php endif; ?></td>
        <td class="small"><?= datetime_tr($pp['created_at']) ?></td>
        <td class="text-end" style="white-space:nowrap">
          <form method="post" style="display:inline"><input type="hidden" name="action" value="approve_payment"><input type="hidden" name="payment_id" value="<?= $pp['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><button class="btn btn-sm btn-success" onclick="return confirm('Onaylansın mı? Abonelik aktif olacak.')"><i class="fa-solid fa-check me-1"></i>Onayla</button></form>
          <form method="post" style="display:inline" onsubmit="return confirm('Reddedilsin mi?')"><input type="hidden" name="action" value="reject_payment"><input type="hidden" name="payment_id" value="<?= $pp['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><input type="hidden" name="reason" value="Admin reddi"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-xmark"></i></button></form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$pendingPayments): ?><tr><td colspan="8" class="text-center py-4 text-muted">Bekleyen ödeme yok</td></tr><?php endif; ?>
    </tbody></table></div></div>

    <?php elseif ($page === 'leads'): ?>
    <div class="page-header d-flex justify-content-between align-items-start">
      <div><h1><i class="fa-solid fa-envelope-open-text me-2 text-warning"></i>Demo Talepleri</h1><p>Landing sayfasındaki ücretsiz deneme formu — en yeni en üstte</p></div>
      <span class="badge bg-warning text-dark fs-6"><?= count($demoLeads) ?> talep</span>
    </div>
    <div class="card"><div class="card-body p-0"><table class="table mb-0"><thead><tr><th>Tarih</th><th>Ad Soyad</th><th>Firma / Site</th><th>Telefon</th><th>E-posta</th><th>Mesaj</th></tr></thead><tbody>
      <?php foreach($demoLeads as $ld): ?>
      <tr>
        <td class="small text-muted" style="white-space:nowrap"><?= htmlspecialchars($ld['date']) ?></td>
        <td class="fw-700"><?= htmlspecialchars($ld['name']) ?></td>
        <td><?= htmlspecialchars($ld['company'] ?: '-') ?></td>
        <td><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/','',$ld['phone'])) ?>"><?= htmlspecialchars($ld['phone']) ?></a></td>
        <td class="small"><a href="mailto:<?= htmlspecialchars($ld['email']) ?>"><?= htmlspecialchars($ld['email']) ?></a></td>
        <td class="small"><?= htmlspecialchars($ld['msg'] ?: '-') ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if(!$demoLeads): ?><tr><td colspan="6" class="text-center py-4 text-muted">Henüz talep yok</td></tr><?php endif; ?>
    </tbody></table></div></div>

    <?php elseif ($page === 'landing'): ?>
    <div class="page-header">
      <div><h1><i class="fa-solid fa-globe me-2 text-accent"></i>Site İçeriği</h1><p>Landing sayfası yazıları, üst menü, SSS ve görseller — <a href="landing.php" target="_blank">siteyi görüntüle</a></p></div>
    </div>
    <?php
    $landingFields=[
      'Üst Menü ve İletişim'=>['contact_email'=>'E-posta','contact_phone'=>'Telefon','footer_text'=>'Alt bilgi yazısı'],
      'Hero (ilk ekran)'=>['hero_badge'=>'Rozet yazısı','hero_title_a'=>'Başlık 1. satır','hero_title_b'=>'Başlık 2. satır (renkli)','hero_subtitle'=>'Alt açıklama|textarea','hero_primary_btn'=>'Birincil buton','hero_secondary_btn'=>'İkincil buton','hero_note'=>'Alt not'],
      'Bölüm Başlıkları'=>['problem_title'=>'Problem başlığı','problem_subtitle'=>'Problem alt yazı|textarea','solution_title'=>'Çözüm başlığı','solution_subtitle'=>'Çözüm alt yazı|textarea','payment_title'=>'Ödeme başlığı','payment_text'=>'Ödeme açıklaması|textarea','migration_title'=>'Geçiş başlığı','migration_subtitle'=>'Geçiş alt yazı|textarea'],
      'Ekranlar ve Fiyatlar'=>['screens_title'=>'Ekranlar başlığı','screens_subtitle'=>'Ekranlar alt yazı|textarea','screens_side_title'=>'Telefon kutusu başlığı','screens_side_text'=>'Telefon kutusu yazı|textarea','pricing_title'=>'Fiyat başlığı','pricing_subtitle'=>'Fiyat alt yazı|textarea','pricing_note'=>'Fiyat alt notu'],
      'SSS ve Çağrı'=>['faq_title'=>'SSS başlığı','faq_subtitle'=>'SSS alt yazı|textarea','cta_title'=>'Alt çağrı başlığı','cta_text'=>'Alt çağrı yazı|textarea','cta_primary_btn'=>'Alt çağrı butonu'],
    ];
    ?>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="landing_save_settings">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
      <?php foreach($landingFields as $group=>$fields): ?>
      <div class="card mb-3"><div class="card-header fw-700"><?= htmlspecialchars($group) ?></div><div class="card-body"><div class="row g-3">
        <?php foreach($fields as $k=>$lbl): $isTa=strpos($lbl,'|textarea')!==false; $lbl=str_replace('|textarea','',$lbl); ?>
        <div class="col-md-6"><label class="form-label"><?= htmlspecialchars($lbl) ?></label>
          <?php if($isTa): ?><textarea name="settings[<?= $k ?>]" class="form-control" rows="2"><?= htmlspecialchars($LS[$k]??'') ?></textarea>
          <?php else: ?><input type="text" name="settings[<?= $k ?>]" class="form-control" value="<?= htmlspecialchars($LS[$k]??'') ?>">
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div></div></div>
      <?php endforeach; ?>
      <div class="card mb-3"><div class="card-header fw-700">Görseller (JPG/PNG/WEBP, en fazla 2MB)</div><div class="card-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">Üst logo</label><input type="file" name="nav_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp"><div class="small text-muted mt-1">Mevcut: <?= htmlspecialchars($LS['nav_logo']??'') ?></div></div>
        <div class="col-md-6"><label class="form-label">Hero yan görseli (boşsa varsayılan panel görünür)</label><input type="file" name="hero_image" class="form-control" accept=".jpg,.jpeg,.png,.webp"><div class="small text-muted mt-1">Mevcut: <?= htmlspecialchars($LS['hero_image']??'') ?: '—' ?></div></div>
      </div></div></div>
      <button class="btn btn-primary mb-4"><i class="fa-solid fa-save me-1"></i>Tüm İçeriği Kaydet</button>
    </form>

    <div class="card mb-4"><div class="card-header fw-700"><i class="fa-solid fa-bars me-2"></i>Üst Menü</div><div class="card-body p-0">
      <table class="table mb-0"><thead><tr><th>#</th><th>Ad</th><th>Bağlantı</th><th>Sıra</th><th>Durum</th><th class="text-end">İşlem</th></tr></thead><tbody>
      <?php foreach($allLandingMenus as $m): ?>
      <tr>
        <td class="text-muted"><?= $m['id'] ?></td>
        <td class="fw-700"><?= htmlspecialchars($m['label']) ?></td>
        <td class="small"><?= htmlspecialchars($m['url']) ?></td>
        <td><?= (int)$m['sort_order'] ?></td>
        <td><span class="badge <?= $m['is_active']?'bg-success':'bg-secondary' ?>"><?= $m['is_active']?'Açık':'Kapalı' ?></span></td>
        <td class="text-end" style="white-space:nowrap">
          <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editMenu<?= $m['id'] ?>"><i class="fa-solid fa-pen"></i></button>
          <form method="post" style="display:inline"><input type="hidden" name="action" value="landing_menu_toggle"><input type="hidden" name="menu_id" value="<?= $m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><button class="btn btn-sm btn-warning"><i class="fa-solid fa-eye"></i></button></form>
          <form method="post" style="display:inline" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="action" value="landing_menu_delete"><input type="hidden" name="menu_id" value="<?= $m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>
        </td>
      </tr>
      <div class="modal fade" id="editMenu<?= $m['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="action" value="landing_menu_edit"><input type="hidden" name="menu_id" value="<?= $m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><div class="modal-header"><h5 class="modal-title">Menüyü Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Ad</label><input type="text" name="label" class="form-control" value="<?= htmlspecialchars($m['label']) ?>" required></div><div class="mb-3"><label class="form-label">Bağlantı (örn: #fiyatlar)</label><input type="text" name="url" class="form-control" value="<?= htmlspecialchars($m['url']) ?>" required></div><div class="mb-3"><label class="form-label">Sıra</label><input type="number" name="sort_order" class="form-control" value="<?= (int)$m['sort_order'] ?>"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-warning">Güncelle</button></div></form></div></div></div>
      <?php endforeach; ?>
      </tbody></table>
      <form method="post" class="p-3 border-top d-flex gap-2 flex-wrap align-items-end">
        <input type="hidden" name="action" value="landing_menu_add"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div><label class="form-label">Ad</label><input type="text" name="label" class="form-control" required></div>
        <div><label class="form-label">Bağlantı</label><input type="text" name="url" class="form-control" placeholder="#fiyatlar" required></div>
        <div><label class="form-label">Sıra</label><input type="number" name="sort_order" class="form-control" value="0" style="width:90px"></div>
        <div><button class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Ekle</button></div>
      </form>
    </div></div>

    <div class="card mb-4"><div class="card-header fw-700"><i class="fa-solid fa-circle-question me-2"></i>Sık Sorulan Sorular</div><div class="card-body p-0">
      <table class="table mb-0"><thead><tr><th>#</th><th>Soru</th><th>Sıra</th><th>Durum</th><th class="text-end">İşlem</th></tr></thead><tbody>
      <?php foreach($allLandingFaqs as $f): ?>
      <tr>
        <td class="text-muted"><?= $f['id'] ?></td>
        <td class="fw-700"><?= htmlspecialchars($f['question']) ?></td>
        <td><?= (int)$f['sort_order'] ?></td>
        <td><span class="badge <?= $f['is_active']?'bg-success':'bg-secondary' ?>"><?= $f['is_active']?'Açık':'Kapalı' ?></span></td>
        <td class="text-end" style="white-space:nowrap">
          <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editFaq<?= $f['id'] ?>"><i class="fa-solid fa-pen"></i></button>
          <form method="post" style="display:inline"><input type="hidden" name="action" value="landing_faq_toggle"><input type="hidden" name="faq_id" value="<?= $f['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><button class="btn btn-sm btn-warning"><i class="fa-solid fa-eye"></i></button></form>
          <form method="post" style="display:inline" onsubmit="return confirm('Silinsin mi?')"><input type="hidden" name="action" value="landing_faq_delete"><input type="hidden" name="faq_id" value="<?= $f['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>
        </td>
      </tr>
      <div class="modal fade" id="editFaq<?= $f['id'] ?>" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="action" value="landing_faq_edit"><input type="hidden" name="faq_id" value="<?= $f['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>"><div class="modal-header"><h5 class="modal-title">Soruyu Düzenle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Soru</label><input type="text" name="question" class="form-control" value="<?= htmlspecialchars($f['question']) ?>" required></div><div class="mb-3"><label class="form-label">Cevap</label><textarea name="answer" class="form-control" rows="3" required><?= htmlspecialchars($f['answer']) ?></textarea></div><div class="mb-3"><label class="form-label">Sıra</label><input type="number" name="sort_order" class="form-control" value="<?= (int)$f['sort_order'] ?>"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-warning">Güncelle</button></div></form></div></div></div>
      <?php endforeach; ?>
      </tbody></table>
      <form method="post" class="p-3 border-top">
        <input type="hidden" name="action" value="landing_faq_add"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="row g-2 align-items-end">
          <div class="col-md-4"><label class="form-label">Soru</label><input type="text" name="question" class="form-control" required></div>
          <div class="col-md-5"><label class="form-label">Cevap</label><input type="text" name="answer" class="form-control" required></div>
          <div class="col-md-1"><label class="form-label">Sıra</label><input type="number" name="sort_order" class="form-control" value="0"></div>
          <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fa-solid fa-plus me-1"></i>Ekle</button></div>
        </div>
      </form>
    </div></div>
    <?php endif; ?>

  </div><!-- /content-body -->
</div><!-- /main-content -->
</div><!-- /app-layout -->

<!-- ═══ Modal: Site Ekle ═══ -->
<div class="modal fade" id="addSiteModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_site">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-plus me-2 text-accent"></i>Yeni Site Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Site Adı <span class="text-danger">*</span></label>
            <input type="text" name="site_name" class="form-control" placeholder="Örn: Güneş Evleri Sitesi" required>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Adres</label>
            <input type="text" name="site_address" class="form-control" placeholder="Mahalle, ilçe, şehir...">
          </div>
          
          <div class="mb-3">
            <label class="form-label">Maksimum Daire Sakini Sınırı</label>
            <input type="number" name="max_residents" class="form-control" min="0" value="0">
            <small class="text-muted">Sınır koymak istemiyorsanız 0 bırakın.</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Banka Adı</label>
            <input type="text" name="bank_name" class="form-control" placeholder="Örn: Ziraat Bankası">
          </div>
          
          <div class="mb-3">
            <label class="form-label">IBAN</label>
            <input type="text" name="iban" class="form-control" placeholder="TR00 0000 0000 0000 0000 0000 00">
            <small class="text-muted">Sakinlerin ödeme yapacağı site IBAN'ı</small>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Hesap Sahibi</label>
            <input type="text" name="iban_holder" class="form-control" placeholder="Site Yönetimi / Site Adı">
            <small class="text-muted">IBAN hesap sahibi adı</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
      </form>
    </div>
  </div>
</div>
<div class="mb-3">
  <label class="form-label">Maksimum Daire Sakini Sınırı</label>
  <input type="number" name="max_residents" class="form-control" min="0" value="0">
  <small class="text-muted">Sınır koymak istemiyorsanız 0 bırakın.</small>
</div>
<!-- ═══ Modal: Yönetici Ekle ═══ -->
<div class="modal fade" id="addManagerModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="add_manager">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-solid fa-user-plus me-2 text-success"></i>Yönetici Ekle</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Sorumlu Site <span class="text-danger">*</span></label>
              <select name="site_id" class="form-select" required>
                <option value="" disabled selected>Site seçin...</option>
                <?php foreach ($sites as $s): ?>
                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="Ahmet Yılmaz" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
              <input type="text" name="username" class="form-control" placeholder="ayilmaz" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Şifre <span class="text-danger">*</span></label>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Telefon</label>
              <input type="text" name="phone" class="form-control" placeholder="0532 xxx xx xx">
            </div>
            <div class="col-md-6">
              <label class="form-label">E-posta</label>
              <input type="email" name="email" class="form-control" placeholder="ornek@mail.com">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
          <button type="submit" class="btn btn-primary">Yönetici Oluştur</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Paket Ekle Modal -->
<div class="modal fade" id="addPlanModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="action" value="add_plan"><div class="modal-header"><h5 class="modal-title">Yeni Paket</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Paket Adı *</label><input type="text" name="plan_name" class="form-control" required></div><div class="mb-3"><label class="form-label">Max Daire (0=Sınırsız)</label><input type="number" name="max_residents" class="form-control" value="20"></div><div class="mb-3"><label class="form-label">Aylık Fiyat *</label><input type="number" step="0.01" name="price_monthly" class="form-control" required></div><div class="mb-3"><label class="form-label">Yıllık Fiyat</label><input type="number" step="0.01" name="price_yearly" class="form-control"></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Kaydet</button></div></form></div></div></div>
<!-- Abonelik Aktifleştir Modal -->
<div class="modal fade" id="addSubModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="action" value="activate_subscription"><div class="modal-header"><h5 class="modal-title">Abonelik Aktifleştir</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label class="form-label">Site</label><select name="site_id" class="form-select" required><?php foreach($sites as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option><?php endforeach; ?></select></div><div class="mb-3"><label class="form-label">Paket</label><select name="plan_id" class="form-select" required><?php foreach($plans as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?> - <?= money($p['price_monthly']) ?> ₺/ay</option><?php endforeach; ?></select></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button><button type="submit" class="btn btn-primary">Aktifleştir</button></div></form></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
      sidebarToggle.addEventListener('click', function() {
        document.body.classList.toggle('sidebar-hidden');
      });
    }
  });
</script>
</body>
</html>