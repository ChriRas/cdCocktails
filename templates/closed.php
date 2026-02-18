<?php
/** @var \App\Helpers $helpers */
use App\Config;
?>
<main class="closed-wrap" aria-label="Bar geschlossen">
  <div class="closed-inner">
    <img src="<?= $helpers->h(Config::LOGO_PATH) ?>" alt="Logo">
    <h1>Heute ist die Bar geschlossen</h1>
  </div>
</main>
