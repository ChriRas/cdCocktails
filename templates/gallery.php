<?php
/** @var \App\Helpers $helpers */
/** @var array $infoBlock */
/** @var array $items */
?>
<main class="page">
    <?php if (!empty($infoBlock)): ?>
        <?php include __DIR__ . '/title_block.php'; ?>
    <?php endif; ?>

    <section class="edge-gallery" id="gallery">
        <?php foreach ($items as $it): ?>
            <a class="thumb-card"
               href="<?= $helpers->h($it['full']) ?>"
               data-pswp-width="<?= (int)$it['w'] ?>"
               data-pswp-height="<?= (int)$it['h'] ?>"
               data-cropped="true"
               target="_blank"
               rel="noreferrer">
                <img src="<?= $helpers->h($it['thumb']) ?>"
                     alt="<?= $helpers->h($it['alt']) ?>"
                     loading="lazy"
                     decoding="async">
            </a>
        <?php endforeach; ?>
    </section>
</main>
