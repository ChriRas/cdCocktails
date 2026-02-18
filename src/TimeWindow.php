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

    $showFrom  = $this->startAt->subHours(2);
    $showUntil = $this->startAt->addDay()->setTime(4, 0, 0);

    return $now->betweenIncluded($showFrom, $showUntil);
  }
}
