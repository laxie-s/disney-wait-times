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
    </section>

    <section class="shell section-shell tight-top">
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

