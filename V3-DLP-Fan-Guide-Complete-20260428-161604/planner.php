<?php
include 'includes/data.php';
include 'includes/layout.php';

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
    </section>

    <section class="shell section-shell tight-top">
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

    <section class="shell section-shell tight-top">
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

    <section class="shell section-shell">
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
