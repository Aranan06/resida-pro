<?php
// resident_panel.php – Sakin Paneli (Karanlık Modda Duyuru/Etkinlik Yazıları Beyaz)
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/PaymentGateway.php';
if (!isResident()) { header('Location: dashboard.php'); exit; }

$page = $_GET['page'] ?? 'dashboard';
$mySiteId = $user['site_id'];

$siteStmt = $pdo->prepare("SELECT name, iban, bank_name, iban_holder, penalty_enabled, penalty_rate, penalty_grace_days FROM sites WHERE id = ?");
$siteStmt->execute([$mySiteId]);
$siteRow = $siteStmt->fetch(PDO::FETCH_ASSOC);
$siteName = $siteRow['name'] ?? 'Bilinmeyen Site';
$siteIban = $siteRow['iban'] ?? '';
$siteBank = $siteRow['bank_name'] ?? '';
$siteHolder = $siteRow['iban_holder'] ?? '';
$penaltySettings = ['enabled'=>(int)($siteRow['penalty_enabled']??0), 'rate'=>(float)($siteRow['penalty_rate']??0), 'grace'=>(int)($siteRow['penalty_grace_days']??5)];

// ─── Ödeme Bildirimi (Manuel Havale) ───
$paySuccess = $payError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay_due_manual') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $dueId = (int)($_POST['due_id'] ?? 0);
    // Aidat bu sakine ait mi ve ödenmemiş mi?
    $chk = $pdo->prepare("SELECT * FROM dues WHERE id=? AND resident_id=? AND site_id=? AND paid=0");
    $chk->execute([$dueId, $user['id'], $mySiteId]);
    $due = $chk->fetch();
    if (!$due) {
        $payError = 'Aidat bulunamadı veya zaten ödenmiş.';
    } else {
        // Dosya yükleme (opsiyonel dekont)
        $receiptPath = null;
        if (!empty($_FILES['receipt']['name']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','pdf'];
            $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $payError = 'Sadece JPG, PNG veya PDF yükleyebilirsiniz.';
            } elseif ($_FILES['receipt']['size'] > 5*1024*1024) {
                $payError = 'Dosya 5MB sınırını aşıyor.';
            } else {
                $uploadDir = __DIR__ . '/uploads/receipts';
                if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
                $fname = 'receipt_' . $dueId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . '/' . $fname;
                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $dest)) {
                    $receiptPath = 'uploads/receipts/' . $fname;
                } else {
                    $payError = 'Dosya yüklenemedi.';
                }
            }
        }
        if (!$payError) {
            // Aynı aidat için bekleyen ödeme var mı?
            $dup = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE due_id=? AND status='pending'");
            $dup->execute([$dueId]);
            if ($dup->fetchColumn() > 0) {
                $payError = 'Bu aidat için zaten bekleyen bir ödeme bildiriminiz var. Yönetici onayı bekleniyor.';
            } else {
                $pen = calculatePenalty($due, $penaltySettings);
                $totalAmount = $due['amount'] + $pen;
                $gw = getPaymentGateway($pdo, 'manual');
                $res = $gw->createPayment([
                    'site_id' => $mySiteId,
                    'user_id' => $user['id'],
                    'due_id'  => $dueId,
                    'amount'  => $totalAmount,
                    'note'    => trim($_POST['note'] ?? '') . ($pen>0 ? " (Faiz: ".money($pen)." TL dahil)" : ''),
                    'receipt_path' => $receiptPath
                ]);
                if ($res['success']) $paySuccess = $res['message'] . ($pen>0 ? " Toplam ".money($totalAmount)." TL (".money($pen)." TL faiz dahil) bildirildi." : "");
                else $payError = $res['message'];
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pay_due_iyzico') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    $dueId = (int)($_POST['due_id'] ?? 0);
    $chk = $pdo->prepare("SELECT * FROM dues WHERE id=? AND resident_id=? AND site_id=? AND paid=0");
    $chk->execute([$dueId, $user['id'], $mySiteId]);
    $due = $chk->fetch();
    if (!$due) { $payError = 'Aidat bulunamadı.'; }
    else {
        $pen = calculatePenalty($due, $penaltySettings);
        $total = (float)$due['amount'] + $pen;
        $gw = getPaymentGateway($pdo, 'iyzico');
        $res = $gw->createPayment([
            'site_id' => $mySiteId,
            'user_id' => $user['id'],
            'due_id'  => $dueId,
            'amount'  => $total,
            'note'    => 'iyzico ödeme' . ($pen>0 ? " (faiz ".money($pen)." TL dahil)" : ""),
            'description' => $due['description']
        ]);
        if (!empty($res['redirect_url'])) { header('Location: '.$res['redirect_url']); exit; }
        if ($res['success']) $paySuccess = $res['message']; else $payError = $res['message'];
    }
}

$myDues = $pdo->prepare("SELECT * FROM dues WHERE resident_id = ? ORDER BY due_date DESC");
$myDues->execute([$user['id']]);
$myDues = $myDues->fetchAll();

$unpaidTotal = array_sum(array_map(fn($d) => !$d['paid'] ? $d['amount'] + calculatePenalty($d, $penaltySettings) : 0, $myDues));
$paidTotal   = array_sum(array_map(fn($d) => $d['paid'] ? $d['amount'] : 0, $myDues));
$unpaidCount = count(array_filter($myDues, fn($d) => !$d['paid']));
$penaltyTotal = $unpaidTotal - array_sum(array_map(fn($d) => !$d['paid'] ? $d['amount'] : 0, $myDues));

$announcements = getAnnouncementsBySite($pdo, $mySiteId);
$events = getEventsBySite($pdo, $mySiteId);

$darkModeCookie = $_COOKIE['darkMode'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="tr" data-theme="<?= $darkModeCookie ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="<?= $darkModeCookie === 'dark' ? '#0f172a' : '#1e3a8a' ?>">
    <title>Sakin Paneli - <?= htmlspecialchars($siteName) ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/img/apple-touch-icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ========== TEMA DEĞİŞKENLERİ ========== */
        :root {
            --bg-primary: #f1f5f9;
            --bg-card: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --sidebar-bg: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            --sidebar-text: #e2e8f0;
            --topbar-bg: rgba(255,255,255,0.95);
            --table-header: #f8fafc;
            --stat-bg: #ffffff;
            --shadow: 0 2px 8px rgba(0,0,0,0.05);
            --announcement-text: #0f172a;
            --event-text: #0f172a;
            --event-title: #0f172a;
        }
        
        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-card: #1e293b;
            --text-primary: #f1f5f9;
            --text-secondary: #94a3b8;
            --border-color: #334155;
            --sidebar-bg: linear-gradient(180deg, #020617 0%, #0f172a 100%);
            --sidebar-text: #cbd5e1;
            --topbar-bg: rgba(15,23,42,0.95);
            --table-header: #334155;
            --stat-bg: #1e293b;
            --shadow: 0 2px 8px rgba(0,0,0,0.2);
            --announcement-text: #ffffff;
            --event-text: #e2e8f0;
            --event-title: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.2s ease, border-color 0.2s ease;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            overflow-x: hidden;
        }
        
        /* ========== MASAÜSTÜ: SIDEBAR SABİT, İÇERİK GENİŞ ========== */
        .app-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar - masaüstünde her zaman görünür */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        /* Masaüstünde sidebar her zaman açık */
        @media (min-width: 992px) {
            .sidebar {
                transform: translateX(0) !important;
                position: fixed;
            }
            
            .main-content {
                margin-left: 260px;
                width: calc(100% - 260px);
                min-height: 100vh;
            }
            
            /* Sidebar toggle butonu masaüstünde gizli */
            .topbar-menu-btn,
            .mobile-menu-toggle {
                display: none !important;
            }
            
            /* Sidebar daraltma seçeneği */
            body.sidebar-collapsed .sidebar {
                width: 70px;
            }
            
            body.sidebar-collapsed .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            body.sidebar-collapsed .sidebar-brand-text h3,
            body.sidebar-collapsed .sidebar-brand-text small,
            body.sidebar-collapsed .user-info,
            body.sidebar-collapsed .nav-link span {
                display: none;
            }
            
            body.sidebar-collapsed .nav-link {
                justify-content: center;
                padding: 0.75rem;
            }
            
            body.sidebar-collapsed .nav-link i {
                margin: 0;
                font-size: 1.3rem;
            }
            
            body.sidebar-collapsed .sidebar-user {
                justify-content: center;
            }
            
            body.sidebar-collapsed .sidebar-brand {
                justify-content: center;
            }
        }
        
        /* Mobil Sidebar (kayan menü) */
        @media (max-width: 991px) {
            .sidebar {
                position: fixed;
                transform: translateX(-100%);
                transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                z-index: 1050;
                width: 280px;
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            /* Mobil menü butonları */
            .mobile-menu-toggle {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: #3b82f6;
                color: white;
                border: none;
                box-shadow: 0 4px 12px rgba(59,130,246,0.4);
                z-index: 1060;
                display: flex !important;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 1.5rem;
            }
            
            .topbar-menu-btn {
                background: transparent;
                border: none;
                font-size: 1.5rem;
                color: var(--text-primary);
                padding: 8px;
                display: flex !important;
                align-items: center;
                justify-content: center;
                cursor: pointer;
            }
        }
        
        /* Topbar */
        .topbar {
            background: var(--topbar-bg);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        /* Sidebar içeriği */
        .sidebar-brand h3 {
            font-size: 1.1rem;
            margin: 0;
            color: white;
        }
        .sidebar-brand small {
            font-size: 0.65rem;
            opacity: 0.8;
            color: #94a3b8;
        }
        
        .user-avatar {
            width: 44px;
            height: 44px;
            background: #3b82f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
            color: white;
            flex-shrink: 0;
        }
        
        .nav-link {
            color: #cbd5e1;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            margin: 4px 10px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem;
        }
        .nav-link i {
            width: 22px;
            font-size: 1rem;
        }
        .nav-link.active {
            background: #3b82f6;
            color: white;
        }
        .nav-link:hover:not(.active) {
            background: #334155;
            color: white;
        }
        
        /* Stat kartları */
        .stat-card {
            background: var(--stat-bg);
            border-radius: 16px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-icon.red { background: #fee2e2; color: #dc2626; }
        .stat-icon.green { background: #dcfce7; color: #16a34a; }
        .stat-icon.amber { background: #fed7aa; color: #ea580c; }
        
        [data-theme="dark"] .stat-icon.red { background: #7f1a1a; color: #fca5a5; }
        [data-theme="dark"] .stat-icon.green { background: #14532d; color: #86efac; }
        [data-theme="dark"] .stat-icon.amber { background: #7c2d12; color: #fdba74; }
        
        .stat-value {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--text-primary);
        }
        .stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }
        
        /* Tablo kaydırma */
        .table-swipe-container {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }
        
        /* Masaüstünde tablo tam genişlik */
        @media (min-width: 992px) {
            .table {
                min-width: auto;
                width: 100%;
            }
            .table-swipe-container {
                overflow-x: visible;
            }
        }
        
        /* Mobilde yatay kaydırma */
        @media (max-width: 991px) {
            .table {
                min-width: 550px;
            }
            .table-swipe-container {
                overflow-x: auto;
            }
            
            .swipe-hint {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-size: 0.7rem;
                color: var(--text-secondary);
                background: var(--bg-card);
                padding: 6px 12px;
                border-radius: 20px;
            }
        }
        
        .table {
            margin-bottom: 0;
            color: var(--text-primary);
        }
        
        .table thead th {
            background: var(--table-header);
            color: var(--text-primary);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        /* ========== DUYURULAR VE ETKİNLİKLER KARANLIK MOD AYARLARI ========== */
        /* Duyuru kartları */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }
        
        /* Duyuru başlıkları */
        .card .card-body h5,
        .card .card-body h5.fw-bold,
        .card .card-body h5.fw-bold.fs-6 {
            color: var(--announcement-text) !important;
        }
        
        /* Duyuru içerik metni */
        .card .card-body p,
        .card .card-body p.text-muted {
            color: var(--announcement-text) !important;
            opacity: 0.9;
        }
        
        /* Duyuru tarih metni */
        .card .card-body small.text-muted,
        .card .card-body small {
            color: var(--text-secondary) !important;
        }
        
        /* Etkinlik kartları */
        .card.h-100 .card-body h5,
        .card.h-100 .card-body h5.fw-bold {
            color: var(--event-title) !important;
        }
        
        /* Etkinlik tarih metinleri */
        .card.h-100 .card-body p.text-primary,
        .card.h-100 .card-body p.text-secondary {
            color: var(--event-text) !important;
        }
        
        .card.h-100 .card-body p.text-primary i,
        .card.h-100 .card-body p.text-secondary i {
            color: inherit;
        }
        
        /* Etkinlik açıklama metni */
        .card.h-100 .card-body p.text-muted {
            color: var(--event-text) !important;
            opacity: 0.85;
        }
        
        /* Modal içeriği (duyuru detay) */
        .modal-content .modal-body {
            color: var(--announcement-text) !important;
        }
        
        .card-header {
            background: var(--bg-card);
            border-bottom-color: var(--border-color);
        }
        
        /* Tema butonu */
        .theme-toggle-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-primary);
            transition: all 0.2s;
        }
        
        .theme-toggle-btn:hover {
            background: var(--border-color);
        }
        
        /* Sidebar daraltma butonu (masaüstü) */
        .sidebar-collapse-btn {
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .sidebar-collapse-btn:hover {
            background: #334155;
            color: white;
        }
        
        @media (max-width: 991px) {
            .sidebar-collapse-btn {
                display: none;
            }
        }
        
        .modal-content {
            background: var(--bg-card);
            color: var(--announcement-text);
        }
        
        .modal-header {
            border-bottom-color: var(--border-color);
        }
        
        .modal-header .modal-title {
            color: var(--announcement-text);
        }
        
        .btn-close {
            filter: var(--text-primary) === '#f1f5f9' ? invert(1) : invert(0);
        }
        
        .content-body {
            padding: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .content-body {
                padding: 1rem;
            }
            .stat-card {
                padding: 0.75rem;
            }
            .stat-icon {
                width: 42px;
                height: 42px;
                font-size: 1.2rem;
            }
            .stat-value {
                font-size: 1.1rem;
            }
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1040;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        @media (min-width: 992px) {
            .sidebar-overlay.active {
                display: none;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Alert mesajları karanlık mod */
        .alert-info {
            background: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
    </style>
</head>
<body>

<div class="app-layout">
    <div id="sidebarOverlay" class="sidebar-overlay"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand d-flex align-items-center justify-content-between p-3">
            <div class="d-flex align-items-center gap-2">
                <div class="sidebar-logo">
                    <a href="resident_panel.php"><img src="assets/img/resida-pro-logo2.png" alt="Logo" style="max-width: 35px; height: auto;"></a>
                </div>
                <div class="sidebar-brand-text">
                    <h3>RESİDA PRO</h3>
                    <small><?= htmlspecialchars($siteName) ?></small>
                </div>
            </div>
            <button id="sidebarCollapseBtn" class="sidebar-collapse-btn">
                <i class="fa-solid fa-chevron-left"></i>
            </button>
        </div>
        <div class="sidebar-user d-flex align-items-center gap-3 p-3">
            <div class="user-avatar"><?= avatarLetter($user['name']) ?></div>
            <div class="user-info">
                <div class="user-name fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                <div class="user-role"><span class="badge bg-primary bg-opacity-50">Sakin</span></div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="sidebar-section px-3 py-2 text-muted small fw-bold">GENEL</div>
            <a href="?page=dashboard" class="nav-link <?= $page==='dashboard'?'active':'' ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Ana Sayfa</span>
            </a>
            <div class="sidebar-section px-3 py-2 text-muted small fw-bold">İLETİŞİM</div>
            <a href="?page=duyurular" class="nav-link <?= $page==='duyurular'?'active':'' ?>">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Duyurular</span>
            </a>
            <a href="?page=etkinlikler" class="nav-link <?= $page==='etkinlikler'?'active':'' ?>">
                <i class="fa-solid fa-calendar-days"></i>
                <span>Etkinlikler</span>
            </a>
        </nav>
        <div class="sidebar-footer mt-auto p-3">
            <a href="logout.php" class="nav-link text-danger">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Çıkış</span>
            </a>
        </div>
    </aside>
    
    <main class="main-content">
        <header class="topbar p-3">
            <div class="d-flex align-items-center gap-2">
                <button id="topbarMenuBtn" class="topbar-menu-btn">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <span class="topbar-title fw-bold">Sakin Paneli</span>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-primary d-none d-sm-inline"><?= htmlspecialchars($siteName) ?></span>
                <div class="dropdown">
                  <button id="notifBell" class="btn btn-light position-relative" style="border-radius:50%;width:38px;height:38px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border-color)" title="Bildirimler">
                    <i class="fa-solid fa-bell"></i>
                    <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                  </button>
                  <div id="notifDropdown" class="dropdown-menu dropdown-menu-end p-0" style="width:320px;max-height:400px;overflow:auto;display:none;position:absolute;right:0;z-index:1050;background:var(--bg-card);border:1px solid var(--border-color);border-radius:12px;box-shadow:0 8px 20px rgba(0,0,0,.15);">
                    <div class="p-3 border-bottom d-flex justify-content-between align-items-center"><strong>Bildirimler</strong><button id="enablePushBtn" class="btn btn-sm btn-outline-primary" style="font-size:.70rem">Push Aç</button></div>
                    <div id="notifList" class="p-2 small text-muted text-center">Yükleniyor...</div>
                  </div>
                </div>
                <button id="themeToggle" class="theme-toggle-btn">
                    <i id="themeIcon" class="fa-solid <?= $darkModeCookie === 'dark' ? 'fa-sun' : 'fa-moon' ?>"></i>
                </button>
            </div>
        </header>
        
        <div class="content-body fade-in">
            <?php if ($page === 'dashboard'): ?>
                <!-- Hoşgeldin Kartı -->
                <div class="card p-3 p-md-4 border-0 shadow-sm text-white mb-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <h2 class="h5 mb-2">Merhaba, <?= htmlspecialchars($user['name']) ?>! 👋</h2>
                    <p class="mb-0 opacity-75 small">Resida Pro ile aidatlarınızı takip edin.</p>
                </div>
                
                <!-- İstatistik Kartları -->
                <div class="row g-3 g-md-4 mb-4">
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-icon red"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?= money($unpaidTotal) ?> ₺<?php if($penaltyTotal>0): ?><small class="text-danger" style="font-size:.65em"> (faiz: <?= money($penaltyTotal) ?> ₺)</small><?php endif; ?></div>
                                <div class="stat-label">Toplam Borcum<?php if($penaltySettings['enabled']): ?> <span class="badge bg-danger" style="font-size:.55em">Faiz %<?= $penaltySettings['rate'] ?></span><?php endif; ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-icon green"><i class="fa-solid fa-check-double"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?= money($paidTotal) ?> ₺</div>
                                <div class="stat-label">Ödenen</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-4">
                        <div class="stat-card">
                            <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
                            <div class="stat-info">
                                <div class="stat-value"><?= $unpaidCount ?></div>
                                <div class="stat-label">Bekleyen İşlem</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if($paySuccess): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($paySuccess) ?></div><?php endif; ?>
                <?php if($payError): ?><div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($payError) ?></div><?php endif; ?>

                <?php
                // Aidat IBAN'ı SİTE'nin kendi IBAN'ı (yönetim hesabı) — sana değil
                $hasSiteIban = !empty($siteIban);
                $iyzicoEnabled = !empty($_ENV['IYZICO_API_KEY']) && $_ENV['IYZICO_API_KEY'] !== 'sandbox-api-key';
                ?>
                <?php if($hasSiteIban): ?>
                <!-- IBAN Bilgi Kartı - Siteye özel -->
                <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg,#0f172a,#1e3a8a); color:white;">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h6 class="mb-1"><i class="fa-solid fa-building-columns me-2"></i>Havale / EFT Bilgileri - <?= htmlspecialchars($siteName) ?> Yönetimi</h6>
                            <div class="small opacity-75"><?= htmlspecialchars($siteBank ?: 'Banka') ?> - <?= htmlspecialchars($siteHolder ?: $siteName . ' Yönetimi') ?></div>
                            <div class="fw-bold" style="letter-spacing:1px;"><?= htmlspecialchars($siteIban) ?></div>
                            <small class="opacity-75">Açıklamaya: Daire <?= htmlspecialchars($user['apartment_no']) ?> - <?= htmlspecialchars($user['name']) ?> yazın ve dekontu yükleyin</small>
                        </div>
                        <span class="badge bg-success"><i class="fa-solid fa-check me-1"></i>Site Hesabı</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-warning"><i class="fa-solid fa-triangle-exclamation me-2"></i>Site yönetimi henüz tahsilat hesabını tanımlamadı. Ödeme yapmadan önce yöneticinizle görüşün.</div>
                <?php endif; ?>

                <!-- Aidat Tablosu -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="mb-0 fw-bold fs-6">📋 Aidat Özetim</h5>
                        <small class="text-muted swipe-hint" id="swipeHint">
                            <i class="fa-solid fa-arrow-left"></i> Kaydırmak için parmağınızla sola/sağa kaydırın <i class="fa-solid fa-arrow-right"></i>
                        </small>
                    </div>
                    <div class="table-swipe-container" id="tableSwipeContainer">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Dönem</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th>İşlem</th>
                                    <th>Makbuz</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($myDues as $d): 
                                    $pendCheck = $pdo->prepare("SELECT id FROM payments WHERE due_id=? AND status='pending' LIMIT 1");
                                    $pendCheck->execute([$d['id']]);
                                    $hasPending = $pendCheck->fetchColumn();
                                    $pen = calculatePenalty($d, $penaltySettings);
                                    $hasPenalty = $pen > 0;
                                ?>
                                <tr class="<?= $hasPenalty ? 'table-warning' : '' ?>">
                                    <td class="fw-medium"><?= htmlspecialchars($d['description']) ?><br><small class="text-muted"><?= date_tr($d['due_date']) ?><?php if($hasPenalty): ?> <span class="text-danger">· <?= getDaysOverdue($d,$penaltySettings) ?> gün gecikme</span><?php endif; ?></small></td>
                                    <td class="fw-bold"><?= money($d['amount']) ?> ₺<?php if($hasPenalty): ?><br><small class="text-danger">+<?= money($pen) ?> faiz</small><br><small class="text-danger">= <?= money($d['amount']+$pen) ?> ₺</small><?php endif; ?></td>
                                    <td>
                                        <?php if($d['paid']): ?><span class="badge bg-success px-3 py-2">✓ Ödendi</span>
                                        <?php elseif($hasPending): ?><span class="badge bg-warning text-dark px-3 py-2">⏳ Onay Bekliyor</span>
                                        <?php else: ?><span class="badge bg-danger px-3 py-2">⏳ Ödenmedi</span><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!$d['paid'] && !$hasPending): ?>
                                            <?php if($hasSiteIban): ?>
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#payModal<?= $d['id'] ?>"><i class="fa-solid fa-credit-card me-1"></i> Öde</button>
                                            <?php else: ?>
                                            <span class="badge bg-warning text-dark">IBAN tanımsız</span>
                                            <?php endif; ?>
                                        <?php elseif($hasPending): ?>
                                            <small class="text-muted">Yönetici onayı bekleniyor</small>
                                        <?php else: ?>
                                            <small class="text-success">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php if($d['paid']): ?><a href="receipt.php?id=<?=$d['id']?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download"></i> İndir</a><?php else: ?><span class="text-muted">-</span><?php endif; ?></td>
                                </tr>

                                <?php if(!$d['paid'] && !$hasPending): ?>
                                <!-- Ödeme Modal -->
                                <div class="modal fade" id="payModal<?= $d['id'] ?>" tabindex="-1">
                                  <div class="modal-dialog">
                                    <div class="modal-content">
                                      <form method="post" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="pay_due_manual">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                                        <input type="hidden" name="due_id" value="<?= $d['id'] ?>">
                                        <div class="modal-header">
                                          <h5 class="modal-title"><i class="fa-solid fa-building-columns me-2"></i>Ödeme Bildir - <?= htmlspecialchars($d['description']) ?></h5>
                                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                          <?php $modalPen = calculatePenalty($d, $penaltySettings); ?>
                                          <div class="alert alert-info small">
                                            <strong><?= htmlspecialchars($siteBank ?: 'Banka') ?></strong><br>
                                            IBAN: <code><?= htmlspecialchars($siteIban ?: 'Tanımsız') ?></code><br>
                                            Alıcı: <?= htmlspecialchars($siteHolder ?: $siteName . ' Yönetimi') ?><br>
                                            Tutar: <strong><?= money($d['amount']) ?> ₺</strong><?php if($modalPen>0): ?><br><span class="text-danger">Gecikme faizi: +<?= money($modalPen) ?> ₺ (<?= getDaysOverdue($d,$penaltySettings) ?> gün, %<?= $penaltySettings['rate'] ?>/ay)</span><br><strong class="text-danger">Toplam ödenecek: <?= money($d['amount']+$modalPen) ?> ₺</strong><?php endif; ?><br>
                                            Açıklama: <code><?= htmlspecialchars($user['name']) ?> - Daire <?= htmlspecialchars($user['apartment_no']) ?> - <?= htmlspecialchars($d['description']) ?></code>
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label">Dekont Yükle (JPG/PNG/PDF, max 5MB) <span class="text-muted">(opsiyonel ama önerilir)</span></label>
                                            <input type="file" name="receipt" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label">Not (opsiyonel)</label>
                                            <input type="text" name="note" class="form-control" placeholder="Örn: 12.05.2026 saat 14:30'da havale yapıldı">
                                          </div>
                                        </div>
                                        <div class="modal-footer">
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                          <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane me-1"></i> Havale Bildir</button>
                                        </div>
                                      </form>
                                      <?php if($iyzicoEnabled): ?>
                                      <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                                        <input type="hidden" name="action" value="pay_due_iyzico">
                                        <input type="hidden" name="due_id" value="<?= $d['id'] ?>">
                                        <div class="p-3 bg-light border-top text-center">
                                          <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-credit-card me-1"></i> Kartla Öde — <?= money($d['amount']+$modalPen) ?> ₺</button>
                                          <small class="text-muted d-block mt-1">iyzico güvencesiyle • 256-bit SSL • Taksit imkanı</small>
                                        </div>
                                      </form>
                                      <?php else: ?>
                                      <div class="p-3 bg-light border-top small text-muted text-center">Kartla ödeme için yönetici iyzico anahtarlarını tanımlamalı. Şimdilik havale yapın.</div>
                                      <?php endif; ?>
                                    </div>
                                  </div>
                                </div>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if(count($myDues) == 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                                        Henüz aidat kaydı bulunmuyor.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($page === 'duyurular'): ?>
                <h1 class="h3 mb-4 fw-bold" style="color: var(--text-primary);">📢 Duyurular</h1>
                <div class="row">
                    <?php if(count($announcements) > 0): ?>
                        <?php foreach($announcements as $ann): ?>
                            <div class="col-12 mb-3">
                                <div class="card border-0 shadow-sm" style="cursor:pointer" data-bs-toggle="modal" data-bs-target="#annModal<?=$ann['id']?>">
                                    <div class="card-body">
                                        <h5 class="fw-bold fs-6"><?= htmlspecialchars($ann['title']) ?></h5>
                                        <small class="text-muted"><i class="fa-solid fa-clock me-1"></i><?= datetime_tr($ann['created_at']) ?></small>
                                        <p class="mt-2 mb-0"><?= htmlspecialchars(mb_substr($ann['content'], 0, 100)) ?>...</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="annModal<?=$ann['id']?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?= htmlspecialchars($ann['title']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body" style="white-space:pre-wrap;"><?= nl2br(htmlspecialchars($ann['content'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">📭 Henüz duyuru bulunmuyor.</div>
                        </div>
                    <?php endif; ?>
                </div>
                
            <?php elseif ($page === 'etkinlikler'): ?>
                <h1 class="h3 mb-4 fw-bold" style="color: var(--text-primary);">🎉 Etkinlikler</h1>
                <div class="row g-3">
                    <?php if($events): foreach($events as $ev): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="fw-bold fs-6"><?= htmlspecialchars($ev['title']) ?></h5>
                                    <p class="fw-bold mb-1 small">
                                        <i class="fa-regular fa-calendar me-1"></i> <?= date('d.m.Y H:i', strtotime($ev['event_date'])) ?>
                                    </p>
                                    <?php if (!empty($ev['end_date'])): ?>
                                        <p class="fw-bold mb-1 small">
                                            <i class="fa-regular fa-calendar-check me-1"></i> <?= date('d.m.Y H:i', strtotime($ev['end_date'])) ?>
                                        </p>
                                    <?php endif; ?>
                                    <p class="small mt-2 mb-0"><?= nl2br(htmlspecialchars($ev['description'] ?? 'Detay yok.')) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">📅 Yaklaşan etkinlik bulunmuyor.</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Mobil Menü Butonu -->
    <button id="fabMenuBtn" class="mobile-menu-toggle">
        <i class="fa-solid fa-bars"></i>
    </button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // ========== KARANLIK MOD ==========
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const htmlElement = document.documentElement;
    
    function setTheme(theme) {
        htmlElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        document.cookie = `darkMode=${theme}; path=/; max-age=31536000`;
        
        if (theme === 'dark') {
            if (themeIcon) themeIcon.className = 'fa-solid fa-sun';
            document.querySelector('meta[name="theme-color"]').setAttribute('content', '#0f172a');
        } else {
            if (themeIcon) themeIcon.className = 'fa-solid fa-moon';
            document.querySelector('meta[name="theme-color"]').setAttribute('content', '#1e3a8a');
        }
    }
    
    const savedTheme = localStorage.getItem('theme') || 
                       (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    setTheme(savedTheme);
    
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    }
    
    // ========== SIDEBAR KONTROLÜ (MOBİL + MASAÜSTÜ) ==========
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const topbarBtn = document.getElementById('topbarMenuBtn');
    const fabBtn = document.getElementById('fabMenuBtn');
    const collapseBtn = document.getElementById('sidebarCollapseBtn');
    
    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function toggleSidebar() {
        if (!sidebar) return;
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }
    
    const isMobile = window.innerWidth < 992;
    
    if (isMobile) {
        if (topbarBtn) topbarBtn.addEventListener('click', toggleSidebar);
        if (fabBtn) fabBtn.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', closeSidebar);
        
        if (sidebar) {
            const sidebarLinks = sidebar.querySelectorAll('.nav-link');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', () => setTimeout(closeSidebar, 150));
            });
        }
        
        closeSidebar();
    } else {
        if (collapseBtn) {
            const collapseIcon = collapseBtn.querySelector('i');
            
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                document.body.classList.add('sidebar-collapsed');
                if (collapseIcon) collapseIcon.className = 'fa-solid fa-chevron-right';
            }
            
            collapseBtn.addEventListener('click', () => {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', isCollapsed);
                
                if (collapseIcon) {
                    collapseIcon.className = isCollapsed ? 'fa-solid fa-chevron-right' : 'fa-solid fa-chevron-left';
                }
            });
        }
        
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
    }
    
    // ========== TABLO KAYDIRMA (SADECE MOBİL) ==========
    const tableContainer = document.getElementById('tableSwipeContainer');
    const swipeHint = document.getElementById('swipeHint');
    
    if (tableContainer && window.innerWidth < 992) {
        let startX, scrollLeft;
        let isDragging = false;
        
        tableContainer.addEventListener('touchstart', (e) => {
            isDragging = true;
            startX = e.touches[0].pageX - tableContainer.offsetLeft;
            scrollLeft = tableContainer.scrollLeft;
        });
        
        tableContainer.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.touches[0].pageX - tableContainer.offsetLeft;
            const walk = (x - startX) * 1.5;
            tableContainer.scrollLeft = scrollLeft - walk;
        });
        
        tableContainer.addEventListener('touchend', () => {
            isDragging = false;
        });
        
        if (swipeHint) {
            setTimeout(() => {
                swipeHint.style.opacity = '0.5';
            }, 3000);
            setTimeout(() => {
                if (swipeHint) swipeHint.style.display = 'none';
            }, 6000);
        }
    }
    if('serviceWorker' in navigator){navigator.serviceWorker.register('service-worker.js').catch(()=>{});}
    // ===== Bildirimler =====
    const vapidKey = "<?= $_ENV['VAPID_PUBLIC_KEY'] ?? '' ?>";
    const notifBell = document.getElementById('notifBell');
    const notifBadge = document.getElementById('notifBadge');
    const notifDropdown = document.getElementById('notifDropdown');
    const notifList = document.getElementById('notifList');
    const enablePushBtn = document.getElementById('enablePushBtn');
    function urlBase64ToUint8Array(b){const p='='.repeat((4-b.length%4)%4);const s=(b+p).replace(/-/g,'+').replace(/_/g,'/');const r=window.atob(s);const a=new Uint8Array(r.length);for(let i=0;i<r.length;i++)a[i]=r.charCodeAt(i);return a;}
    async function fetchNotifs(){
        try{
            const r=await fetch('api/notifications.php',{credentials:'same-origin'});
            const j=await r.json();
            if(j.success){
                const unread=j.unread||0;
                if(unread>0){notifBadge.textContent=unread;notifBadge.classList.remove('d-none');}else{notifBadge.classList.add('d-none');}
                if(j.notifications.length===0) notifList.innerHTML='<div class="p-3 text-muted">Henüz bildirim yok</div>';
                else notifList.innerHTML=j.notifications.map(n=>`<div class="p-2 border-bottom" style="text-align:left"><div class="fw-bold" style="color:var(--text-primary)">${n.title}</div><div style="color:var(--text-secondary)">${n.body}</div><small class="text-muted">${n.created_at}</small></div>`).join('');
            }
        }catch(e){}
    }
    if(notifBell){
        notifBell.addEventListener('click',()=>{ notifDropdown.style.display = notifDropdown.style.display==='block'?'none':'block'; fetchNotifs(); });
        document.addEventListener('click',e=>{ if(!e.target.closest('#notifBell') && !e.target.closest('#notifDropdown')) notifDropdown.style.display='none'; });
    }
    async function enablePush(){
        if(!('Notification' in window) || !('PushManager' in window)){ alert('Tarayıcınız push desteklemiyor'); return; }
        const perm=await Notification.requestPermission();
        if(perm!=='granted'){ alert('Bildirim izni verilmedi'); return; }
        const reg=await navigator.serviceWorker.ready;
        const sub=await reg.pushManager.subscribe({userVisibleOnly:true, applicationServerKey:urlBase64ToUint8Array(vapidKey)});
        await fetch('api/push/subscribe.php',{method:'POST',headers:{'Content-Type':'application/json'},credentials:'same-origin',body:JSON.stringify({endpoint:sub.endpoint, keys:{p256dh:btoa(String.fromCharCode(...new Uint8Array(sub.getKey('p256dh')))), auth:btoa(String.fromCharCode(...new Uint8Array(sub.getKey('auth'))))}})});
        alert('Push bildirimleri aktif!');
        enablePushBtn.textContent='Aktif ✓'; enablePushBtn.disabled=true;
    }
    if(enablePushBtn) enablePushBtn.addEventListener('click', enablePush);
    fetchNotifs(); setInterval(fetchNotifs, 30000);
</script>
</body>
</html>