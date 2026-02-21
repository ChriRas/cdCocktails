<?php
declare(strict_types=1);

namespace App;

use Carbon\CarbonImmutable;

class Helpers {
  public function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }

    public function isImageFilename(string $name): bool {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return $ext === 'webp';
    }

  public function basenameSafe(string $name): string {
    return basename($name);
  }

    /**
     * Renders a page with the shared layout.
     *
     * @param array<int, string> $preload
     */
    public function renderLayout(string $title, string $content, array $preload = [], string $headExtra = '', string $bodyExtra = ''): void
    {
        // Variables used by templates/layout.php
        /** @var string $title */
        /** @var array<int, string> $preload */
        /** @var string $headExtra */
        /** @var string $bodyExtra */
        /** @var string $content */

        include __DIR__ . '/../templates/layout.php';
    }

    public function renderClosed(): void
    {
        $title = 'Bar geschlossen';
        $preload = [];
        $headExtra = '';
        $bodyExtra = '';

        ob_start();
        include __DIR__ . '/../templates/closed.php';
        $content = (string)ob_get_clean();

        $this->renderLayout($title, $content, $preload, $headExtra, $bodyExtra);
    }
    /**
     * @param bool $debug
     * @param array $info
     * @param mixed $appEnv
     * @param mixed $tz
     * @param CarbonImmutable $now
     * @param TimeWindow $window
     * @return void
     */
    public function debugHelper(bool $debug, array $info, mixed $appEnv, mixed $tz, CarbonImmutable $now, TimeWindow $window): void
    {
        if ($debug) {
            header('Content-Type: text/plain; charset=utf-8');

            $startAt = ($info['startAt'] ?? null) instanceof CarbonImmutable ? $info['startAt'] : null;
            $showFrom = $startAt ? $startAt->subHours(2) : null;
            $showUntil = $startAt ? $startAt->addDay()->setTime(4, 0, 0) : null;

            echo "APP_ENV={$appEnv}\n";
            echo "TZ=" . $tz->getName() . "\n";
            echo "NOW=" . $now->toDateTimeString() . "\n";
            echo "HAS_INFO=" . (($info['hasInfo'] ?? false) ? '1' : '0') . "\n";
            echo "START_AT=" . ($startAt ? $startAt->toDateTimeString() : '(null)') . "\n";
            echo "SHOW_FROM=" . ($showFrom ? $showFrom->toDateTimeString() : '(null)') . "\n";
            echo "SHOW_UNTIL=" . ($showUntil ? $showUntil->toDateTimeString() : '(null)') . "\n";
            echo "ENFORCED=" . ($window->enforced ? '1' : '0') . "\n";
            echo "IS_OPEN=" . ($window->isOpen($now) ? '1' : '0') . "\n";
            exit;
        }
    }
}
