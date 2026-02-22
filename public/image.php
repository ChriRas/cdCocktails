<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config;
use App\Helpers;
use App\CocktailService;

header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true);

$helpers = new Helpers();
$config = new Config();
$service = new CocktailService($helpers, $config);

$type = $_GET['t'] ?? 'full';
$f = $_GET['f'] ?? '';

if (!is_string($type) || !in_array($type, ['full','thumb'], true)) {
  http_response_code(400);
  exit('Bad Request');
}
if (!is_string($f) || $f === '') {
  http_response_code(400);
  exit('Bad Request');
}

$path = $type === 'thumb' ? $service->resolveThumb($f) : $service->resolveFull($f);
if (!$path) {
  http_response_code(404);
  exit('Not Found');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = match ($ext) {
  'jpg', 'jpeg' => 'image/jpeg',
  'png' => 'image/png',
  'webp' => 'image/webp',
  'gif' => 'image/gif',
  default => 'application/octet-stream'
};

$mtime = filemtime($path) ?: time();
$size  = filesize($path) ?: 0;
$etag  = '"' . sha1($path . '|' . $mtime . '|' . $size) . '"';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)$size);
header('ETag: ' . $etag);
header('Cache-Control: public, max-age=86400');

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
  http_response_code(304);
  exit;
}

$fp = fopen($path, 'rb');
if ($fp === false) {
  http_response_code(500);
  exit('Could not open file');
}

while (!feof($fp)) {
  $buf = fread($fp, 1024 * 1024);
  if ($buf === false) {
      break;
  }
  echo $buf;
  flush();
}
fclose($fp);
