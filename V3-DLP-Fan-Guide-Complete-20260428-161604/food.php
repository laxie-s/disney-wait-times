<?php
include 'includes/data.php';
include 'includes/layout.php';

function mealBudgetTotal($defaults, $adults, $kids, $quickMeals, $tableMeals, $signatureMeals, $snacks)
{
    $adultTotal = $adults * (
        $quickMeals * $defaults['quick_adult'] +
        $tableMeals * $defaults['table_adult'] +
        $signatureMeals * $defaults['signature_adult'] +
        $snacks * $defaults['snack']
    );

    $kidTotal = $kids * (
        $quickMeals * $defaults['quick_kid'] +
        $tableMeals * $defaults['table_kid'] +
        $signatureMeals * $defaults['signature_kid'] +
        $snacks * $defaults['snack']
    );

    return $adultTotal + $kidTotal;
}

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

$service_summaries = [];
$land_summaries = [];
foreach ($display_spots as $spot) {
    $service_key = normalizeName($spot['service']);
    if (!isset($service_summaries[$service_key])) {
        $service_summaries[$service_key] = [
            'label' => $spot['service'],
            'count' => 0,
            'vegan_count' => 0,
            'parks' => [],
            'bookings' => [],
        ];
    }
    $service_summaries[$service_key]['count']++;
    if ($spot['vegan']) {
        $service_summaries[$service_key]['vegan_count']++;
    }
    $service_summaries[$service_key]['parks'][$spot['park']] = true;
    $service_summaries[$service_key]['bookings'][$spot['booking']] = true;

    $land_key = normalizeName($spot['park'] . '-' . $spot['land']);
    if (!isset($land_summaries[$land_key])) {
        $land_summaries[$land_key] = [
            'park' => $spot['park'],
            'label' => $spot['land'],
            'count' => 0,
            'services' => [],
            'vegan_count' => 0,
        ];
    }
    $land_summaries[$land_key]['count']++;
    if ($spot['vegan']) {
        $land_summaries[$land_key]['vegan_count']++;
    }
    $land_summaries[$land_key]['services'][$spot['service']] = true;
}

$service_summaries = array_values(array_map(function ($summary) {
    $summary['parks'] = array_keys($summary['parks']);
    $summary['bookings'] = array_keys($summary['bookings']);
    return $summary;
}, $service_summaries));

$land_summaries = array_values(array_map(function ($summary) {
    $summary['services'] = array_keys($summary['services']);
    return $summary;
}, $land_summaries));

usort($service_summaries, function ($left, $right) {
    if ($left['count'] === $right['count']) {
        return strcmp($left['label'], $right['label']);
    }
    return $right['count'] <=> $left['count'];
});

usort($land_summaries, function ($left, $right) {
    if ($left['count'] === $right['count']) {
        return strcmp($left['label'], $right['label']);
    }
    return $right['count'] <=> $left['count'];
});

$budget_profiles = [
    [
        'label' => 'Pause legere',
        'copy' => 'Un repas rapide et un snack par personne pour une journee qui reste mobile.',
        'total' => mealBudgetTotal($meal_budget_defaults, 2, 2, 1, 0, 0, 1),
    ],
    [
        'label' => 'Journee mixte',
        'copy' => 'Une table le midi, un snack plus tard et peu d extras.',
        'total' => mealBudgetTotal($meal_budget_defaults, 2, 2, 0, 1, 0, 1),
    ],
    [
        'label' => 'Version premium',
        'copy' => 'Un repas signature, un snack et une vraie pause experience.',
        'total' => mealBudgetTotal($meal_budget_defaults, 2, 2, 0, 0, 1, 1),
    ],
];

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
            <h1>Choisir ses pauses repas sur les deux parcs sans perdre de temps.</h1>
            <p>
                Le bon guide resto ne t oblige pas a chercher partout. Il aide a reperer vite les adresses utiles selon
                le parc, le type de service, le regime alimentaire et le niveau de reservation.
            </p>
        </div>

        <div class="section-visual-band">
            <img src="<?php echo e(siteEditorialImage('food')); ?>" alt="Table editoriale avec vue sur le parc et assiettes de restauration">
        </div>

        <div class="metric-grid food-metric-grid">
            <article class="metric-card">
                <span><?php echo e(count($display_spots)); ?></span>
                <p>adresses suivies dans cette vue</p>
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

        <div class="page-anchor-wrap">
            <span class="meta-label">Sommaire de la page</span>
            <nav class="chip-nav secondary page-anchor-nav" aria-label="Sommaire restauration">
                <a href="#food-parks" class="chip-link">Parcs</a>
                <a href="#food-directory" class="chip-link">Services et lands</a>
                <a href="#food-filtered" class="chip-link">Adresses filtrees</a>
                <a href="#food-snacks" class="chip-link">Snacks</a>
                <a href="#food-budget" class="chip-link">Budget et reservations</a>
            </nav>
        </div>
    </section>

    <section class="shell section-shell tight-top" id="food-parks">
        <div class="park-grid">
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <?php if ($park_choisi !== null && $parkName !== $park_choisi) {
                    continue;
                } ?>
                <article class="park-card has-cover">
                    <div class="card-cover card-cover-park">
                        <img src="<?php echo e(parkEditorialImage($parkName)); ?>" alt="Visuel editorial du parc <?php echo e($parkName); ?>">
                    </div>
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

    <section class="shell section-shell" id="food-directory">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Sous-categories utiles</p>
                <h2>Commencer par le bon type de pause, puis rester dans la bonne zone.</h2>
            </div>
            <span class="quiet-note">La lecture devient plus claire quand on choisit d abord le format de repas, puis la zone dans laquelle on veut rester.</span>
        </div>

        <div class="two-column-tools food-directory-layout">
            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Par service</p>
                    <h2>Le bon format avant la bonne adresse.</h2>
                </div>
                <div class="stack-grid food-directory-grid">
                    <?php foreach ($service_summaries as $service) : ?>
                        <article class="mini-surface food-directory-card">
                            <div class="card-row">
                                <span class="pill soft-gold"><?php echo e($service['label']); ?></span>
                                <span class="pill soft-blue"><?php echo e($service['count']); ?> adresses</span>
                            </div>
                            <h3><?php echo e($service['label']); ?></h3>
                            <p><?php echo e(count($service['parks'])); ?> parc(s) couverts, avec <?php echo e($service['vegan_count']); ?> adresse(s) ou une option vegan est reperee.</p>
                            <small class="quiet-note"><?php echo e(implode(' / ', array_slice($service['bookings'], 0, 2))); ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Par land</p>
                    <h2>Rester dans la zone ou tu visites deja.</h2>
                </div>
                <div class="stack-grid food-directory-grid">
                    <?php foreach (array_slice($land_summaries, 0, 8) as $land) : ?>
                        <article class="mini-surface food-directory-card">
                            <div class="card-row">
                                <span class="pill soft-blue"><?php echo e($land['park']); ?></span>
                                <span class="pill soft-gold"><?php echo e($land['count']); ?> adresses</span>
                            </div>
                            <h3><?php echo e($land['label']); ?></h3>
                            <p><?php echo e(implode(' / ', array_slice($land['services'], 0, 3))); ?></p>
                            <small class="quiet-note"><?php echo e($land['vegan_count']); ?> option(s) vegan reperee(s) dans cette zone.</small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>

    <section class="shell section-shell tight-top" id="food-filtered">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Filtres rapides</p>
                <h2>Voir tout de suite ou manger selon le besoin du moment.</h2>
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
                <p class="quiet-note">Pour le sans gluten, cette page sert de repere rapide. Les compositions et allergenes se confirment directement avec les equipes sur place.</p>
                <p class="quiet-note">Le croisement service + land sert a construire un vrai plan repas terrain, pas seulement une liste d adresses.</p>
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
                            <span class="meta-label">Reservation</span>
                            <strong><?php echo e($spot['booking']); ?></strong>
                            <small><?php echo e($spot['support_label']); ?></small>
                        </div>
                        <div class="food-location">
                            <span class="meta-label">Zone</span>
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

    <section class="shell section-shell" id="food-snacks">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Guide des snacks</p>
                <h2>Les produits photogeniques et les reperes gourmands a surveiller.</h2>
            </div>
            <span class="quiet-note">Visuels d ambiance utilises pour illustrer la rubrique snacks.</span>
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
                        <span class="meta-label">Point de vente</span>
                        <strong><?php echo e($snack['location']); ?></strong>
                        <span><?php echo e($snack['land']); ?></span>
                        <small><?php echo e($snack['season']); ?></small>
                    </div>
                    <small class="source-note"><?php echo e($snack['source']); ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="food-budget">
        <div class="two-column-tools">
            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Tables difficiles</p>
                    <h2>Les reservations a surveiller de pres sur les deux parcs.</h2>
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

            <article class="tool-panel has-cover" data-budget-calculator data-quick-adult="<?php echo e($meal_budget_defaults['quick_adult']); ?>" data-quick-kid="<?php echo e($meal_budget_defaults['quick_kid']); ?>" data-table-adult="<?php echo e($meal_budget_defaults['table_adult']); ?>" data-table-kid="<?php echo e($meal_budget_defaults['table_kid']); ?>" data-signature-adult="<?php echo e($meal_budget_defaults['signature_adult']); ?>" data-signature-kid="<?php echo e($meal_budget_defaults['signature_kid']); ?>" data-snack-price="<?php echo e($meal_budget_defaults['snack']); ?>">
                <div class="card-cover card-cover-wide">
                    <img src="<?php echo e(siteEditorialImage('food')); ?>" alt="Visuel editorial pour la rubrique budget et restauration">
                </div>
                <div class="section-head compact-head">
                    <p class="eyebrow">Simulateur budget repas</p>
                    <h2>Une estimation simple avant de partir.</h2>
                    <p>Estimation fan construite a partir des menus affiches et de paniers moyens par type de repas. Le resultat sert a cadrer une journee, pas a figer un montant exact.</p>
                </div>
                <div class="story-grid budget-profile-grid">
                    <?php foreach ($budget_profiles as $profile) : ?>
                        <article class="story-card budget-profile-card">
                            <span class="pill soft-gold"><?php echo e($profile['label']); ?></span>
                            <h3><?php echo e(number_format($profile['total'], 0, ',', ' ')); ?> EUR</h3>
                            <p><?php echo e($profile['copy']); ?></p>
                        </article>
                    <?php endforeach; ?>
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
                    <p data-budget-breakdown>Renseigne ton rythme de repas pour obtenir une enveloppe simple et lisible.</p>
                </div>
                <div class="food-assumption-grid">
                    <article class="mini-surface">
                        <span class="meta-label">Base rapide</span>
                        <strong><?php echo e($meal_budget_defaults['quick_adult']); ?> EUR adulte / <?php echo e($meal_budget_defaults['quick_kid']); ?> EUR enfant</strong>
                    </article>
                    <article class="mini-surface">
                        <span class="meta-label">Base table</span>
                        <strong><?php echo e($meal_budget_defaults['table_adult']); ?> EUR adulte / <?php echo e($meal_budget_defaults['table_kid']); ?> EUR enfant</strong>
                    </article>
                    <article class="mini-surface">
                        <span class="meta-label">Base premium</span>
                        <strong><?php echo e($meal_budget_defaults['signature_adult']); ?> EUR adulte / <?php echo e($meal_budget_defaults['signature_kid']); ?> EUR enfant</strong>
                    </article>
                </div>
                <small class="source-note">A ajuster selon le parc, les menus du moment, les boissons, le petit-dejeuner et les extras plaisir.</small>
            </article>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
