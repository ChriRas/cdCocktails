<?php
/** @var \App\Helpers $helpers */
/** @var array $infoBlock */
use App\Config;
?>
<header class="title-block" aria-label="Titel">
    <img src="<?= $helpers->h(Config::LOGO_PATH) ?>" alt="Logo" class="title-logo">

    <div class="title-text">
        <div class="title-kicker">Cocktailkarte</div>
        <?php if (!empty($infoBlock['party'])): ?>
            <h1><?= $helpers->h($infoBlock['party']) ?></h1>
        <?php endif; ?>
    </div>
</header>