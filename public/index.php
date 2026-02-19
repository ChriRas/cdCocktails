<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\Helpers;
use App\CocktailService;
use App\TimeWindow;
use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use Dotenv\Dotenv;

// .env laden (nur wenn vorhanden)
$dotenvPath = dirname(__DIR__);
if (file_exists($dotenvPath . '/.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->safeLoad();
}

header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);

$helpers = new Helpers();
$service = new CocktailService($helpers);

try {
  $info = $service->loadInfo();

  $tz = ($info['tz'] ?? null) instanceof CarbonTimeZone
    ? $info['tz']
    : new CarbonTimeZone(Config::DEFAULT_TZ);

  $now = CarbonImmutable::now($tz);

  $window = new TimeWindow(
    enforced: (($info['hasInfo'] ?? false) === true) && (($info['startAt'] ?? null) instanceof CarbonImmutable),
    startAt: $info['startAt'] ?? null
  );

  if (!$window->isOpen($now)) {
    $title = 'Bar geschlossen';
    $preload = [];
    $headExtra = '';
    $bodyExtra = '';
    ob_start();
    include __DIR__ . '/../templates/closed.php';
    $content = ob_get_clean();
    include __DIR__ . '/../templates/layout.php';
    exit;
  }

  $basenames = $service->listProcessedBasenames();
  if (count($basenames) === 0) {
    $title = 'Bar geschlossen';
    $preload = [];
    $headExtra = '';
    $bodyExtra = '';
    ob_start();
    include __DIR__ . '/../templates/closed.php';
    $content = ob_get_clean();
    include __DIR__ . '/../templates/layout.php';
    exit;
  }

    $infoBlock = [];
    if (($info['hasInfo'] ?? false) === true && is_string($info['party_name'] ?? null)) {
        $infoBlock = [
            'party' => $info['party_name'],
        ];
    }

  $items = [];
  foreach ($basenames as $name) {
    $fullPath = $service->resolveFull($name);
    $thumbPath = $service->resolveThumb($name);
    if (!$fullPath || !$thumbPath) continue;

    $size = @getimagesize($fullPath);
    if (!$size) { $w = 1440; $h = 960; }
    else { $w = (int)$size[0]; $h = (int)$size[1]; }

    $items[] = [
      'thumb' => '/image.php?t=thumb&f=' . rawurlencode($name),
      'full'  => '/image.php?t=full&f=' . rawurlencode($name),
      'w' => $w,
      'h' => $h,
      'alt' => $name,
    ];
  }

  if (count($items) === 0) {
    $title = 'Bar geschlossen';
    $preload = [];
    $headExtra = '';
    $bodyExtra = '';
    ob_start();
    include __DIR__ . '/../templates/closed.php';
    $content = ob_get_clean();
    include __DIR__ . '/../templates/layout.php';
    exit;
  }

  $preload = [$items[0]['thumb']];
  if (isset($items[1])) $preload[] = $items[1]['thumb'];

  $headExtra = '<link rel="stylesheet" href="/vendor/photoswipe/photoswipe.css">';
  $bodyExtra = '<script type="module" src="/assets/gallery.js"></script>';

  $title = 'Cocktailkarte';
  ob_start();
  include __DIR__ . '/../templates/gallery.php';
  $content = ob_get_clean();
  include __DIR__ . '/../templates/layout.php';

} catch (Throwable) {
  $title = 'Bar geschlossen';
  $preload = [];
  $headExtra = '';
  $bodyExtra = '';
  ob_start();
  include __DIR__ . '/../templates/closed.php';
  $content = ob_get_clean();
  include __DIR__ . '/../templates/layout.php';
}
