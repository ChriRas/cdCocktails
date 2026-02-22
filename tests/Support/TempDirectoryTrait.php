<?php
declare(strict_types=1);

namespace Tests\Support;

trait TempDirectoryTrait
{
    /** @var string[] */
    private array $tempDirectories = [];

    protected function createTempDirectory(string $prefix): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . '-' . bin2hex(random_bytes(8));
        mkdir($dir, 0777, true);
        $this->tempDirectories[] = $dir;
        return $dir;
    }

    protected function createImageTree(string $root): void
    {
        mkdir($root . '/incoming', 0777, true);
        mkdir($root . '/full', 0777, true);
        mkdir($root . '/thumbs', 0777, true);
        mkdir($root . '/data', 0777, true);
    }

    protected function cleanupTempDirectories(): void
    {
        foreach ($this->tempDirectories as $dir) {
            $this->removeDirectory($dir);
        }
        $this->tempDirectories = [];
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $entries = scandir($dir);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($dir);
    }
}
