<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

$vegan_count = count(array_filter($dining_spots, function ($spot) use ($park_choisi) {
    return (!$park_choisi || $spot['park'] === $park_choisi) && $spot['vegan'];
}));

$gluten_support_count = count(array_filter($dining_spots, function ($spot) use ($park_choisi) {
    return (!$park_choisi || $spot['park'] === $park_choisi) && $spot['gluten_support'];
}));

$display_spots = array_values(array_filter($dining_spots, function ($spot) use ($park_choisi) {
    return !$park_choisi || $spot['park'] === $park_choisi;
}));

$display_snacks = array_values(array_filter($seasonal_snacks, function ($snack) use ($park_choisi) {
    return !$park_choisi || $snack['park'] === $park_choisi;
}));

$service_options = [];
$land_options = [];
foreach ($display_spots as $spot) {
    $service_options[normalizeName($spot['service'])] = $spot['service'];
    $land_options[normalizeName($spot['land'])] = $spot['land'];
}
asort($service_options);
asort($land_options);

$park_restaurant_counts = [];
$park_snack_counts = [];
foreach ($park_profiles as $parkName => $profile) {
    $park_restaurant_counts[$parkName] = count(array_filter($dining_spots, function ($spot) use ($parkName) {
        return $spot['park'] === $parkName;
    }));
    $park_snack_counts[$parkName] = count(array_filter($seasonal_snacks, function ($snack) use ($parkName) {
        return $snack['park'] === $parkName;
    }));
}

renderHead('Restauration', 'Guide resto fan sur Disneyland Park et Disney Adventure World.', $site);
renderHeader('food', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Restauration</p>
            <h1>Comparer les pauses repas sur les deux parcs sans perdre de temps.</h1>
            <p>
                Le bon guide resto ne t oblige pas a chercher partout. Il te donne vite les bonnes adresses selon
                le parc, le type de service, le regime alimentaire et le niveau de reservation requis.
            </p>
        </div>

        <div class="metric-grid food-metric-grid">
            <article class="metric-card">
                <span><?php echo e(count($display_spots)); ?></span>
                <p>spots suivis dans cette vue</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($vegan_count); ?></span>
                <p>adresses avec option vegan reperee</p>
            </article>
            <article class="metric-card">
                <span><?php echo e(count($display_snacks)); ?></span>
                <p>snacks suivis dans cette vue</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($gluten_support_count); ?></span>
                <p>adresses ou verifier les allergenes</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="food.php" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="food.php?park=<?php echo urlencode($parkName); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="shell section-shell tight-top">
        <div class="park-grid">
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <?php if ($park_choisi !== null && $parkName !== $park_choisi) {
                    continue;
                } ?>
                <article class="park-card">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($parkName); ?></span>
                        <span class="pill soft-gold"><?php echo e($park_restaurant_counts[$parkName]); ?> restos</span>
                    </div>
                    <h3><?php echo e($parkName); ?></h3>
                    <p><?php echo e($profile['headline']); ?></p>
                    <small class="quiet-note"><?php echo e($profile['focus']); ?></small>
                    <small class="quiet-note"><?php echo e($park_snack_counts[$parkName]); ?> snacks saisonniers ou a surveiller dans cette base.</small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Filtres rapides</p>
                <h2>Voir tout de suite ou manger selon le regime recherche.</h2>
            </div>
            <span class="quiet-note"><?php echo e($source_notes['food']); ?></span>
        </div>

        <div class="tool-panel filter-panel" data-food-filters>
            <div class="filter-panel-grid">
                <div class="filter-stack">
                    <span class="meta-label">Regime</span>
                    <div class="filter-toggle-row">
                        <button type="button" class="toggle-filter is-active" data-filter-group="diet" data-filter-value="all">Tout</button>
                        <?php foreach ($food_filters as $key => $label) : ?>
                            <button type="button" class="toggle-filter" data-filter-group="diet" data-filter-value="<?php echo e($key); ?>"><?php echo e($label); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-stack">
                    <span class="meta-label">Service</span>
                    <div class="filter-chip-row">
                        <button type="button" class="chip-link active" data-filter-group="service" data-filter-value="all">Tous</button>
                        <?php foreach ($service_options as $serviceKey => $serviceLabel) : ?>
                            <button type="button" class="chip-link" data-filter-group="service" data-filter-value="<?php echo e($serviceKey); ?>"><?php echo e($serviceLabel); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-stack">
                    <span class="meta-label">Land</span>
                    <div class="filter-chip-row">
                        <button type="button" class="chip-link active" data-filter-group="land" data-filter-value="all">Tous les lands</button>
                        <?php foreach ($land_options as $landKey => $landLabel) : ?>
                            <button type="button" class="chip-link" data-filter-group="land" data-filter-value="<?php echo e($landKey); ?>"><?php echo e($landLabel); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="filter-note-grid">
                <p class="quiet-note">Pour le sans gluten, on parle ici de reperes utiles. Les allergenes restent a verifier sur place avec les equipes.</p>
                <p class="quiet-note">Les categories service + land servent a composer un vrai plan repas terrain plus qu un simple annuaire.</p>
            </div>
        </div>

        <div class="food-grid">
            <?php foreach ($display_spots as $spot) : ?>
                <article class="food-card" data-food-card data-vegan="<?php echo $spot['vegan'] ? '1' : '0'; ?>" data-vegetarian="<?php echo $spot['vegetarian'] ? '1' : '0'; ?>" data-gluten_support="<?php echo $spot['gluten_support'] ? '1' : '0'; ?>" data-service="<?php echo e(normalizeName($spot['service'])); ?>" data-land-key="<?php echo e(normalizeName($spot['land'])); ?>">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($spot['park']); ?></span>
                        <span class="pill soft-gold"><?php echo e($spot['service']); ?></span>
                    </div>
                    <h3><?php echo e($spot['name']); ?></h3>
                    <p><?php echo e($spot['why']); ?></p>
                    <div class="tag-row compact">
                        <?php if ($spot['vegan']) : ?><span>Vegan</span><?php endif; ?>
                        <?php if ($spot['vegetarian']) : ?><span>Vegetarien</span><?php endif; ?>
                        <?php if ($spot['gluten_support']) : ?><span>Sans gluten / allergenes</span><?php endif; ?>
                    </div>
                    <div class="food-meta food-meta-split">
                        <div class="food-booking">
                            <strong><?php echo e($spot['booking']); ?></strong>
                            <small><?php echo e($spot['support_label']); ?></small>
                        </div>
                        <div class="food-location">
                            <span class="meta-label">Land</span>
                            <small><?php echo e($spot['land']); ?></small>
                        </div>
                    </div>
                    <ul class="food-item-list">
                        <?php foreach ($spot['items'] as $item) : ?>
                            <li>
                                <div class="food-item-copy">
                                    <strong><?php echo e($item['name']); ?></strong>
                                    <small><?php echo e($item['type']); ?></small>
                                </div>
                                <span class="food-price"><?php echo e($item['price']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <small class="source-note"><?php echo e($spot['source']); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Guide des snacks</p>
                <h2>Les produits photogeniques et les reperes gourmands a suivre.</h2>
            </div>
            <span class="quiet-note">Visuels fans d inspiration, a remplacer plus tard par tes propres photos terrain.</span>
        </div>

        <div class="snack-grid">
            <?php foreach ($display_snacks as $snack) : ?>
                <article class="snack-card">
                    <div class="snack-image-wrap">
                        <img src="<?php echo e($snack['image']); ?>" alt="Visuel fan du snack <?php echo e($snack['name']); ?>">
                    </div>
                    <div class="card-row snack-card-top">
                        <span class="pill soft-blue"><?php echo e($snack['park']); ?></span>
                        <span class="pill soft-green"><?php echo e($snack['price']); ?></span>
                    </div>
                    <h3><?php echo e($snack['name']); ?></h3>
                    <p><?php echo e($snack['summary']); ?></p>
                    <div class="snack-location-block">
                        <strong><?php echo e($snack['location']); ?></strong>
                        <span><?php echo e($snack['land']); ?></span>
                        <small><?php echo e($snack['season']); ?></small>
                    </div>
                    <small class="source-note"><?php echo e($snack['source']); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="two-column-tools">
            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Tables difficiles</p>
                    <h2>Des reservations a surveiller sur les deux parcs.</h2>
                </div>
                <div class="stack-grid">
                    <?php foreach ($reservation_tips as $tip) : ?>
                        <article class="mini-surface">
                            <span class="pill <?php echo $tip['tag'] === 'Officiel' ? 'soft-blue' : 'soft-gold'; ?>"><?php echo e($tip['tag']); ?></span>
                            <h3><?php echo e($tip['title']); ?></h3>
                            <p><?php echo e($tip['copy']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="tool-panel" data-budget-calculator data-quick-adult="<?php echo e($meal_budget_defaults['quick_adult']); ?>" data-quick-kid="<?php echo e($meal_budget_defaults['quick_kid']); ?>" data-table-adult="<?php echo e($meal_budget_defaults['table_adult']); ?>" data-table-kid="<?php echo e($meal_budget_defaults['table_kid']); ?>" data-signature-adult="<?php echo e($meal_budget_defaults['signature_adult']); ?>" data-signature-kid="<?php echo e($meal_budget_defaults['signature_kid']); ?>" data-snack-price="<?php echo e($meal_budget_defaults['snack']); ?>">
                <div class="section-head compact-head">
                    <p class="eyebrow">Simulateur budget repas</p>
                    <h2>Une estimation simple avant de partir.</h2>
                    <p>Base indicative pour un jour standard avec menus affiches. Le resultat ne remplace pas la carte du jour, les offres ou les extras premium.</p>
                </div>
                <div class="budget-grid">
                    <label><span>Adultes</span><input type="number" min="1" max="8" value="<?php echo e($meal_budget_defaults['adults']); ?>" data-budget-field="adults"></label>
                    <label><span>Enfants</span><input type="number" min="0" max="8" value="<?php echo e($meal_budget_defaults['kids']); ?>" data-budget-field="kids"></label>
                    <label><span>Repas rapides / pers.</span><input type="number" min="0" max="4" value="1" data-budget-field="quickMeals"></label>
                    <label><span>Repas a table / pers.</span><input type="number" min="0" max="3" value="0" data-budget-field="tableMeals"></label>
                    <label><span>Repas premium / pers.</span><input type="number" min="0" max="2" value="0" data-budget-field="signatureMeals"></label>
                    <label><span>Snacks / pers.</span><input type="number" min="0" max="6" value="1" data-budget-field="snacks"></label>
                </div>
                <div class="budget-result">
                    <div class="budget-result-head">
                        <span class="meta-label">Budget estime</span>
                        <strong data-budget-total>--</strong>
                    </div>
                    <p data-budget-breakdown>Ajoute ton rythme de repas pour obtenir une enveloppe simple.</p>
                </div>
                <small class="source-note">A ajuster selon le parc, les menus du moment, les boissons, le petit-dejeuner et les achats plaisir.</small>
            </article>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
