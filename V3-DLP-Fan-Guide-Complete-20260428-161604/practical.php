<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

$visible_practical_sections = [];
foreach ($practical_sections as $title => $items) {
    $filtered_items = array_values(array_filter($items, function ($item) use ($park_choisi) {
        return $park_choisi === null || $item['zone'] === $park_choisi;
    }));
    if (!empty($filtered_items)) {
        $visible_practical_sections[$title] = $filtered_items;
    }
}

$practical_count = 0;
foreach ($visible_practical_sections as $items) {
    $practical_count += count($items);
}

$recharge_count = count($visible_practical_sections['Recharge'] ?? []);
$shade_count = count($visible_practical_sections['Ombre et pluie'] ?? []);
$water_count = count($visible_practical_sections['Fontaines a eau'] ?? []);

renderHead('Infos sur place', 'Recharge, ombre, pluie et fontaines sur les deux parcs.', $site);
renderHeader('practical', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Infos in-park</p>
            <h1>Les petits details qui sauvent une journee sur Disneyland Park comme sur Disney Adventure World.</h1>
            <p>
                Quand il pleut, qu il fait chaud ou qu un telephone tombe a 6 %, les visiteurs ne veulent pas une grande page officielle.
                Ils veulent un repere simple, tout de suite, et valable sur les deux parcs.
            </p>
        </div>
        <div class="metric-grid">
            <article class="metric-card"><span><?php echo e($practical_count); ?></span><p>reperes fan de terrain</p></article>
            <article class="metric-card"><span><?php echo e($recharge_count); ?></span><p>points a tenter pour une recharge</p></article>
            <article class="metric-card"><span><?php echo e($shade_count); ?></span><p>zones de respiration en chaleur ou pluie</p></article>
            <article class="metric-card"><span><?php echo e($water_count); ?></span><p>reperes a verifier pour les gourdes</p></article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="practical.php" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="practical.php?park=<?php echo urlencode($parkName); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="shell section-shell tight-top">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Guide terrain</p>
                <h2>Des cartes simples, pensees pour le moment de besoin.</h2>
            </div>
            <span class="quiet-note"><?php echo e($source_notes['practical']); ?></span>
        </div>

        <div class="practical-grid">
            <?php foreach ($visible_practical_sections as $title => $items) : ?>
                <article class="practical-column">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($title); ?></span>
                        <span class="pill soft-gold"><?php echo e(count($items)); ?> reperes</span>
                    </div>
                    <h3><?php echo e($title); ?></h3>
                    <div class="stack-grid">
                        <?php foreach ($items as $item) : ?>
                            <article class="mini-surface">
                                <span class="pill soft-green"><?php echo e($item['zone']); ?></span>
                                <h4><?php echo e($item['name']); ?></h4>
                                <p><?php echo e($item['detail']); ?></p>
                                <small class="quiet-note"><?php echo e($item['note']); ?></small>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
