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
if (is_file($dotenvPath . '/.env')) {
    Dotenv::createImmutable($dotenvPath)->safeLoad();
}

header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);

// Never cache HTML output (TimeWindow must react immediately)
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0', true);
header('Pragma: no-cache', true);
header('Expires: 0', true);
header('Surrogate-Control: no-store', true);

$helpers = new Helpers();
$service = new CocktailService($helpers);

/**
 * Main request handler.
 */
function handleRequest(Helpers $helpers, CocktailService $service): void
{
    $info = $service->loadInfo();

    $tz = ($info['tz'] ?? null) instanceof CarbonTimeZone
        ? $info['tz']
        : new CarbonTimeZone(Config::DEFAULT_TZ);

    $now = CarbonImmutable::now($tz);

    // DEV debug: show the computed time window (enable with ?debug=1)
    $appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod';
    $isDev = ($appEnv === 'dev');
    $debug = $isDev && (($_GET['debug'] ?? '') === '1');

    $window = new TimeWindow(
        enforced: (($info['hasInfo'] ?? false) === true) && (($info['startAt'] ?? null) instanceof CarbonImmutable),
        startAt: $info['startAt'] ?? null
    );

   $helpers->debugHelper($debug, $info, $appEnv, $tz, $now, $window);

    if (!$window->isOpen($now)) {
        $helpers->renderClosed();
        return;
    }

    $baseNames = $service->listProcessedBaseNames();
    if ($baseNames === []) {
        $helpers->renderClosed();
        return;
    }

    $infoBlock = [];
    if (($info['hasInfo'] ?? false) === true && is_string($info['party_name'] ?? null)) {
        $infoBlock = ['party' => $info['party_name']];
    }

    $items = $service->buildGalleryItems();
    if ($items === []) {
        $helpers->renderClosed();
        return;
    }

    $preload = [$items[0]['thumb']];
    if (isset($items[1])) {
        $preload[] = $items[1]['thumb'];
    }

    $headExtra = '<link rel="stylesheet" href="/vendor/photoswipe/photoswipe.css">';
    $bodyExtra = '<script type="module" src="/assets/gallery.js"></script>';

    $title = 'Cocktailkarte';
    ob_start();
    include __DIR__ . '/../templates/gallery.php';
    $content = (string)ob_get_clean();

    $helpers->renderLayout($title, $content, $preload, $headExtra, $bodyExtra);
}

try {
    handleRequest($helpers, $service);
} catch (Throwable $e) {
    $helpers->renderClosed();
}
