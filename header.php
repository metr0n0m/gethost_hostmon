<?php
if (!isset($pageTitle)) {
    $pageTitle = t('app_title');
}
$currentCode = function_exists('lang_code') ? lang_code() : 'eng';
?>
<!DOCTYPE html>
<html lang="<?= $currentCode === 'heb' ? 'he' : ($currentCode === 'rus' ? 'ru' : 'en') ?>" dir="<?= $currentCode === 'heb' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/custom.css?v=<?= file_exists(__DIR__ . '/assets/css/custom.css') ? filemtime(__DIR__ . '/assets/css/custom.css') : time() ?>">
</head>
<body>
<header class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php"><?= htmlspecialchars(t('app_title')) ?></a>
        <div class="navbar-nav">
            <a class="nav-link" href="index.php"><?= htmlspecialchars(t('nav_ip_host')) ?></a>
            <a class="nav-link" href="history.php"><?= htmlspecialchars(t('nav_history')) ?></a>
            <a class="nav-link" href="dashboard.php"><?= htmlspecialchars(t('nav_monitor')) ?></a>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <a class="btn btn-sm btn-outline-light" href="?lang=eng" title="English">
                <img src="https://flagcdn.com/20x15/gb.png" width="20" height="15" alt="English">
            </a>
            <a class="btn btn-sm btn-outline-light" href="?lang=rus" title="Русский">
                <img src="https://flagcdn.com/20x15/ru.png" width="20" height="15" alt="Русский">
            </a>
            <a class="btn btn-sm btn-outline-light" href="?lang=heb" title="עברית">
                <img src="https://flagcdn.com/20x15/il.png" width="20" height="15" alt="עברית">
            </a>
        </div>
</header>

<main class="container my-4" id="alive">
