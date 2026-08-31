<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$dueId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$dueId) { http_response_code(400); die("Geçersiz makbuz numarası."); }

// Yetki kontrollü çekim
$stmt = $pdo->prepare("
    SELECT d.*, u.name as resident_name, u.apartment_no, u.floor, u.block_id, b.name as block_name,
           s.name as site_name, s.address as site_address, s.iban, s.bank_name, s.iban_holder,
           s.penalty_enabled, s.penalty_rate, s.penalty_grace_days
    FROM dues d 
    JOIN users u ON d.resident_id = u.id 
    JOIN sites s ON d.site_id = s.id 
    LEFT JOIN blocks b ON u.block_id = b.id
    WHERE d.id = ?
");
$stmt->execute([$dueId]);
$receipt = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$receipt) { http_response_code(404); die("Makbuz bulunamadı."); }

// Yetki: admin her şeyi görür, manager sadece kendi sitesi, resident sadece kendi aidatı
if ($user['role'] === 'manager' && (int)$receipt['site_id'] !== (int)$user['site_id']) { http_response_code(403); die("Bu makbuzu görmeye yetkiniz yok."); }
if ($user['role'] === 'resident' && (int)$receipt['resident_id'] !== (int)$user['id']) { http_response_code(403); die("Bu makbuz size ait değil."); }
if (!$receipt['paid']) { die("<div style='font-family:sans-serif;padding:40px;text-align:center'>Bu aidat henüz <b>ödenmemiş</b>, makbuz oluşturulamaz. <a href='javascript:history.back()'>Geri dön</a></div>"); }

// Ödeme yöntemi ve dekont (varsa)
$payStmt = $pdo->prepare("SELECT gateway, receipt_path, created_at FROM payments WHERE due_id=? AND status='approved' ORDER BY approved_at DESC LIMIT 1");
$payStmt->execute([$dueId]);
$payment = $payStmt->fetch();

// Faiz hesaplama (ödenenlerde faiz ödenen tarihe göre değil, vade üzerinden - bilgi amaçlı)
$penaltySettings = ['enabled'=>(int)($receipt['penalty_enabled']??0), 'rate'=>(float)($receipt['penalty_rate']??0), 'grace'=>(int)($receipt['penalty_grace_days']??5)];
$penaltyAmount = 0;
if($penaltySettings['enabled'] && !$receipt['paid']) { $penaltyAmount = calculatePenalty($receipt, $penaltySettings); }
elseif($penaltySettings['enabled'] && $receipt['paid']) {
    // Ödenmişte tarihsel faiz: due_date + grace sonrası paid_date'e kadar
    $dueTs = strtotime($receipt['due_date']); $graceEnd = $dueTs + $penaltySettings['grace']*86400;
    $paidTs = strtotime($receipt['paid_date'] ?? $receipt['due_date']);
    if($paidTs > $graceEnd){
        $days = (int)floor(($paidTs - $graceEnd)/86400)+1; $months = (int)ceil($days/30); if($months<1)$months=1;
        $penaltyAmount = round($receipt['amount'] * ($penaltySettings['rate']/100) * $months, 2);
    }
}



$siteName = htmlspecialchars($receipt['site_name']);
$siteAddress = htmlspecialchars($receipt['site_address'] ?? '');
$siteIban = $receipt['iban'] ?? '';
$siteBank = $receipt['bank_name'] ?? '';
$residentName = htmlspecialchars($receipt['resident_name']);
$blockInfo = $receipt['block_name'] ? htmlspecialchars($receipt['block_name']) . ' · ' : '';
$aptInfo = $blockInfo . $receipt['floor'] . ". Kat / Daire " . $receipt['apartment_no'];
$baseAmount = (float)$receipt['amount'];
$totalAmount = $baseAmount + $penaltyAmount;
$amount = money($totalAmount) . " ₺";
$baseStr = money($baseAmount) . " ₺";
$penaltyStr = $penaltyAmount > 0 ? money($penaltyAmount) . " ₺" : "";
$date = date_tr($receipt['paid_date'] ?? $receipt['due_date']);
$period = htmlspecialchars($receipt['description'] ?: 'Aidat Tahsilatı');
$receiptNo = "#MAK-" . date('Y', strtotime($receipt['paid_date'] ?? $receipt['due_date'])) . "-" . str_pad($receipt['id'], 4, '0', STR_PAD_LEFT);
$payMethod = $payment ? ($payment['gateway']==='iyzico' ? 'Kredi Kartı (iyzico)' : 'Havale / EFT') : 'Havale / EFT';
$dueDateStr = date_tr($receipt['due_date']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tahsilat Makbuzu - <?= $residentName ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; font-family: 'Helvetica Neue', Arial, sans-serif; }
        body { color: #333; margin: 0; padding: 20px; background-color: #f1f5f9; display: flex; justify-content: center; }
        
        .receipt-container { 
            width: 100%; max-width: 800px; background: white; border: 1px solid #cbd5e1; 
            padding: 40px; position: relative; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .watermark { 
            position: absolute; right: 40px; bottom: 120px; border: 4px double #10b981; 
            color: #10b981; font-size: 24pt; font-weight: bold; padding: 8px 16px; 
            transform: rotate(-15deg); border-radius: 8px; letter-spacing: 2px; opacity: 0.2; 
        }
        .header { width: 100%; margin-bottom: 30px; display: table; }
        .header td { vertical-align: middle; }
        .title-left h2 { margin: 0; color: #0f172a; font-size: 22pt; font-weight: bold; }
        .title-left p { margin: 5px 0 0 0; color: #64748b; font-size: 10pt; text-transform: uppercase; letter-spacing: 1px; }
        .title-right { text-align: right; }
        .title-right h1 { margin: 0; color: #1e3a8a; font-size: 20pt; font-weight: 800; letter-spacing: 1px; }
        
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #f8fafc; border: 1px solid #e2e8f0; }
        .info-table td { padding: 12px 15px; font-size: 10pt; color: #475569; width: 25%; border-right: 1px solid #e2e8f0; }
        .info-table strong { color: #0f172a; display: block; margin-top: 4px; font-size: 11pt; }
        
        .details-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .details-table th { background-color: #1e3a8a; color: #ffffff; text-align: left; padding: 12px 15px; font-size: 11pt; border: 1px solid #1e3a8a; }
        .details-table td { padding: 20px 15px; border-bottom: 1px solid #cbd5e1; border-left: 1px solid #cbd5e1; border-right: 1px solid #cbd5e1; font-size: 11pt; }
        
        .amount-box { background-color: #f0fdf4; border: 1px dashed #10b981; padding: 20px; text-align: center; margin-bottom: 40px; border-radius: 6px; }
        .amount-box .label { font-size: 11pt; color: #166534; font-weight: bold; letter-spacing: 1px; }
        .amount-box .val { font-size: 28pt; font-weight: 800; color: #15803d; margin-top: 5px; }
        
        .signatures { width: 100%; margin-top: 20px; display: table; text-align: center; }
        .signatures td { width: 50%; padding-top: 40px; color: #1e293b; }
        .line { border-top: 1px solid #94a3b8; width: 55%; margin: 0 auto 10px auto; }
        
        .action-buttons { text-align: center; margin-bottom: 20px; }
        .btn-print { background: #1e3a8a; color: white; border: none; padding: 12px 24px; font-size: 11pt; border-radius: 6px; cursor: pointer; font-weight: bold; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-print:hover { background: #1e40af; }
        
        /* YAZDIRMA AYARLARI */
        @media print {
            body { background-color: white; padding: 0; }
            .receipt-container { border: none; box-shadow: none; max-width: 100%; padding: 0; }
            .action-buttons { display: none !important; }
            .watermark { opacity: 0.85; } /* Çıktıda filigran net çıksın */
        }
    </style>
</head>
<body>

<div>
    <div class="action-buttons">
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-file-pdf"></i> PDF Olarak İndir / Yazdır
        </button>
    </div>

    <div class="receipt-container">
        <div class="watermark">ÖDENDİ</div>
        <table class="header">
            <tr>
                <td class="title-left">
                    <h2><?= $siteName ?></h2>
                    <p>RESİDA PRO Site Yönetimi</p>
                    <?php if($siteAddress): ?><small style="color:#64748b"><?= $siteAddress ?></small><?php endif; ?>
                    <?php if($siteIban): ?><small style="display:block;color:#64748b;margin-top:4px;">IBAN: <?= htmlspecialchars($siteIban) ?> (<?= htmlspecialchars($siteBank ?: '') ?>)</small><?php endif; ?>
                </td>
                <td class="title-right">
                    <h1>TAHSİLAT MAKBUZU</h1>
                    <small style="color:#64748b">KVKK uyumlu</small>
                </td>
            </tr>
        </table>
        
        <table class="info-table">
            <tr>
                <td>Makbuz No:<br><strong><?= $receiptNo ?></strong></td>
                <td>Tarih:<br><strong><?= $date ?></strong></td>
                <td>Vade:<br><strong><?= $dueDateStr ?></strong></td>
                <td>Ödeme Türü:<br><strong><?= htmlspecialchars($payMethod) ?></strong></td>
            </tr>
        </table>
        
        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Açıklama Detayı</th>
                    <th style="width: 55%;">Ödeme Yapan Bilgileri</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="font-weight: bold; color: #1e3a8a; line-height: 1.6; vertical-align: top;">
                        <?= $period ?>
                        <?php if($penaltyAmount>0): ?><div style="margin-top:8px;font-size:9pt;color:#dc2626;background:#fef2f2;padding:6px 8px;border-radius:4px;">Gecikme faizi: <?= $penaltyStr ?> (<?php echo $penaltySettings['rate'] ?>%/ay, <?php echo $penaltySettings['grace'] ?> gün hoşgörü)</div><?php endif; ?>
                    </td>
                    <td style="vertical-align: top;">
                        <div style="margin-bottom: 6px;"><span style="color:#64748b">Adı Soyadı:</span> <strong><?= $residentName ?></strong></div>
                        <div><span style="color:#64748b">Daire Bilgisi:</span> <strong><?= $aptInfo ?></strong></div>
                        <div style="margin-top:6px;font-size:9pt;color:#64748b">Durum: <strong style="color:#10b981">Tahsil Edildi</strong></div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="amount-box">
            <div class="label">Tahsil Edilen Toplam Tutar</div>
            <?php if($penaltyAmount>0): ?>
            <div style="font-size:11pt;color:#475569;margin-bottom:6px;">Ana para: <?= $baseStr ?> + Faiz: <span style="color:#dc2626"><?= $penaltyStr ?></span></div>
            <?php endif; ?>
            <div class="val"><?= $amount ?></div>
        </div>
        
        <table class="signatures">
            <tr>
                <td><div class="line"></div><strong>Teslim Eden (Ödeyen)</strong><br><span style="font-size: 9pt; color: #64748b;">İmza</span></td>
                <td><div class="line"></div><strong>Tahsil Eden (Yönetim)</strong><br><span style="font-size: 9pt; color: #64748b;">Kaşe / İmza</span></td>
            </tr>
        </table>
    </div>
</div>

</body>
</html>