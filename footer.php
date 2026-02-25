<?php
?>
</main>

<footer class="footer-fixed bg-light border-top">
    <div class="container py-2 text-center text-muted">
        &copy; <?= date('Y') ?> <?= htmlspecialchars(t('app_title')) ?>
    </div>
</footer>

<script>
window.I18N = <?= json_encode(js_i18n_payload(), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if ($currentPage === 'index.php') {
    $p = __DIR__ . '/assets/js/app_index.js';
    $v = file_exists($p) ? filemtime($p) : time();
    echo '<script src="assets/js/app_index.js?v=' . $v . '"></script>';
} elseif ($currentPage === 'dashboard.php') {
    $p = __DIR__ . '/assets/js/app_dashboard.js';
    $v = file_exists($p) ? filemtime($p) : time();
    echo '<script src="assets/js/app_dashboard.js?v=' . $v . '"></script>';
} elseif ($currentPage === 'history.php') {
    $p = __DIR__ . '/assets/js/app_history.js';
    $v = file_exists($p) ? filemtime($p) : time();
    echo '<script src="assets/js/app_history.js?v=' . $v . '"></script>';
}
?>

</body>
</html>
