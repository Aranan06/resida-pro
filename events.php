<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (!isLoggedIn()) { header('Location: login.php'); exit; }

$events = getEventsBySite($pdo, $user['site_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Etkinlikler - RESİDA PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fa-solid fa-calendar-days me-2 text-cyan"></i>Etkinlikler</h1>
        <a href="resident_panel.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Geri Dön</a>
    </div>

    <div class="row">
        <?php if ($events): foreach ($events as $ev): ?>
        <div class="col-md-6">
            <div class="card mb-3 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($ev['title']) ?></h5>
                    <div class="badge bg-primary mb-2"><i class="fa-solid fa-calendar-day me-1"></i><?= datetime_tr($ev['event_date']) ?></div>
                    <p class="card-text text-muted"><?= nl2br(htmlspecialchars($ev['description'] ?? 'Detay belirtilmemiş.')) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; else: ?>
            <div class="col-12"><div class="alert alert-info text-center">Yakın zamanda planlanan bir etkinlik bulunmuyor.</div></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>