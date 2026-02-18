<?php
declare(strict_types=1);

namespace App;

class Config {
  public const string IMAGES_ROOT = __DIR__ . '/../cocktail-images';
  public const string FULL_DIR   = self::IMAGES_ROOT . '/full';
  public const string THUMBS_DIR = self::IMAGES_ROOT . '/thumbs';
  public const string DATA_DIR   = self::IMAGES_ROOT . '/data';
  public const string INFO_FILE    = self::DATA_DIR . '/info.yml';

  public const string DEFAULT_TZ = 'Europe/Berlin';
  public const string LOGO_PATH  = '/assets/logo.webp';
}
