<?php
declare(strict_types=1);

namespace Tests\Command;

use App\Config;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\FixedRootConfig;
use Tests\Support\TestableProcessImagesCommand;
use Tests\Support\TempDirectoryTrait;

class ProcessImagesCommandTest extends TestCase
{
    use TempDirectoryTrait;

    protected function tearDown(): void
    {
        $this->cleanupTempDirectories();
        parent::tearDown();
    }

    public function testBuildPathsResolvesUsingConfiguredRoot(): void
    {
        $root = $this->createTempDirectory('process-images');
        $this->createImageTree($root);
        $command = new TestableProcessImagesCommand(new FixedRootConfig($root));

        $paths = $command->exposedBuildPaths();
        self::assertSame($root . '/incoming', $paths['incoming']);
        self::assertSame($root . '/full', $paths['full']);
        self::assertSame($root . '/thumbs', $paths['thumbs']);
        self::assertSame($root . '/data', $paths['data']);
        self::assertSame($root . '/incoming/info.yml', $paths['infoIn']);
        self::assertSame($root . '/data/info.yml', $paths['infoOut']);
    }

    public function testSlugifyAndAllowedExtensions(): void
    {
        $command = new TestableProcessImagesCommand(new FixedRootConfig('/tmp/not-used'));

        self::assertSame('queens-park-swizzle', $command->exposedSlugify('Queen´s Park Swizzle'));
        self::assertSame(Config::IMAGE_FALLBACK_SLUG, $command->exposedSlugify('###'));
        self::assertTrue($command->exposedIsAllowedImagePath('/tmp/a.JPEG'));
        self::assertFalse($command->exposedIsAllowedImagePath('/tmp/a.txt'));
    }

    public function testExecuteProcessesBatchWithoutImagickBinary(): void
    {
        $root = $this->createTempDirectory('process-images');
        $this->createImageTree($root);

        file_put_contents($root . '/incoming/My Drink.JPG', 'raw-content');
        file_put_contents($root . '/incoming/info.yml', "party_name: Test Party\n");
        file_put_contents($root . '/full/old.webp', 'old');
        file_put_contents($root . '/thumbs/old.webp', 'old');
        file_put_contents($root . '/data/info.yml', 'old-info');
        touch($root . '/full/old.webp', time() - ((Config::IMAGE_BATCH_RESET_MINUTES * 60) + 10));
        touch($root . '/thumbs/old.webp', time() - ((Config::IMAGE_BATCH_RESET_MINUTES * 60) + 10));

        $command = new TestableProcessImagesCommand(new FixedRootConfig($root));
        $tester = new CommandTester($command);
        $statusCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $statusCode);
        self::assertFileExists($root . '/full/my-drink.webp');
        self::assertFileExists($root . '/thumbs/my-drink.webp');
        self::assertFileDoesNotExist($root . '/incoming/My Drink.JPG');
        self::assertFileExists($root . '/data/info.yml');
        self::assertFileDoesNotExist($root . '/incoming/info.yml');
        self::assertFileDoesNotExist($root . '/full/old.webp');
        self::assertFileDoesNotExist($root . '/thumbs/old.webp');
        self::assertStringContainsString('Processed 1 image(s).', $tester->getDisplay());
    }
}
