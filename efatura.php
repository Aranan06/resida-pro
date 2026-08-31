<?php
// efatura.php – e-Fatura / e-Arşiv UBL-TR stub
// Gerçek entegrasyon için GIB entegratör (Foriba/Logo/eLogo) API anahtarları gerekir.
// Bu dosya şirketsizken mock çalışır, şirket sonrası entegratörle birebir uyumlu iskelettir.

require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (!isManager() && !isAdmin()) { http_response_code(403); die('Yetkisiz'); }

$mode = $_GET['mode'] ?? 'info'; // info | generate

if ($mode === 'info') {
    $integrator = $_ENV['EFATURA_INTEGRATOR'] ?? 'mock';
    $enabled = ($integrator !== 'mock' && !empty($_ENV['EFATURA_API_KEY']));
    ?>
    <!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>e-Fatura – RESİDA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
    <body class="bg-light"><div class="container py-5" style="max-width:700px">
    <h3><i class="fa-solid fa-file-invoice me-2"></i>e-Fatura / e-Arşiv</h3>
    <div class="card mt-4"><div class="card-body">
      <p class="small text-muted">Durum: 
        <?php if($enabled): ?><span class="badge bg-success">Entegratör aktif (<?= htmlspecialchars($integrator) ?>)</span>
        <?php else: ?><span class="badge bg-warning text-dark">Mock / Hazır (şirket sonrası aktif)</span><?php endif; ?>
      </p>
      <ul class="small">
        <li>Mevcut makbuzlar: <code>receipt.php?id=123</code> → yazdırılabilir HTML, resmi e-Fatura yerine geçerli değil.</li>
        <li>Canlı e-Fatura için: <code>.env</code> içine <code>EFATURA_INTEGRATOR=foriba</code>, <code>EFATURA_API_KEY</code>, <code>EFATURA_API_SECRET</code> ekleyin.</li>
        <li>Desteklenen entegratörler: Foriba, Logo Connect, eLogo, Uyumsoft (UBL-TR 2.1).</li>
        <li>Bu iskelet GIB UBL-TR oluşturur, entegratöre POST eder, UUID döner ve <code>payments</code> tablosuna işler.</li>
      </ul>
      <div class="alert alert-info small">Şirketsizken resmi e-Fatura kesemezsin (VKN şart). Şimdilik makbuz sistemi yeterli, kod hazır.</div>
      <a href="manager_panel.php" class="btn btn-secondary btn-sm">Panele Dön</a>
      <a href="?mode=generate&due_id=1" class="btn btn-outline-primary btn-sm" onclick="return confirm('Demo UBL oluşturulsun mu?')">Demo UBL Oluştur</a>
    </div></div></div></body></html>
    <?php exit;
}

if ($mode === 'generate') {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="efatura-demo-'.date('YmdHis').'.xml"');
    $dueId = (int)($_GET['due_id'] ?? 1);
    $uuid = strtoupper(sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)));
    // Minimal UBL-TR iskelet
    echo <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2" xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2" xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2">
  <cbc:UBLVersionID>2.1</cbc:UBLVersionID>
  <cbc:CustomizationID>TR1.2</cbc:CustomizationID>
  <cbc:ProfileID>TICARIFATURA</cbc:ProfileID>
  <cbc:ID>RES{$dueId}</cbc:ID>
  <cbc:UUID>{$uuid}</cbc:UUID>
  <cbc:IssueDate>2026-08-29</cbc:IssueDate>
  <cbc:InvoiceTypeCode>500</cbc:InvoiceTypeCode>
  <cbc:Note>RESIDA PRO demo e-Fatura - Entegratör mock</cbc:Note>
  <cac:AccountingSupplierParty><cac:Party><cac:PartyName><cbc:Name>RESIDA PRO</cbc:Name></cac:PartyName></cac:Party></cac:AccountingSupplierParty>
  <cac:LegalMonetaryTotal><cbc:PayableAmount currencyID="TRY">100.00</cbc:PayableAmount></cac:LegalMonetaryTotal>
</Invoice>
XML;
    exit;
}
