<?php
require_once __DIR__ . '/config.php';
$pageTitle = t('nav_history');
require_once 'header.php';
?>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= htmlspecialchars(t('history_top_title')) ?></h5>
        <small class="text-muted"><?= htmlspecialchars(t('history_auto_updates')) ?></small>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= htmlspecialchars(t('table_type')) ?></th>
                    <th><?= htmlspecialchars(t('table_query')) ?></th>
                    <th><?= htmlspecialchars(t('table_count')) ?></th>
                    <th><?= htmlspecialchars(t('table_last_time')) ?></th>
                </tr>
            </thead>
            <tbody id="top-counters-body"></tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><?= htmlspecialchars(t('history_title')) ?></h5>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th><?= htmlspecialchars(t('table_time')) ?></th>
                    <th><?= htmlspecialchars(t('table_query')) ?></th>
                    <th><?= htmlspecialchars(t('table_result')) ?></th>
                    <th><?= htmlspecialchars(t('table_counter')) ?></th>
                    <th><?= htmlspecialchars(t('table_client_ip')) ?></th>
                    <th><?= htmlspecialchars(t('table_os_browser')) ?></th>
                    <th><?= htmlspecialchars(t('table_tor')) ?></th>
                    <th><?= htmlspecialchars(t('table_proxy')) ?></th>
                    <th><?= htmlspecialchars(t('table_source_host')) ?></th>
                    <th><?= htmlspecialchars(t('action_add_tracking')) ?></th>
                </tr>
            </thead>
            <tbody id="query-history-body"></tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
