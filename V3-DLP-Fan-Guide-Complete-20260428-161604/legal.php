<?php
include 'includes/data.php';
include 'includes/layout.php';

renderHead('Mentions et clarte fan site', 'Clarte legale et positionnement fan du projet.', $site);
renderHeader('about', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Clarte fan site</p>
            <h1>Un projet fan tres clair sur ce qu il est.</h1>
            <p>Le but est d aider les visiteurs, pas de laisser croire qu il s agit du site officiel de Disney.</p>
        </div>

        <div class="metric-grid">
            <article class="metric-card"><span>Clair</span><p>le statut du site est annonce des l entree</p></article>
            <article class="metric-card"><span>Fan</span><p>lecture passionnee mais distincte</p></article>
            <article class="metric-card"><span>Source</span><p>les infos pratiques sont reliees a des bases identifiables</p></article>
            <article class="metric-card"><span>Respect</span><p>pas de copie de marque faite pour tromper</p></article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Principes de clarte</p>
                <h2>Le cadre qui permet au site de rester ambitieux sans devenir flou.</h2>
            </div>
            <a class="text-link" href="about.php">Retour a la vision du projet</a>
        </div>

        <div class="faq-grid">
            <article class="faq-card">
                <h3>Statut</h3>
                <p><?php echo e($site['legal_notice']); ?></p>
            </article>
            <article class="faq-card">
                <h3>Marque et confusion</h3>
                <p>Le projet cherche a etre excellent et utile, sans copier les codes officiels au point de semer le doute.</p>
            </article>
            <article class="faq-card">
                <h3>Sources</h3>
                <p>Les informations pratiques, menus et travaux sont appuyes autant que possible sur des sources officielles ou clairement etiquetees comme veille fan.</p>
            </article>
            <article class="faq-card">
                <h3>Ambition</h3>
                <p>Faire un meilleur service rendu aux visiteurs et montrer une vraie comprehension du resort, en particulier depuis l arrivee de Disney Adventure World.</p>
            </article>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
