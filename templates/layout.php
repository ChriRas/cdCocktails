<?php /** @var \App\Helpers $helpers */ ?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <title><?= $helpers->h($title ?? 'Cocktailkarte') ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Wix+Madefor+Text:wght@400;500;700&family=Wix+Madefor+Display:wght@400;500;700&display=swap" rel="stylesheet">

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
</body>
</html>
