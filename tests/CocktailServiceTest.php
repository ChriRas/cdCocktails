<?php
declare(strict_types=1);

namespace Tests;

use App\CocktailService;
use App\Helpers;
use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use PHPUnit\Framework\TestCase;
use Tests\Support\FixedRootConfig;
use Tests\Support\TempDirectoryTrait;

class CocktailServiceTest extends TestCase
{
    use TempDirectoryTrait;

    protected function tearDown(): void
    {
        $this->cleanupTempDirectories();
        parent::tearDown();
    }

    public function testLoadInfoReturnsHasInfoFalseWhenFileMissing(): void
    {
        $root = $this->createTempDirectory('cocktail-service');
        $this->createImageTree($root);

        $service = new CocktailService(new Helpers(), new FixedRootConfig($root));
        $info = $service->loadInfo();

        self::assertSame(['hasInfo' => false], $info);
    }

    public function testLoadInfoParsesYamlData(): void
    {
        $root = $this->createTempDirectory('cocktail-service');
        $this->createImageTree($root);
        file_put_contents(
            $root . '/data/info.yml',
            "party_name: Summer Party\ntimezone: Europe/Berlin\nstart: '2026-02-22 21:30:00'\n"
        );

        $service = new CocktailService(new Helpers(), new FixedRootConfig($root));
        $info = $service->loadInfo();

        self::assertTrue($info['hasInfo']);
        self::assertSame('Summer Party', $info['party_name']);
        self::assertInstanceOf(CarbonImmutable::class, $info['startAt']);
        self::assertInstanceOf(CarbonTimeZone::class, $info['tz']);
        self::assertSame('Europe/Berlin', $info['tz']->getName());
    }

    public function testLoadInfoReturnsHasInfoFalseOnInvalidYaml(): void
    {
        $root = $this->createTempDirectory('cocktail-service');
        $this->createImageTree($root);
        file_put_contents($root . '/data/info.yml', "party_name: [broken\n");

        $service = new CocktailService(new Helpers(), new FixedRootConfig($root));
        $info = $service->loadInfo();

        self::assertSame(['hasInfo' => false], $info);
    }

    public function testListProcessedBaseNamesFiltersAndSorts(): void
    {
        $root = $this->createTempDirectory('cocktail-service');
        $this->createImageTree($root);
        file_put_contents($root . '/full/b.webp', 'b');
        file_put_contents($root . '/full/A.webp', 'a');
        file_put_contents($root . '/full/ignore.jpg', 'x');

        $service = new CocktailService(new Helpers(), new FixedRootConfig($root));
        $names = $service->listProcessedBaseNames();

        self::assertSame(['A.webp', 'b.webp'], $names);
    }

    public function testResolveAndBuildGalleryItemsUseOnlyCompletePairs(): void
    {
        $root = $this->createTempDirectory('cocktail-service');
        $this->createImageTree($root);
        file_put_contents($root . '/full/drink-a.webp', 'not-an-image');
        file_put_contents($root . '/thumbs/drink-a.webp', 'not-an-image');
        file_put_contents($root . '/full/drink-b.webp', 'not-an-image');

        $service = new CocktailService(new Helpers(), new FixedRootConfig($root));

        self::assertSame($root . '/full/drink-a.webp', $service->resolveFull('../drink-a.webp'));
        self::assertSame($root . '/thumbs/drink-a.webp', $service->resolveThumb('drink-a.webp'));
        self::assertNull($service->resolveFull('drink-a.jpg'));

        $items = $service->buildGalleryItems();
        self::assertCount(1, $items);
        self::assertSame('/image.php?t=thumb&f=drink-a.webp', $items[0]['thumb']);
        self::assertSame('/image.php?t=full&f=drink-a.webp', $items[0]['full']);
        self::assertSame(1440, $items[0]['w']);
        self::assertSame(960, $items[0]['h']);
        self::assertSame('drink-a.webp', $items[0]['alt']);
    }

    public function testGetImageDimensionsFallsBackForMissingFile(): void
    {
        $root = $this->createTempDirectory('cocktail-service');
        $this->createImageTree($root);
        $service = new CocktailService(new Helpers(), new FixedRootConfig($root));

        [$w, $h] = $service->getImageDimensions($root . '/full/missing.webp', 300, 200);
        self::assertSame(300, $w);
        self::assertSame(200, $h);
    }
}
