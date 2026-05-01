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
            <p>Le site assume une ligne claire : tres utile, tres lisible, tres fan, mais jamais presente comme officielle.</p>
        </div>

        <div class="metric-grid">
            <article class="metric-card"><span>Fan</span><p>identite claire et assumee</p></article>
            <article class="metric-card"><span>2</span><p>parcs lus dans un meme produit</p></article>
            <article class="metric-card"><span>Pratique</span><p>priorite au service rendu visiteur</p></article>
            <article class="metric-card"><span>Distinct</span><p>jamais presente comme officielle</p></article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav secondary" aria-label="Navigation a propos">
            <a href="#about-principles" class="chip-link">Principes</a>
            <a href="#about-clarity" class="chip-link">Clarte fan site</a>
        </nav>
    </section>

    <section class="shell section-shell tight-top" id="about-principles">
        <div class="feature-grid">
            <?php foreach ($brand_principles as $item) : ?>
                <article class="feature-card">
                    <h3><?php echo e($item['title']); ?></h3>
                    <p><?php echo e($item['copy']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="about-clarity">
        <div class="two-column-tools">
            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Pourquoi cette ligne compte</p>
                    <h2>Etre tres bon sans brouiller l identite du site.</h2>
                    <p>L objectif n est pas d imiter le site officiel au point de semer le doute. L objectif est d etre meilleur sur l aide concrete, les reperes terrain et la lecture fan.</p>
                </div>
            </article>
            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Clarte legale</p>
                    <h2>Garder une base propre, claire et defendable.</h2>
                    <p>La page de clarte fan site sert a rappeler ce positionnement quand le projet grandit et quand l experience devient plus ambitieuse.</p>
                    <div class="hero-actions">
                        <a class="btn btn-secondary" href="legal.php">Lire la clarte fan site</a>
                    </div>
                </div>
            </article>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
