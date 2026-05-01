<?php
include 'includes/data.php';
include 'includes/layout.php';

$planner_total_steps = 0;
foreach ($planner_guides as $guide) {
    $planner_total_steps += count($guide['steps']);
}

renderHead('Preparer sa visite', 'Parcours, checklists et conseils pour preparer une visite sur les deux parcs.', $site);
renderHeader('planner', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Preparation</p>
            <h1>Une page guide pour preparer Disneyland Park et Disney Adventure World ensemble.</h1>
            <p>
                Depuis le printemps 2026, le plus dur n est plus seulement de choisir ses attractions. C est aussi
                de choisir comment on partage son energie entre deux parcs qui n ont pas le meme rythme.
            </p>
        </div>

        <div class="metric-grid">
            <article class="metric-card"><span><?php echo e(count($planner_guides)); ?></span><p>profils de journee deja poses</p></article>
            <article class="metric-card"><span><?php echo e($planner_total_steps); ?></span><p>etapes de lecture visiteur</p></article>
            <article class="metric-card"><span><?php echo e(count($faq_items)); ?></span><p>questions pratiques relues</p></article>
            <article class="metric-card"><span>2</span><p>parcs a arbitrer dans la meme visite</p></article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav secondary" aria-label="Navigation preparation">
            <a href="#planner-parks" class="chip-link">Parcs</a>
            <a href="#planner-guides" class="chip-link">Profils de journee</a>
            <a href="#planner-faq" class="chip-link">FAQ</a>
        </nav>
    </section>

    <section class="shell section-shell tight-top" id="planner-parks">
        <div class="park-grid">
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <article class="park-card">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($parkName); ?></span>
                        <span class="pill soft-gold"><?php echo e($park_summaries[$parkName]['count']); ?> attractions</span>
                    </div>
                    <h3><?php echo e($profile['headline']); ?></h3>
                    <p><?php echo e($profile['focus']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="planner-guides">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Profils de journee</p>
                <h2>Des chemins de visite differents selon l energie et les priorites.</h2>
            </div>
            <span class="quiet-note">On lit mieux cette page comme un coach de journee que comme une checklist universelle.</span>
        </div>

        <div class="story-grid">
            <?php foreach ($planner_guides as $guide) : ?>
                <article class="story-card">
                    <span class="pill soft-blue"><?php echo e($guide['title']); ?></span>
                    <p><?php echo e($guide['audience']); ?></p>
                    <ul class="mini-list">
                        <?php foreach ($guide['steps'] as $step) : ?>
                            <li><?php echo e($step); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="planner-faq">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Questions frequentes</p>
                <h2>Les petits arbitrages qui changent une vraie visite.</h2>
            </div>
        </div>

        <div class="faq-grid">
            <?php foreach ($faq_items as $item) : ?>
                <article class="faq-card">
                    <h3><?php echo e($item['question']); ?></h3>
                    <p><?php echo e($item['answer']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
