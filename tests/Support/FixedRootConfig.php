<?php
declare(strict_types=1);

namespace Tests\Support;

use App\Config;

class FixedRootConfig extends Config
{
    public function __construct(private readonly string $root)
    {
    }

    public function imagesRoot(): string
    {
        return rtrim($this->root, DIRECTORY_SEPARATOR);
    }
}
