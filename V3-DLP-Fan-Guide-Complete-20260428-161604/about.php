<?php
include 'includes/data.php';
include 'includes/layout.php';

renderHead('A propos', 'Positionnement du site fan et vision produit.', $site);
renderHeader('about', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">A propos</p>
            <h1>Un guide fan premium, pense pour aider les visiteurs sur les deux parcs.</h1>
            <p>Le site assume un positionnement clair: tres utile, tres lisible, tres fan, mais jamais officiel.</p>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <div class="feature-grid">
            <?php foreach ($brand_principles as $item) : ?>
                <article class="feature-card">
                    <h3><?php echo e($item['title']); ?></h3>
                    <p><?php echo e($item['copy']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>

