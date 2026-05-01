<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

$visible_catalogue_by_park = $park_choisi !== null
    ? [$park_choisi => $catalogue_by_park[$park_choisi]]
    : $catalogue_by_park;

$visible_total_attractions = 0;
foreach ($visible_catalogue_by_park as $items) {
    $visible_total_attractions += count($items);
}

$visible_secret_task_count = count(array_filter($secret_hunt_tasks, function ($task) use ($park_choisi, $land_profiles) {
    return $park_choisi === null || $land_profiles[$task['land']]['park'] === $park_choisi;
}));

$visible_seed_ratings = array_values(array_filter($community_seed_ratings, function ($item) use ($park_choisi) {
    return $park_choisi === null || $item['zone'] === $park_choisi;
}));

$rating_attractions_count = count(array_filter($visible_seed_ratings, function ($item) {
    return $item['type'] === 'Attraction';
}));
$rating_restaurants_count = count(array_filter($visible_seed_ratings, function ($item) {
    return $item['type'] === 'Restaurant';
}));

renderHead('Communaute', 'Checklists locales, notes des fans et top 10 sur les deux parcs.', $site);
renderHeader('community', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Communaute et gamification</p>
            <h1>Cocher, noter et revenir, maintenant sur tout le resort.</h1>
            <p>
                La partie communaute prend plus de sens avec deux parcs. On peut suivre sa journee, noter des attractions
                de styles tres differents et garder une progression locale sans compte utilisateur.
            </p>
        </div>

        <div class="metric-grid">
            <article class="metric-card">
                <span><?php echo e($visible_total_attractions); ?></span>
                <p>attractions disponibles dans cette vue</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($visible_secret_task_count); ?></span>
                <p>defis secrets lies a cette vue</p>
            </article>
            <article class="metric-card">
                <span><?php echo e(count($visible_seed_ratings)); ?></span>
                <p>elements notes dans cette vue</p>
            </article>
            <article class="metric-card">
                <span>Local</span>
                <p>tes donnees restent sur cet appareil</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="community.php" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="community.php?park=<?php echo urlencode($parkName); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <nav class="chip-nav secondary" aria-label="Navigation communaute">
            <a href="#community-overview" class="chip-link">Vue rapide</a>
            <a href="#community-checklist" class="chip-link">Checklist</a>
            <a href="#community-ratings" class="chip-link">Notes</a>
            <a href="#community-top" class="chip-link">Top local</a>
        </nav>
    </section>

    <section class="shell section-shell tight-top" id="community-overview">
        <div class="feature-grid">
            <article class="feature-card">
                <span class="pill soft-blue">Checklist</span>
                <h3>Suivre sa journee sans compte</h3>
                <p>Le coeur utile ici, c est de cocher ce qui est deja fait sans se forcer a creer un profil.</p>
            </article>
            <article class="feature-card">
                <span class="pill soft-gold"><?php echo e($rating_attractions_count); ?> attractions</span>
                <h3>Noter ce qui vaut vraiment le detour</h3>
                <p>Les notes fans servent a faire emergir des vraies preferences terrain, pas juste les noms les plus connus.</p>
            </article>
            <article class="feature-card">
                <span class="pill soft-green"><?php echo e($rating_restaurants_count); ?> restos</span>
                <h3>Comparer aussi les pauses repas</h3>
                <p>Le top local ne doit pas oublier les restos qui font gagner du temps ou qui sauvent une grosse journee.</p>
            </article>
            <article class="feature-card">
                <span class="pill soft-rose"><?php echo e($visible_secret_task_count); ?> secrets</span>
                <h3>Boucler avec la chasse aux details</h3>
                <p>Le bon retour visiteur, c est de passer d une progression pratique a une progression fan plus ludique.</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell" id="community-checklist">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Checklist du jour</p>
                <h2>Cocher les attractions faites sur les deux parcs.</h2>
            </div>
            <div class="progress-chip">
                <strong data-checklist-complete>0</strong>
                <span>/ <?php echo e($visible_total_attractions); ?> faites</span>
            </div>
        </div>

        <div class="checklist-board" data-checklist-board>
            <?php foreach ($visible_catalogue_by_park as $parkName => $items) : ?>
                <article class="checklist-group">
                    <div class="card-row">
                        <span class="land-mark small"><?php echo e(initials($parkName)); ?></span>
                        <span class="pill soft-blue"><?php echo e($parkName); ?></span>
                    </div>
                    <h3><?php echo e($parkName); ?></h3>

                    <div class="checklist-list">
                        <?php foreach ($items as $item) : ?>
                            <?php $checkKey = normalizeName($parkName . '-' . $item['name']); ?>
                            <label class="checklist-item">
                                <input type="checkbox" data-checklist-key="<?php echo e($checkKey); ?>">
                                <span><?php echo e($item['name']); ?></span>
                                <small><?php echo e($item['land']); ?></small>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="community-ratings">
        <div class="two-column-tools">
            <article class="tool-panel" data-rating-board>
                <div class="section-head compact-head">
                    <p class="eyebrow">Notes des fans</p>
                    <h2>Donner une note sur 5 et faire vivre ton propre top resort.</h2>
                </div>

                <div class="rating-grid">
                    <?php foreach ($visible_seed_ratings as $item) : ?>
                        <article class="rating-card" data-rating-card data-rating-id="<?php echo e($item['id']); ?>" data-base-rating="<?php echo e($item['rating']); ?>" data-base-votes="<?php echo e($item['votes']); ?>" data-name="<?php echo e($item['name']); ?>" data-type="<?php echo e($item['type']); ?>" data-zone="<?php echo e($item['zone']); ?>">
                            <div class="card-row">
                                <span class="pill soft-blue"><?php echo e($item['type']); ?></span>
                                <span class="pill soft-gold"><?php echo e($item['zone']); ?></span>
                            </div>
                            <h3><?php echo e($item['name']); ?></h3>
                            <div class="rating-stars" role="group" aria-label="Noter <?php echo e($item['name']); ?>">
                                <?php for ($star = 1; $star <= 5; $star++) : ?>
                                    <button type="button" class="star-button" data-rating-value="<?php echo e($star); ?>" aria-label="<?php echo e($star); ?> sur 5">&#9733;</button>
                                <?php endfor; ?>
                            </div>
                            <div class="rating-meta">
                                <strong data-rating-average><?php echo e(number_format($item['rating'], 1)); ?>/5</strong>
                                <small data-rating-votes><?php echo e($item['votes']); ?> avis fan</small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="tool-panel" id="community-top">
                <div class="section-head compact-head">
                    <p class="eyebrow">Top 10 local</p>
                    <h2>Le classement qui bouge avec tes notes.</h2>
                </div>
                <ol class="leaderboard" data-rating-leaderboard></ol>
                <div class="mini-surface">
                    <span class="pill soft-green">Astuce</span>
                    <h3>Enchaine avec les secrets</h3>
                    <p>Les checklists d attractions et les defis secrets se combinent tres bien quand on couvre les deux parcs.</p>
                    <a class="text-link" href="secrets.php<?php echo $park_choisi ? '?park=' . urlencode($park_choisi) : ''; ?>">Ouvrir les defis secrets</a>
                </div>
            </article>
        </div>
        <script type="application/json" id="ratings-dataset"><?php echo json_encode($visible_seed_ratings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
