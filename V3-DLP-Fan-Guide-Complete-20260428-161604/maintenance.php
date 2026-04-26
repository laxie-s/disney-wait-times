<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

$visible_current_closures = array_values(array_filter($current_closures, function ($item) use ($park_choisi) {
    return $park_choisi === null || $item['park'] === $park_choisi;
}));

$visible_watchlist = array_values(array_filter($maintenance_watchlist, function ($item) use ($park_choisi) {
    return $park_choisi === null || $item['park'] === 'Resort' || $item['park'] === $park_choisi;
}));

renderHead('Fermetures et rehabilitations', 'Vue claire des fermetures du jour et des transformations a surveiller.', $site);
renderHeader('maintenance', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Maintenance et travaux</p>
            <h1>Voir ce qui est ferme aujourd hui et ce qui bouge encore sur la destination.</h1>
            <p>
                Avec Disney Adventure World relance depuis le 29 mars 2026, la lecture des fermetures et des zones en transformation
                devient encore plus utile pour preparer une visite.
            </p>
        </div>
        <div class="metric-grid">
            <article class="metric-card"><span><?php echo e(count($visible_current_closures)); ?></span><p>fermetures remontees dans cette vue</p></article>
            <article class="metric-card"><span><?php echo e(count($visible_watchlist)); ?></span><p>points prospectifs suivis</p></article>
            <article class="metric-card"><span><?php echo e($park_choisi ? '1' : '2'); ?></span><p>parcs a surveiller</p></article>
            <article class="metric-card"><span><?php echo e($sync_short_label); ?></span><p>etat de la base live</p></article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="maintenance.php" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="maintenance.php?park=<?php echo urlencode($parkName); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="shell section-shell tight-top">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Aujourd hui</p>
                <h2>Fermetures et elements a verifier dans le flux du moment.</h2>
            </div>
            <span class="quiet-note"><?php echo e($source_notes['maintenance']); ?></span>
        </div>
        <?php if (!empty($visible_current_closures)) : ?>
            <div class="attraction-grid">
                <?php foreach ($visible_current_closures as $item) : ?>
                    <article class="attraction-card status-closed">
                        <div class="card-row">
                            <span class="land-mark small"><?php echo e(initials($item['name'])); ?></span>
                            <span class="pill soft-rose">Fermee</span>
                        </div>
                        <div class="attraction-top">
                            <div>
                                <h3><?php echo e($item['name']); ?></h3>
                                <p><?php echo e($item['summary']); ?></p>
                            </div>
                            <div class="wait-box"><strong>--</strong><span>FERME</span></div>
                        </div>
                        <div class="attraction-foot">
                            <span class="pill soft-blue"><?php echo e($item['park']); ?></span>
                            <small><?php echo e($item['land']); ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <article class="empty-card">
                <h3>Aucune fermeture claire dans ce filtre</h3>
                <p>Repasse en vue resort pour revoir l ensemble des fermetures remontees par le flux du jour.</p>
            </article>
        <?php endif; ?>
    </section>

    <section class="shell section-shell">
        <div class="section-head">
            <p class="eyebrow">Calendrier prospectif</p>
            <h2>Les grands points de veille sur les prochains mois.</h2>
        </div>
        <?php if (!empty($visible_watchlist)) : ?>
            <div class="maintenance-grid">
                <?php foreach ($visible_watchlist as $item) : ?>
                    <article class="maintenance-card">
                        <div class="card-row">
                            <span class="pill <?php echo $item['status'] === 'Officiel' ? 'soft-blue' : 'soft-gold'; ?>"><?php echo e($item['status']); ?></span>
                            <span class="pill soft-green"><?php echo e($item['window']); ?></span>
                            <span class="pill soft-gold"><?php echo e($item['park']); ?></span>
                        </div>
                        <h3><?php echo e($item['title']); ?></h3>
                        <p><?php echo e($item['impact']); ?></p>
                        <div class="story-meta">
                            <span><?php echo e($item['source_label']); ?></span>
                            <?php if ($item['source_url'] !== '') : ?><a href="<?php echo e($item['source_url']); ?>">Source</a><?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <article class="empty-card">
                <h3>Pas de veille specifique dans ce filtre</h3>
                <p>La vue resort garde l ensemble des transformations et points officiels que l on suit.</p>
            </article>
        <?php endif; ?>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
