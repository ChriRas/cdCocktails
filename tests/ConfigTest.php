<?php
declare(strict_types=1);

namespace Tests;

use App\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private bool $hadEnv;
    private bool $hadServer;
    private string|false $oldGetEnv;
    private string $oldEnvValue = '';
    private string $oldServerValue = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->hadEnv = array_key_exists('IMAGES_ROOT', $_ENV);
        $this->hadServer = array_key_exists('IMAGES_ROOT', $_SERVER);
        $this->oldGetEnv = getenv('IMAGES_ROOT');
        if ($this->hadEnv) {
            $this->oldEnvValue = (string)$_ENV['IMAGES_ROOT'];
        }
        if ($this->hadServer) {
            $this->oldServerValue = (string)$_SERVER['IMAGES_ROOT'];
        }
    }

    protected function tearDown(): void
    {
        if ($this->hadEnv) {
            $_ENV['IMAGES_ROOT'] = $this->oldEnvValue;
        } else {
            unset($_ENV['IMAGES_ROOT']);
        }

        if ($this->hadServer) {
            $_SERVER['IMAGES_ROOT'] = $this->oldServerValue;
        } else {
            unset($_SERVER['IMAGES_ROOT']);
        }

        if ($this->oldGetEnv === false) {
            putenv('IMAGES_ROOT');
        } else {
            putenv('IMAGES_ROOT=' . $this->oldGetEnv);
        }

        parent::tearDown();
    }

    public function testImagesRootFallsBackToProjectDirectory(): void
    {
        unset($_ENV['IMAGES_ROOT'], $_SERVER['IMAGES_ROOT']);
        putenv('IMAGES_ROOT');

        $config = new Config();
        self::assertSame(
            $config->projectRoot() . '/cocktail-images',
            $config->imagesRoot()
        );
    }

    public function testImagesRootSupportsRelativePathFromEnv(): void
    {
        $_ENV['IMAGES_ROOT'] = 'custom/images';
        $_SERVER['IMAGES_ROOT'] = 'custom/images';
        putenv('IMAGES_ROOT=custom/images');

        $config = new Config();
        self::assertSame(
            $config->projectRoot() . '/custom/images',
            $config->imagesRoot()
        );
    }

    public function testImagesRootSupportsAbsolutePathFromEnv(): void
    {
        $_ENV['IMAGES_ROOT'] = '/tmp/custom-images';
        $_SERVER['IMAGES_ROOT'] = '/tmp/custom-images';
        putenv('IMAGES_ROOT=/tmp/custom-images');

        $config = new Config();
        self::assertSame('/tmp/custom-images', $config->imagesRoot());
    }

    public function testDerivedDirectoriesUseResolvedRoot(): void
    {
        $_ENV['IMAGES_ROOT'] = '/tmp/cd-images';
        $_SERVER['IMAGES_ROOT'] = '/tmp/cd-images';
        putenv('IMAGES_ROOT=/tmp/cd-images');

        $config = new Config();
        self::assertSame('/tmp/cd-images/incoming', $config->incomingDir());
        self::assertSame('/tmp/cd-images/full', $config->fullDir());
        self::assertSame('/tmp/cd-images/thumbs', $config->thumbsDir());
        self::assertSame('/tmp/cd-images/data', $config->dataDir());
        self::assertSame('/tmp/cd-images/data/info.yml', $config->infoFile());
    }

    public function testConstantsExposeStaticImageSettings(): void
    {
        self::assertSame(5, Config::IMAGE_BATCH_RESET_MINUTES);
        self::assertSame(1440, Config::IMAGE_FULL_MAX_SIZE);
        self::assertSame(82, Config::IMAGE_FULL_QUALITY);
        self::assertSame(1080, Config::IMAGE_THUMB_MAX_SIZE);
        self::assertSame(78, Config::IMAGE_THUMB_QUALITY);
        self::assertSame('bild', Config::IMAGE_FALLBACK_SLUG);
        self::assertSame(['jpg', 'jpeg', 'png', 'webp', 'gif'], Config::IMAGE_ALLOWED_EXTENSIONS);
        self::assertSame('Europe/Berlin', Config::DEFAULT_TZ);
    }
}
