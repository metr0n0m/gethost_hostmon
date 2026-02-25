<?php
require_once __DIR__ . '/config.php';
$pageTitle = t('app_title');
require_once __DIR__ . '/app/services/SettingsService.php';
require_once __DIR__ . '/app/services/QueryHistoryService.php';
require_once 'header.php';

$refreshSeconds = settings_get_refresh_seconds();
$topQueries = query_top_queries($pdo, 5);
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= htmlspecialchars(t('resolve_title')) ?></h5>
            </div>
            <div class="card-body">
                <form id="resolve-form" class="mb-3">
                    <label for="resolve-input" class="form-label"><?= htmlspecialchars(t('resolve_input_label')) ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="resolve-input" name="query" placeholder="<?= htmlspecialchars(t('resolve_placeholder')) ?>" required>
                        <button class="btn btn-primary" type="submit"><i class="fa fa-magnifying-glass"></i> <?= htmlspecialchars(t('resolve_button')) ?></button>
                    </div>
                </form>
                <div id="resolve-result" class="d-none"></div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><?= htmlspecialchars(t('history_top_title')) ?></h6>
                <small class="text-muted">Top 5</small>
            </div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('table_type')) ?></th>
                            <th><?= htmlspecialchars(t('table_query')) ?></th>
                            <th><?= htmlspecialchars(t('table_count')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$topQueries): ?>
                        <tr><td colspan="3" class="text-muted"><?= htmlspecialchars(t('no_data')) ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($topQueries as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$row['query_type']) ?></td>
                                <td><?= htmlspecialchars((string)$row['query_value_norm']) ?></td>
                                <td><strong><?= (int)$row['counter'] ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <div class="card shadow-sm border-primary-subtle">
            <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><?= htmlspecialchars(t('monitoring_title')) ?></h5>
                <button id="reload-sites" class="btn btn-sm btn-primary"><i class="fa fa-rotate"></i> <?= htmlspecialchars(t('monitoring_recheck_all')) ?></button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle monitor-table index-monitor-table">
                    <thead class="table-light">
                        <tr>
                            <th><?= htmlspecialchars(t('table_name')) ?></th>
                            <th><?= htmlspecialchars(t('table_url')) ?></th>
                            <th><?= htmlspecialchars(t('table_status')) ?></th>
                            <th><?= htmlspecialchars(t('table_last_check')) ?></th>
                            <th class="text-end"><?= htmlspecialchars(t('table_action')) ?></th>
                        </tr>
                    </thead>
                    <tbody id="sites-table-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    let INDEX_RECHECK_SECONDS = <?= (int)$refreshSeconds ?>;
</script>

<?php require_once 'footer.php'; ?>
