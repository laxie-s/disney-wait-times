<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

$visible_secret_tasks = array_values(array_filter($secret_hunt_tasks, function ($task) use ($park_choisi, $land_profiles) {
    return $park_choisi === null || $land_profiles[$task['land']]['park'] === $park_choisi;
}));

$visible_secret_sections = array_filter($secret_sections, function ($items, $landName) use ($park_choisi, $land_profiles) {
    return $park_choisi === null || $land_profiles[$landName]['park'] === $park_choisi;
}, ARRAY_FILTER_USE_BOTH);

$difficulty_counts = [];
foreach ($visible_secret_tasks as $task) {
    if (!isset($difficulty_counts[$task['difficulty']])) {
        $difficulty_counts[$task['difficulty']] = 0;
    }
    $difficulty_counts[$task['difficulty']]++;
}

renderHead('Secrets', 'Details caches et defis fan sur les deux parcs.', $site);
renderHeader('secrets', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Details caches et regard fan</p>
            <h1>Les secrets du resort deviennent de vrais defis a cocher.</h1>
            <p>
                Les visiteurs viennent d abord pour etre aides. Ils reviennent souvent parce qu on leur a appris
                a voir le parc autrement, puis parce qu ils veulent finir leur chasse aux details caches.
            </p>
        </div>

        <div class="metric-grid">
            <article class="metric-card"><span><?php echo e(count($visible_secret_tasks)); ?></span><p>defis secrets a cocher</p></article>
            <article class="metric-card"><span><?php echo e(count($visible_secret_sections)); ?></span><p>zones deja reliees a une lecture fan</p></article>
            <article class="metric-card"><span>2</span><p>parcs integres dans la chasse</p></article>
            <article class="metric-card"><span>Local</span><p>progression gardee sur cet appareil</p></article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="secrets.php" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="secrets.php?park=<?php echo urlencode($parkName); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <nav class="chip-nav secondary" aria-label="Navigation secrets">
            <a href="#secret-overview" class="chip-link">Vue rapide</a>
            <a href="#secret-hunt" class="chip-link">Chasse</a>
            <a href="#secret-zones" class="chip-link">Par land</a>
        </nav>
    </section>

    <section class="shell section-shell tight-top" id="secret-overview">
        <div class="feature-grid">
            <?php foreach ($difficulty_counts as $difficulty => $count) : ?>
                <article class="feature-card">
                    <span class="pill soft-gold"><?php echo e($count); ?> defis</span>
                    <h3><?php echo e($difficulty); ?></h3>
                    <p>Une facon simple de choisir si tu veux juste un clin d oeil iconique, du lore ou une vraie exploration de zone.</p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="secret-hunt">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Chasse aux tresors</p>
                <h2>Les secrets a retrouver pendant la journee.</h2>
            </div>
            <div class="progress-chip">
                <strong data-secret-complete>0</strong>
                <span>/ <?php echo e(count($visible_secret_tasks)); ?> trouves</span>
            </div>
        </div>

        <div class="secret-hunt-grid" data-secret-board>
            <?php foreach ($visible_secret_tasks as $task) : ?>
                <article class="hunt-card">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($land_profiles[$task['land']]['park']); ?></span>
                        <span class="pill soft-gold"><?php echo e($task['difficulty']); ?></span>
                    </div>
                    <h3><?php echo e($task['title']); ?></h3>
                    <p><?php echo e($task['prompt']); ?></p>
                    <label class="hunt-check">
                        <input type="checkbox" data-secret-key="<?php echo e($task['id']); ?>">
                        <span>Coche quand c est trouve</span>
                    </label>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="secret-zones">
        <div class="secret-layout">
            <?php foreach ($visible_secret_sections as $landName => $items) : ?>
                <article class="secret-group" id="<?php echo e(normalizeName($landName)); ?>">
                    <div class="card-row">
                        <span class="land-mark"><?php echo e(initials($landName)); ?></span>
                        <span class="pill soft-blue"><?php echo e($land_profiles[$landName]['park']); ?></span>
                    </div>
                    <h3><?php echo e($landName); ?></h3>
                    <div class="secret-card-grid">
                        <?php foreach ($items as $item) : ?>
                            <article class="story-card">
                                <h4><?php echo e($item['title']); ?></h4>
                                <p><?php echo e($item['copy']); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
