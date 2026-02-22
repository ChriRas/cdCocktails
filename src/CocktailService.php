<?php
declare(strict_types=1);

namespace App;

use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use Symfony\Component\Yaml\Yaml;
use RuntimeException;
use Throwable;

class CocktailService
{
    public function __construct(private Helpers $helpers, private Config $config)
    {
    }

    /**
     * @return array{hasInfo:false}|array{hasInfo:true, party_name:?string, startAt:?CarbonImmutable, tz:CarbonTimeZone}
     */
    public function loadInfo(): array
    {
        $path = $this->config->infoFile();
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
    public function listProcessedBaseNames(): array
    {
        $fullDir = $this->config->fullDir();
        $this->assertReadableDir($fullDir);
        $files = $this->safeScanDir($fullDir);

        $names = [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            if (!$this->helpers->isImageFilename($f)) {
                continue;
            }
            $abs = $fullDir . DIRECTORY_SEPARATOR . $f;
            if (is_file($abs)) {
                $names[] = $f;
            }
        }

        natcasesort($names);
        return array_values($names);
    }

    public function resolveFull(string $basename): ?string
    {
        $basename = $this->helpers->basenameSafe($basename);
        if (!$this->helpers->isImageFilename($basename)) {
            return null;
        }
        $abs = $this->config->fullDir() . DIRECTORY_SEPARATOR . $basename;
        return is_file($abs) ? $abs : null;
    }

    public function resolveThumb(string $basename): ?string
    {
        $basename = $this->helpers->basenameSafe($basename);
        if (!$this->helpers->isImageFilename($basename)) {
            return null;
        }
        $abs = $this->config->thumbsDir() . DIRECTORY_SEPARATOR . $basename;
        return is_file($abs) ? $abs : null;
    }

    /**
     * @return array{0:int, 1:int}
     */
    public function getImageDimensions(string $fullPath, int $defaultW = 1440, int $defaultH = 960): array
    {
        $w = $defaultW;
        $h = $defaultH;

        if (!is_file($fullPath) || !is_readable($fullPath)) {
            return [$w, $h];
        }

        $size = getimagesize($fullPath);
        if (is_array($size)) {
            $w = (int)$size[0];
            $h = (int)$size[1];
        }

        return [$w, $h];
    }

    /**
     * @return array<int, array{thumb:string, full:string, w:int, h:int, alt:string}>
     */
    public function buildGalleryItems(): array
    {
        $items = [];
        $baseNames = $this->listProcessedBaseNames();

        foreach ($baseNames as $name) {
            $fullPath = $this->resolveFull($name);
            $thumbPath = $this->resolveThumb($name);

            if (!$fullPath || !$thumbPath) {
                continue;
            }

            [$w, $h] = $this->getImageDimensions($fullPath);

            $items[] = [
                'thumb' => '/image.php?t=thumb&f=' . rawurlencode($name),
                'full'  => '/image.php?t=full&f=' . rawurlencode($name),
                'w'     => $w,
                'h'     => $h,
                'alt'   => $name,
            ];
        }

        return $items;
    }

    private function assertReadableDir(string $dir): void
    {
        if (!is_dir($dir)) {
            throw new RuntimeException("Directory does not exist: {$dir}");
        }
        if (!is_readable($dir)) {
            throw new RuntimeException("Directory not readable: {$dir}");
        }
    }

    /** @return string[] */
    private function safeScanDir(string $dir): array
    {
        set_error_handler(static function (int $severity, string $message) use ($dir) {
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
