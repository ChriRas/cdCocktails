<?php
declare(strict_types=1);

namespace Tests;

use App\TimeWindow;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class TimeWindowTest extends TestCase
{
    public function testIsOpenWhenNotEnforced(): void
    {
        $window = new TimeWindow(false, null);
        $now = CarbonImmutable::parse('2026-02-22 12:00:00', 'Europe/Berlin');

        self::assertTrue($window->isOpen($now));
    }

    public function testIsOpenWhenEnforcedButStartMissing(): void
    {
        $window = new TimeWindow(true, null);
        $now = CarbonImmutable::parse('2026-02-22 12:00:00', 'Europe/Berlin');

        self::assertTrue($window->isOpen($now));
    }

    public function testIsOpenWithinConfiguredWindow(): void
    {
        $start = CarbonImmutable::parse('2026-02-22 22:00:00', 'Europe/Berlin');
        $window = new TimeWindow(true, $start);

        self::assertTrue($window->isOpen(CarbonImmutable::parse('2026-02-22 20:00:00', 'Europe/Berlin')));
        self::assertTrue($window->isOpen(CarbonImmutable::parse('2026-02-23 04:00:00', 'Europe/Berlin')));
    }

    public function testIsClosedOutsideConfiguredWindow(): void
    {
        $start = CarbonImmutable::parse('2026-02-22 22:00:00', 'Europe/Berlin');
        $window = new TimeWindow(true, $start);

        self::assertFalse($window->isOpen(CarbonImmutable::parse('2026-02-22 19:59:59', 'Europe/Berlin')));
        self::assertFalse($window->isOpen(CarbonImmutable::parse('2026-02-23 04:00:01', 'Europe/Berlin')));
    }
}
