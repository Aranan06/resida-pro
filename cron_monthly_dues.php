<?php
// cron_monthly_dues.php – Otomatik Aidat Oluşturucu
// Kullanım: php cron_monthly_dues.php
// veya cPanel Cron: 0 9 1 * * /usr/bin/php /path/to/cron_monthly_dues.php
// veya InfinityFree Cron (ayda bir manuel tetikleme)

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// Sadece CLI veya cron token ile çalışsın (tarayıcıdan rastgele tetiklemeyi engelle)
$isCli = php_sapi_name() === 'cli';
$token = $_GET['token'] ?? '';
$expectedToken = $_ENV['CRON_TOKEN'] ?? 'resida-cron-2026';
if (!$isCli && $token !== $expectedToken) {
    http_response_code(403);
    die('Forbidden: Geçersiz cron token');
}

// Hangi dönem? Varsayılan: gelecek ay (cron 1. gün çalışıyorsa)
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$dueDate = sprintf('%04d-%02d-05', $year, $month); // ayın 5'i vade
$desc    = sprintf('%04d/%02d Dönemi Aidatı', $year, $month);

$dryRun = isset($_GET['dry']) && $_GET['dry'] == '1';

echo "=== RESIDA Otomatik Aidat - $desc (Vade: $dueDate) ===\n";
if ($dryRun) echo "[DRY RUN] Sadece simülasyon, DB yazılmayacak\n\n";

$sites = $pdo->query("SELECT s.id, s.name, ds.monthly_amount FROM sites s LEFT JOIN due_settings ds ON ds.site_id=s.id AND ds.year=$year")->fetchAll();

$totalCreated = 0;
$totalSkipped = 0;

foreach ($sites as $site) {
    $siteId = $site['id'];
    $amount = (float)($site['monthly_amount'] ?? 0);
    if ($amount <= 0) {
        echo "[ATLA] {$site['name']} - {$year} için aylık ücret tanımlı değil (due_settings)\n";
        continue;
    }

    $residents = getResidentsBySite($pdo, $siteId);
    if (!$residents) {
        echo "[ATLA] {$site['name']} - sakin yok\n";
        continue;
    }

    foreach ($residents as $r) {
        // Mükerrer kontrol: aynı ay + açıklama zaten var mı?
        $chk = $pdo->prepare("SELECT COUNT(*) FROM dues WHERE resident_id=? AND YEAR(due_date)=? AND MONTH(due_date)=? AND description=?");
        $chk->execute([$r['id'], $year, $month, $desc]);
        if ($chk->fetchColumn() > 0) {
            $totalSkipped++;
            continue;
        }
        if (!$dryRun) {
            $pdo->prepare("INSERT INTO dues (site_id, resident_id, amount, due_date, description, paid) VALUES (?,?,?,?,?,0)")
                ->execute([$siteId, $r['id'], $amount, $dueDate, $desc]);
        }
        $totalCreated++;
        echo "[OK] {$site['name']} - {$r['name']} (Daire {$r['apartment_no']}) => {$amount} TL\n";
    }
}

echo "\n=== Özet: {$totalCreated} yeni aidat oluşturuldu, {$totalSkipped} mükerrer atlandı ===\n";

// Log dosyası
$log = sprintf("[%s] cron: %s -> created:%d skipped:%d\n", date('Y-m-d H:i:s'), $desc, $totalCreated, $totalSkipped);
@file_put_contents(__DIR__ . '/cron.log', $log, FILE_APPEND);

if (!$isCli) {
    echo "<pre>$log</pre>";
}
