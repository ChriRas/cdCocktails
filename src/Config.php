<?php
declare(strict_types=1);

namespace App;

class Config
{
  public const int IMAGE_BATCH_RESET_MINUTES = 5;
  public const int IMAGE_FULL_MAX_SIZE = 1440;
  public const int IMAGE_FULL_QUALITY = 82;
  public const int IMAGE_THUMB_MAX_SIZE = 1080;
  public const int IMAGE_THUMB_QUALITY = 78;
  public const string IMAGE_FALLBACK_SLUG = 'bild';
  public const array IMAGE_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  public const string DEFAULT_TZ = 'Europe/Berlin';
  public const string LOGO_PATH = '/assets/logo.webp';

  public function projectRoot(): string
  {
    return dirname(__DIR__);
  }

  public function imagesRoot(): string
  {
    $fromEnvRaw = $_ENV['IMAGES_ROOT'] ?? $_SERVER['IMAGES_ROOT'] ?? getenv('IMAGES_ROOT');
    $fromEnv = is_string($fromEnvRaw) ? $fromEnvRaw : null;
    $root = is_string($fromEnv) && trim($fromEnv) !== ''
      ? trim($fromEnv)
      : $this->projectRoot() . '/cocktail-images';

    if (!$this->isAbsolutePath($root)) {
      $root = $this->projectRoot() . '/' . $root;
    }

    return rtrim($root, DIRECTORY_SEPARATOR);
  }

  public function incomingDir(): string
  {
    return $this->imagesRoot() . '/incoming';
  }

  public function fullDir(): string
  {
    return $this->imagesRoot() . '/full';
  }

  public function thumbsDir(): string
  {
    return $this->imagesRoot() . '/thumbs';
  }

  public function dataDir(): string
  {
    return $this->imagesRoot() . '/data';
  }

  public function infoFile(): string
  {
    return $this->dataDir() . '/info.yml';
  }

  protected function isAbsolutePath(string $path): bool
  {
    return str_starts_with($path, '/')
      || str_starts_with($path, '\\')
      || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
  }
}
