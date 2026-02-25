<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/AppInfoService.php';

$pageTitle = t('nav_about');
$aboutText = app_info_get_about_text($pdo);

require_once 'header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= htmlspecialchars(t('nav_about')) ?></h5>
    </div>
    <div class="card-body">
        <p class="mb-0"><?= nl2br(htmlspecialchars($aboutText)) ?></p>
    </div>
</div>

<?php require_once 'footer.php'; ?>

