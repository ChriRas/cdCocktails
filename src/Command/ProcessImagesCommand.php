<?php
declare(strict_types=1);

namespace App\Command;

use App\Config;
use ErrorException;
use Imagick;
use ImagickException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'app:images:process',
    description: 'Processes incoming images to full and thumbnail WebP files.'
)]
class ProcessImagesCommand extends Command
{
    public function __construct(private readonly Config $config)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if (!class_exists(Imagick::class)) {
                throw new RuntimeException('The imagick PHP extension is not available.');
            }

            $paths = $this->buildPaths();
            $this->ensureDirectories([$paths['full'], $paths['thumbs'], $paths['data']]);

            if (
                $this->hasNewUploads($paths['incoming'])
                && $this->processedOlderThanMinutes([$paths['full'], $paths['thumbs']])
            ) {
                $this->clearTopLevelFiles($paths['full']);
                $this->clearTopLevelFiles($paths['thumbs']);
                if (is_file($paths['infoOut'])) {
                    $this->unlinkFile($paths['infoOut']);
                }
                $io->writeln('Detected new upload batch. Existing processed files were cleared.');
            }

            $this->moveInfoFile($paths['infoIn'], $paths['infoOut']);

            $processed = 0;
            $failed = 0;

            foreach ($this->listIncomingImageFiles($paths['incoming']) as $sourcePath) {
                try {
                    $basename = pathinfo($sourcePath, PATHINFO_FILENAME);
                    $slug = $this->slugify($basename);
                    if ($slug === '') {
                        $slug = Config::IMAGE_FALLBACK_SLUG;
                    }

                    $unique = $this->uniqueName($slug, $paths['full'], $paths['thumbs']);
                    $fullTarget = $paths['full'] . DIRECTORY_SEPARATOR . $unique . '.webp';
                    $thumbTarget = $paths['thumbs'] . DIRECTORY_SEPARATOR . $unique . '.webp';

                    $this->renderWebp(
                        $sourcePath,
                        $fullTarget,
                        Config::IMAGE_FULL_MAX_SIZE,
                        Config::IMAGE_FULL_QUALITY
                    );
                    $this->renderWebp(
                        $sourcePath,
                        $thumbTarget,
                        Config::IMAGE_THUMB_MAX_SIZE,
                        Config::IMAGE_THUMB_QUALITY
                    );
                    $this->unlinkFile($sourcePath);

                    $processed++;
                } catch (Throwable $e) {
                    $failed++;
                    $io->warning(sprintf('Could not process "%s": %s', basename($sourcePath), $e->getMessage()));
                }
            }

            if ($failed > 0) {
                $io->error(sprintf('Finished with %d error(s). Processed successfully: %d.', $failed, $processed));
                return Command::FAILURE;
            }

            $io->success(sprintf('Done. Processed %d image(s).', $processed));

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @return array{incoming:string, full:string, thumbs:string, data:string, infoIn:string, infoOut:string}
     */
    protected function buildPaths(): array
    {
        return [
            'incoming' => $this->config->incomingDir(),
            'full' => $this->config->fullDir(),
            'thumbs' => $this->config->thumbsDir(),
            'data' => $this->config->dataDir(),
            'infoIn' => $this->config->incomingDir() . DIRECTORY_SEPARATOR . 'info.yml',
            'infoOut' => $this->config->infoFile(),
        ];
    }

    /**
     * @param string[] $dirs
     * @throws ErrorException
     */
    protected function ensureDirectories(array $dirs): void
    {
        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            $created = $this->callWithErrorHandler(
                static fn(): bool => mkdir($dir, 0775, true),
                sprintf('Could not create directory "%s"', $dir)
            );

            if (!$created && !is_dir($dir)) {
                throw new RuntimeException(sprintf('Could not create directory "%s"', $dir));
            }
        }
    }

    protected function hasNewUploads(string $incomingDir): bool
    {
        foreach ($this->listDirectoryFiles($incomingDir) as $path) {
            if (basename($path) === 'info.yml') {
                continue;
            }
            if ($this->isAllowedImagePath($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string[] $dirs
     * @throws ErrorException
     */
    protected function processedOlderThanMinutes(array $dirs): bool
    {
        $threshold = time() - (Config::IMAGE_BATCH_RESET_MINUTES * 60);

        foreach ($dirs as $dir) {
            foreach ($this->listDirectoryFilesRecursively($dir) as $path) {
                $mtime = $this->callWithErrorHandler(
                    static fn() => filemtime($path),
                    sprintf('Could not read mtime of "%s"', $path)
                );

                if (!is_int($mtime) && !is_float($mtime)) {
                    throw new RuntimeException(sprintf('filemtime returned invalid value for "%s"', $path));
                }

                if ((int)$mtime < $threshold) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function clearTopLevelFiles(string $dir): void
    {
        foreach ($this->listDirectoryEntries($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }

            $this->unlinkFile($path);
        }
    }

    /**
     * @throws ErrorException
     */
    protected function moveInfoFile(string $source, string $target): void
    {
        if (!is_file($source)) {
            return;
        }

        $copied = $this->callWithErrorHandler(
            static fn(): bool => copy($source, $target),
            sprintf('Could not copy info file "%s" to "%s"', $source, $target)
        );

        if (!$copied) {
            throw new RuntimeException(sprintf('Copy returned false for "%s"', $source));
        }

        $this->unlinkFile($source);
    }

    /**
     * @return string[]
     */
    protected function listIncomingImageFiles(string $incomingDir): array
    {
        $files = [];

        foreach ($this->listDirectoryFiles($incomingDir) as $path) {
            if (basename($path) === 'info.yml') {
                continue;
            }
            if (!$this->isAllowedImagePath($path)) {
                continue;
            }
            $files[] = $path;
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);

        return $files;
    }

    protected function isAllowedImagePath(string $path): bool
    {
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, Config::IMAGE_ALLOWED_EXTENSIONS, true);
    }

    protected function slugify(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[ _]+/', '-', $slug) ?? '';
        $slug = preg_replace('/[^a-z0-9-]+/', '', $slug) ?? '';
        $slug = preg_replace('/-+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug;
    }

    protected function uniqueName(string $base, string $fullDir, string $thumbDir): string
    {
        $candidate = $base;
        $counter = 0;

        while (is_file($fullDir . DIRECTORY_SEPARATOR . $candidate . '.webp') || is_file($thumbDir . DIRECTORY_SEPARATOR . $candidate . '.webp')) {
            $counter++;
            $candidate = sprintf('%s-%d', $base, $counter);
        }

        return $candidate;
    }

    protected function renderWebp(string $sourcePath, string $targetPath, int $maxSize, int $quality): void
    {
        try {
            $image = new Imagick();
            $image->readImage($sourcePath);
            $image->autoOrient();
            $image->stripImage();

            $width = $image->getImageWidth();
            $height = $image->getImageHeight();

            if ($width > $maxSize || $height > $maxSize) {
                $scale = min($maxSize / $width, $maxSize / $height);
                $newWidth = max(1, (int)floor($width * $scale));
                $newHeight = max(1, (int)floor($height * $scale));
                $image->resizeImage($newWidth, $newHeight, Imagick::FILTER_LANCZOS, 1.0);
            }

            $image->setImageFormat('webp');
            $image->setImageCompressionQuality($quality);

            if (!$image->writeImage($targetPath)) {
                throw new RuntimeException(sprintf('Could not write file "%s"', $targetPath));
            }
        } catch (ImagickException $e) {
            throw new RuntimeException(sprintf('Imagick failed for "%s": %s', basename($sourcePath), $e->getMessage()), 0, $e);
        } finally {
            if (isset($image)) {
                $image->clear();
            }
        }
    }

    /**
     * @throws ErrorException
     */
    protected function unlinkFile(string $path): void
    {
        $deleted = $this->callWithErrorHandler(
            static fn(): bool => unlink($path),
            sprintf('Could not delete "%s"', $path)
        );

        if (!$deleted && is_file($path)) {
            throw new RuntimeException(sprintf('unlink returned false for "%s"', $path));
        }
    }

    /**
     * @return string[]
     * @throws ErrorException
     */
    protected function listDirectoryEntries(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $entries = $this->callWithErrorHandler(
            static fn() => scandir($dir),
            sprintf('Could not list directory "%s"', $dir)
        );

        if (!is_array($entries)) {
            throw new RuntimeException(sprintf('scandir returned invalid result for "%s"', $dir));
        }

        return $entries;
    }

    /**
     * @return string[]
     * @throws ErrorException
     */
    protected function listDirectoryFilesRecursively(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $paths = [];

        foreach ($this->listDirectoryEntries($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($path)) {
                $paths[] = $path;
                continue;
            }
            if (is_dir($path)) {
                foreach ($this->listDirectoryFilesRecursively($path) as $nestedPath) {
                    $paths[] = $nestedPath;
                }
            }
        }

        return $paths;
    }

    /**
     * @return string[]
     * @throws ErrorException
     */
    protected function listDirectoryFiles(string $dir): array
    {
        $paths = [];

        foreach ($this->listDirectoryEntries($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }

            $paths[] = $path;
        }

        return $paths;
    }

    protected function callWithErrorHandler(callable $callback, string $context): mixed
    {
        set_error_handler(static function (int $severity, string $message, string $file, int $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            return $callback();
        } catch (Throwable $e) {
            throw new RuntimeException($context . ': ' . $e->getMessage(), 0, $e);
        } finally {
            restore_error_handler();
        }
    }
}
