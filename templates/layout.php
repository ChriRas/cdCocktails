<?php /** @var \App\Helpers $helpers */
$DEV_MODE = ($_ENV['APP_ENV'] ?? '') === 'dev';
?>
<!doctype html>
<html lang="de">
<head>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#000000">

    <!-- iOS "Add to Home Screen" -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Cocktails">

    <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title><?= $helpers->h($title ?? 'Cocktailkarte') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Wix+Madefor+Text:wght@400;500;700&family=Wix+Madefor+Display:wght@400;500;700&display=swap"
          rel="stylesheet">

    <link href="/assets/app.css" rel="stylesheet">

    <?php if (!empty($preload) && is_array($preload)): ?>
        <?php foreach ($preload as $u): ?>
            <link rel="preload" as="image" href="<?= $helpers->h($u) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <?= $headExtra ?? '' ?>
</head>
<body>
<?= $content ?? '' ?>
<?= $bodyExtra ?? '' ?>
<script>
    window.__DEV_MODE__ = <?= $DEV_MODE ? 'true' : 'false' ?>;
</script>
<script src="/assets/pwa.js" defer></script>

<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
    }
</script>
</body>
</html>
