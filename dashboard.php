<?php
require_once __DIR__ . '/config.php';
$pageTitle = t('dashboard_title');
require_once __DIR__ . '/app/services/SettingsService.php';
require_once 'header.php';

$refreshSeconds = settings_get_refresh_seconds();
?>

<div class="card shadow-sm mb-4 border-primary-subtle">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0"><?= htmlspecialchars(t('dashboard_title')) ?></h5>
        <div class="d-flex align-items-center gap-2">
            <label for="refresh-seconds-input" class="form-label mb-0"><?= htmlspecialchars(t('dashboard_refresh')) ?></label>
            <input id="refresh-seconds-input" type="number" min="5" max="3600" class="form-control form-control-sm" style="width:100px" value="<?= (int)$refreshSeconds ?>">
            <button id="save-refresh" class="btn btn-sm btn-outline-primary"><?= htmlspecialchars(t('dashboard_save')) ?></button>
            <button id="force-recheck" class="btn btn-sm btn-primary"><i class="fa fa-rotate"></i> <?= htmlspecialchars(t('dashboard_force_recheck')) ?></button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle monitor-table dashboard-monitor-table">
            <thead class="table-light">
                <tr>
                    <th><?= htmlspecialchars(t('table_name')) ?></th>
                    <th><?= htmlspecialchars(t('table_url')) ?></th>
                    <th><?= htmlspecialchars(t('table_status')) ?></th>
                    <th><?= htmlspecialchars(t('table_http')) ?></th>
                    <th><?= htmlspecialchars(t('table_response_ms')) ?></th>
                    <th><?= htmlspecialchars(t('table_last_check')) ?></th>
                    <th class="text-end"><?= htmlspecialchars(t('table_action')) ?></th>
                </tr>
            </thead>
            <tbody id="dashboard-table-body"></tbody>
        </table>
    </div>
</div>

<script>
    let DASHBOARD_REFRESH_SECONDS = <?= (int)$refreshSeconds ?>;
</script>

<?php require_once 'footer.php'; ?>
