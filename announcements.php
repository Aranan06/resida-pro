<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (!currentUser()) { header('Location: login.php'); exit; }

$announcements = getAnnouncementsBySite($pdo, $user['site_id']);
$events = getEventsBySite($pdo, $user['site_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İletişim - RESİDA PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fa-solid fa-comments me-2 text-accent"></i>İletişim Merkezi</h1>
        <a href="resident_panel.php" class="btn btn-outline-secondary"><i class="fa-solid fa-house me-1"></i> Panele Dön</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="commTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#announcements">
                <i class="fa-solid fa-bullhorn me-2"></i>Duyurular
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#events">
                <i class="fa-solid fa-calendar-days me-2"></i>Etkinlikler
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="announcements">
            <?php foreach ($announcements as $ann): ?>
                <div class="card mb-3 border-0 shadow-sm" data-bs-toggle="modal" data-bs-target="#annModal<?= $ann['id'] ?>" style="cursor:pointer">
                    <div class="card-body">
                        <h5 class="fw-bold text-accent"><?= htmlspecialchars($ann['title']) ?></h5>
                        <p class="text-muted small"><i class="fa-solid fa-clock me-1"></i><?= datetime_tr($ann['created_at']) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
        </div>

        <div class="tab-pane fade" id="events">
            <div class="row">
                <?php foreach ($events as $ev): ?>
                <div class="col-md-6">
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="fw-bold"><?= htmlspecialchars($ev['title']) ?></h5>
                            <span class="badge bg-primary mb-2"><?= datetime_tr($ev['event_date']) ?></span>
                            <p class="text-muted"><?= htmlspecialchars($ev['description'] ?? 'Detay yok') ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>