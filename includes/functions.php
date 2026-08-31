<?php
// includes/functions.php
require_once __DIR__ . '/config.php';

// ─── Yönetici listesi ───────────────────────────────────────────────────────
function getSiteManagers($pdo) {
    $stmt = $pdo->query("SELECT u.id, u.username, u.name, u.phone, u.email,
                         s.name AS site_name, s.id AS site_id
                         FROM users u LEFT JOIN sites s ON u.site_id = s.id
                         WHERE u.role = 'manager' ORDER BY u.name");
    return $stmt->fetchAll();
}

// ─── Bloklar ────────────────────────────────────────────────────────────────
function getBlocksBySite($pdo, $site_id) {
    try {
        $stmt = $pdo->prepare("SELECT b.*, (SELECT COUNT(*) FROM users WHERE block_id=b.id AND role='resident') as resident_count FROM blocks b WHERE b.site_id=? ORDER BY b.name");
        $stmt->execute([$site_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}
function getBlockName($pdo, $block_id) {
    if (!$block_id) return null;
    try { $s=$pdo->prepare("SELECT name FROM blocks WHERE id=?"); $s->execute([$block_id]); return $s->fetchColumn(); } catch(PDOException $e){ return null; }
}

// ─── Sakinler ───────────────────────────────────────────────────────────────
function getResidentsBySite($pdo, $site_id) {
    $stmt = $pdo->prepare("SELECT u.*, b.name as block_name FROM users u LEFT JOIN blocks b ON u.block_id=b.id WHERE u.role = 'resident' AND u.site_id = ? ORDER BY b.name, u.floor, u.apartment_no");
    $stmt->execute([$site_id]);
    return $stmt->fetchAll();
}

// ─── Aidatlar ───────────────────────────────────────────────────────────────
function getDuesBySite($pdo, $site_id, $filter = 'all', $year = null, $month = null) {
    $sql = "SELECT d.*, u.name AS resident_name, u.apartment_no, u.floor, b.name as block_name
            FROM dues d JOIN users u ON d.resident_id = u.id LEFT JOIN blocks b ON u.block_id=b.id
            WHERE d.site_id = ?";
    $params = [$site_id];

    if ($filter === 'paid')    { $sql .= " AND d.paid = 1"; }
    if ($filter === 'unpaid')  { $sql .= " AND d.paid = 0"; }
    if ($year)                 { $sql .= " AND YEAR(d.due_date) = ?";  $params[] = $year; }
    if ($month)                { $sql .= " AND MONTH(d.due_date) = ?"; $params[] = $month; }

    $sql .= " ORDER BY d.due_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getDueSummary($pdo, $site_id) {
    $stmt = $pdo->prepare("SELECT
        COUNT(*) AS total,
        SUM(paid = 1) AS paid_count,
        SUM(paid = 0) AS unpaid_count,
        COALESCE(SUM(CASE WHEN paid = 1 THEN amount END), 0) AS paid_amount,
        COALESCE(SUM(CASE WHEN paid = 0 THEN amount END), 0) AS unpaid_amount
        FROM dues WHERE site_id = ?");
    $stmt->execute([$site_id]);
    return $stmt->fetch();
}

// ─── Aidat Ayarları ─────────────────────────────────────────────────────────
function getDueSetting($pdo, $site_id, $year) {
    $stmt = $pdo->prepare("SELECT * FROM due_settings WHERE site_id = ? AND year = ?");
    $stmt->execute([$site_id, $year]);
    return $stmt->fetch();
}

function saveDueSetting($pdo, $site_id, $year, $amount) {
    $stmt = $pdo->prepare("INSERT INTO due_settings (site_id, year, monthly_amount)
                           VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE monthly_amount = VALUES(monthly_amount), updated_at = NOW()");
    $stmt->execute([$site_id, $year, $amount]);
}

// ─── Giderler ───────────────────────────────────────────────────────────────
function getExpensesBySite($pdo, $site_id, $year = null) {
    $sql = "SELECT * FROM expenses WHERE site_id = ?";
    $params = [$site_id];
    if ($year) { $sql .= " AND YEAR(expense_date) = ?"; $params[] = $year; }
    $sql .= " ORDER BY expense_date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getExpenseSummary($pdo, $site_id, $year = null) {
    $sql = "SELECT category, SUM(amount) AS total FROM expenses WHERE site_id = ?";
    $params = [$site_id];
    if ($year) { $sql .= " AND YEAR(expense_date) = ?"; $params[] = $year; }
    $sql .= " GROUP BY category";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ─── Duyurular & Etkinlikler ────────────────────────────────────────────────
function getAnnouncementsBySite($pdo, $site_id) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE site_id = ? ORDER BY created_at DESC");
    $stmt->execute([$site_id]);
    return $stmt->fetchAll();
}

function getEventsBySite($pdo, $site_id) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE site_id = ? ORDER BY event_date DESC");
    $stmt->execute([$site_id]);
    return $stmt->fetchAll();
}

// ─── Hava Durumu ─────────────────────────────────────────────────────────────
function getWeather() {
    $ctx = stream_context_create(['http' => ['timeout' => 3]]);
    $w = @file_get_contents('https://api.open-meteo.com/v1/forecast?latitude=41.0082&longitude=28.9784&current_weather=true', false, $ctx);
    return $w ? (json_decode($w, true)['current_weather'] ?? null) : null;
}

function weatherIcon($code) {
    if ($code === 0) return '☀️';
    if ($code <= 3)  return '⛅';
    if ($code <= 48) return '☁️';
    if ($code <= 57) return '🌧️';
    if ($code <= 67) return '🌨️';
    if ($code <= 77) return '❄️';
    return '🌩️';
}

// ─── Gecikme Faizi ────────────────────────────────────────────────────────────
function getPenaltySettings($pdo, $siteId) {
    try {
        $s=$pdo->prepare("SELECT penalty_enabled, penalty_rate, penalty_grace_days FROM sites WHERE id=?");
        $s->execute([$siteId]);
        $row=$s->fetch();
        if(!$row) return ['enabled'=>0,'rate'=>0,'grace'=>5];
        return ['enabled'=>(int)$row['penalty_enabled'], 'rate'=>(float)$row['penalty_rate'], 'grace'=>(int)$row['penalty_grace_days']];
    } catch(PDOException $e){ return ['enabled'=>0,'rate'=>0,'grace'=>5]; }
}
function calculatePenalty($due, $penalty) {
    if(!$penalty['enabled'] || $due['paid']) return 0;
    $dueDate = strtotime($due['due_date']);
    $graceEnd = $dueDate + ($penalty['grace'] * 86400);
    if(time() <= $graceEnd) return 0;
    // Geciken ay sayısı (30 günde bir)
    $daysOverdue = (int)floor((time() - $graceEnd) / 86400) + 1;
    $monthsOverdue = (int)ceil($daysOverdue / 30);
    if($monthsOverdue < 1) $monthsOverdue = 1;
    // Basit faiz: ana para * oran * ay
    return round($due['amount'] * ($penalty['rate']/100) * $monthsOverdue, 2);
}
function getDaysOverdue($due, $penalty) {
    if($due['paid']) return 0;
    $dueDate = strtotime($due['due_date']);
    $graceEnd = $dueDate + ($penalty['grace'] * 86400);
    if(time() <= $graceEnd) return 0;
    return (int)floor((time() - $graceEnd) / 86400) + 1;
}

// ─── Yardımcılar ─────────────────────────────────────────────────────────────
function money($v) { return number_format($v, 2, ',', '.'); }
function date_tr($d) { return $d ? date('d.m.Y', strtotime($d)) : '-'; }
function datetime_tr($d) { return $d ? date('d.m.Y H:i', strtotime($d)) : '-'; }
function avatarLetter($name) { return mb_strtoupper(mb_substr(trim($name), 0, 1, 'UTF-8'), 'UTF-8'); }
// --- GÜVENLİK FONKSİYONLARI ---

// 1. CSRF Token Üret ve Doğrula
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('<div style="color:red; padding:20px; font-family:sans-serif;"><b>Güvenlik İhlali (CSRF):</b> Lütfen sayfayı yenileyip tekrar deneyin.</div>');
    }
    // Token tek kullanımlık rotasyon (opsiyonel, yorumda bırakıldı)
    // $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 2. OTOMATİK ENJEKTÖR - sadece verifyCsrfToken kullanılan formlar için yedek
// Not: Login dahil tüm POST formlarına otomatik ekler, ama explicit token tercih edilir
ob_start(function($html) {
    // Eğer html içinde zaten csrf_token varsa tekrar ekleme
    if (strpos($html, 'name="csrf_token"') !== false) return $html;
    $csrf = "\n" . '    <input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">' . "\n";
    return preg_replace('/(<form[^>]*method=["\']post["\'][^>]*>)/i', '$1' . $csrf, $html);
});

// 3. XSS Koruması
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// 4. BRUTE-FORCE / RATE LIMITING
function ensureLoginAttemptsTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(45) NOT NULL,
            username VARCHAR(100) NULL,
            attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            success TINYINT(1) DEFAULT 0,
            INDEX idx_ip_time (ip, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (PDOException $e) { /* sessiz geç */ }
}

function isRateLimited($pdo, $ip, $maxAttempts = 5, $lockoutMinutes = 15) {
    ensureLoginAttemptsTable($pdo);
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND attempted_at > (NOW() - INTERVAL ? MINUTE)");
        $stmt->execute([$ip, $lockoutMinutes]);
        return (int)$stmt->fetchColumn() >= $maxAttempts;
    } catch (PDOException $e) { return false; }
}

function getRemainingLockoutMinutes($pdo, $ip, $lockoutMinutes = 15) {
    try {
        $stmt = $pdo->prepare("SELECT attempted_at FROM login_attempts WHERE ip = ? AND success = 0 ORDER BY attempted_at DESC LIMIT 1");
        $stmt->execute([$ip]);
        $last = $stmt->fetchColumn();
        if (!$last) return 0;
        $elapsed = time() - strtotime($last);
        $remaining = ($lockoutMinutes * 60) - $elapsed;
        return $remaining > 0 ? (int)ceil($remaining / 60) : 0;
    } catch (PDOException $e) { return 0; }
}

function recordLoginAttempt($pdo, $ip, $username, $success) {
    ensureLoginAttemptsTable($pdo);
    try {
        $pdo->prepare("INSERT INTO login_attempts (ip, username, success) VALUES (?,?,?)")->execute([$ip, $username, $success ? 1 : 0]);
        // Başarılı girişte eski başarısız kayıtları temizle
        if ($success) {
            $pdo->prepare("DELETE FROM login_attempts WHERE ip = ? AND success = 0")->execute([$ip]);
        }
    } catch (PDOException $e) { /* sessiz geç */ }
}

