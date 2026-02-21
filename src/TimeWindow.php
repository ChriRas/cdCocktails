<?php
declare(strict_types=1);

namespace App;

use Carbon\CarbonImmutable;

class TimeWindow {
  public function __construct(
    public bool $enforced,
    public ?CarbonImmutable $startAt
  ) {}

  public function isOpen(CarbonImmutable $now): bool {
    if (!$this->enforced || !$this->startAt) {
      return true;
    }

    // Show 2 hours before start until 04:00 next day
    $showFrom  = $this->startAt->subHours(2);
    $showUntil = $this->startAt->addDay()->setTime(4, 0, 0);

    // Compatibility-safe comparison (avoid depending on betweenIncluded())
    return $now->greaterThanOrEqualTo($showFrom) && $now->lessThanOrEqualTo($showUntil);
  }
}
