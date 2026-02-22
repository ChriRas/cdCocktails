<?php
declare(strict_types=1);

namespace Tests;

use App\Helpers;
use App\TimeWindow;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testHtmlEscapeUsesUtf8SafeEncoding(): void
    {
        $helpers = new Helpers();

        self::assertSame('&lt;b&gt;Tom &amp; Jerry&lt;/b&gt;', $helpers->h('<b>Tom & Jerry</b>'));
    }

    public function testImageFilenameValidationAcceptsOnlyWebp(): void
    {
        $helpers = new Helpers();

        self::assertTrue($helpers->isImageFilename('drink.WEBP'));
        self::assertFalse($helpers->isImageFilename('drink.jpg'));
    }

    public function testBasenameSafeRemovesDirectoryTraversalPrefix(): void
    {
        $helpers = new Helpers();

        self::assertSame('file.webp', $helpers->basenameSafe('../unsafe/path/file.webp'));
    }

    public function testRenderLayoutOutputsHtmlWithEscapedTitle(): void
    {
        $helpers = new Helpers();

        ob_start();
        $helpers->renderLayout('<Cocktails>', '<main>Body</main>', ['/assets/one.webp']);
        $output = (string)ob_get_clean();

        self::assertStringContainsString('<title>&lt;Cocktails&gt;</title>', $output);
        self::assertStringContainsString('<main>Body</main>', $output);
        self::assertStringContainsString('rel="preload" as="image" href="/assets/one.webp"', $output);
    }

    public function testRenderClosedOutputsClosedMessage(): void
    {
        $helpers = new Helpers();

        ob_start();
        $helpers->renderClosed();
        $output = (string)ob_get_clean();

        self::assertStringContainsString('Heute ist die Bar geschlossen', $output);
    }

    public function testDebugHelperDoesNothingWhenDebugDisabled(): void
    {
        $helpers = new Helpers();
        $now = CarbonImmutable::parse('2026-02-22 12:00:00', 'Europe/Berlin');
        $window = new TimeWindow(true, $now);

        ob_start();
        $helpers->debugHelper(false, ['hasInfo' => true, 'startAt' => $now], 'dev', $now->getTimezone(), $now, $window);
        $output = (string)ob_get_clean();

        self::assertSame('', $output);
    }
}
