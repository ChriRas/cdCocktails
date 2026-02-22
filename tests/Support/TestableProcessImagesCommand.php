<?php
declare(strict_types=1);

namespace Tests\Support;

use App\Command\ProcessImagesCommand;
use App\Config;

class TestableProcessImagesCommand extends ProcessImagesCommand
{
    /**
     * @return array{incoming:string, full:string, thumbs:string, data:string, infoIn:string, infoOut:string}
     */
    public function exposedBuildPaths(): array
    {
        return $this->buildPaths();
    }

    public function exposedSlugify(string $name): string
    {
        $slug = $this->slugify($name);
        return $slug === '' ? Config::IMAGE_FALLBACK_SLUG : $slug;
    }

    public function exposedIsAllowedImagePath(string $path): bool
    {
        return $this->isAllowedImagePath($path);
    }

    protected function renderWebp(string $sourcePath, string $targetPath, int $maxSize, int $quality): void
    {
        file_put_contents($targetPath, sprintf('stub:%s:%d:%d', basename($sourcePath), $maxSize, $quality));
    }
}
