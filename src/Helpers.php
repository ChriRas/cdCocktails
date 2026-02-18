<?php
declare(strict_types=1);

namespace App;

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
}
