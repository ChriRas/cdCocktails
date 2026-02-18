<?php
declare(strict_types=1);

namespace App;

use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use Symfony\Component\Yaml\Yaml;
use RuntimeException;
use Throwable;

class CocktailService {
  public function __construct(private Helpers $helpers) {}

  public function loadInfo(): array {
    $path = Config::INFO_FILE;
    if (!is_file($path)) return ['hasInfo' => false];

    try {
      $info = Yaml::parseFile($path) ?? [];
      $party = is_string($info['party_name'] ?? null) ? $info['party_name'] : null;

      $tzName = is_string($info['timezone'] ?? null) ? $info['timezone'] : Config::DEFAULT_TZ;
      $tz = new CarbonTimeZone($tzName);

      $startAt = null;
      $startRaw = $info['start'] ?? null;
      if (is_string($startRaw) && trim($startRaw) !== '') {
        $startAt = CarbonImmutable::parse($startRaw, $tz);
      }

      return [
        'hasInfo' => true,
        'party_name' => $party,
        'startAt' => $startAt,
        'tz' => $tz,
      ];
    } catch (Throwable) {
      return ['hasInfo' => false];
    }
  }

  /** @return string[] basenames (sorted) present in FULL_DIR */
  public function listProcessedBasenames(): array {
    $this->assertReadableDir(Config::FULL_DIR);
    $files = $this->safeScandir(Config::FULL_DIR);

    $names = [];
    foreach ($files as $f) {
      if ($f === '.' || $f === '..') {
          continue;
      }
      if (!$this->helpers->isImageFilename($f)) {
          continue;
      }
      $abs = Config::FULL_DIR . DIRECTORY_SEPARATOR . $f;
      if (is_file($abs)) {
          $names[] = $f;
      }
    }

    natcasesort($names);
    return array_values($names);
  }

  public function resolveFull(string $basename): ?string {
    $basename = $this->helpers->basenameSafe($basename);
    if (!$this->helpers->isImageFilename($basename)) {
        return null;
    }
    $abs = Config::FULL_DIR . DIRECTORY_SEPARATOR . $basename;
    return is_file($abs) ? $abs : null;
  }

  public function resolveThumb(string $basename): ?string {
    $basename = $this->helpers->basenameSafe($basename);
    if (!$this->helpers->isImageFilename($basename)) {
        return null;
    }
    $abs = Config::THUMBS_DIR . DIRECTORY_SEPARATOR . $basename;
    return is_file($abs) ? $abs : null;
  }

  private function assertReadableDir(string $dir): void {
    if (!is_dir($dir)) {
        throw new RuntimeException("Directory does not exist: {$dir}");
    }
    if (!is_readable($dir)) {
        throw new RuntimeException("Directory not readable: {$dir}");
    }
  }

  /** @return string[] */
  private function safeScandir(string $dir): array {
    set_error_handler(static function(int $severity, string $message) use ($dir) {
      throw new RuntimeException("scandir failed for {$dir}: {$message}");
    });
    try {
      $result = scandir($dir);
      if ($result === false) {
          throw new RuntimeException("scandir returned false for {$dir}");
      }
      return $result;
    } finally {
      restore_error_handler();
    }
  }
}
