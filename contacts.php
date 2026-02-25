<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/app/services/AppInfoService.php';

$pageTitle = t('nav_contacts');
$contacts = app_info_get_contacts($pdo);

require_once 'header.php';
?>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= htmlspecialchars(t('nav_contacts')) ?></h5>
    </div>
    <div class="card-body">
        <div class="row gy-3">
            <div class="col-md-4">
                <div class="fw-semibold">Email</div>
                <div>
                    <?php if ($contacts['email'] !== ''): ?>
                        <a href="mailto:<?= htmlspecialchars($contacts['email']) ?>"><?= htmlspecialchars($contacts['email']) ?></a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold">Telegram</div>
                <div>
                    <?php if ($contacts['telegram'] !== ''): ?>
                        <a href="<?= htmlspecialchars($contacts['telegram']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($contacts['telegram']) ?></a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="fw-semibold">GitHub</div>
                <div>
                    <?php if ($contacts['github'] !== ''): ?>
                        <a href="<?= htmlspecialchars($contacts['github']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($contacts['github']) ?></a>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>

