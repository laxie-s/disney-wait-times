<?php
include 'includes/data.php';
include 'includes/layout.php';

function waitProfileForItem($item, $historyProfiles, $defaultProfiles)
{
    $id = attractionStorageKey($item['park'], $item['name']);
    if (isset($historyProfiles[$id])) {
        return $historyProfiles[$id];
    }

    $fallback = waitAlertProfileForAttraction($item['name'], $defaultProfiles);

    return [
        'id' => $id,
        'name' => $item['name'],
        'park' => $item['park'],
        'samples' => 0,
        'days' => 0,
        'avg_wait' => null,
        'min_wait' => null,
        'max_wait' => null,
        'target_wait' => $fallback['target'],
        'great_wait' => $fallback['great'],
        'typical_wait' => $fallback['typical'],
        'hourly' => [],
    ];
}

function hourlyProfileForNow($profile, $hour)
{
    return $profile['hourly'][$hour] ?? null;
}

function waitOpportunityScore($item, $profile)
{
    if ($item['status'] !== 'open' || !is_numeric($item['wait_time'])) {
        return 999999;
    }

    $target = max(5, (int) ($profile['target_wait'] ?? 15));
    $wait = (int) $item['wait_time'];

    return (int) round(($wait / $target) * 1000) + $wait;
}

function waitStoryCopy($item, $profile, $hour)
{
    $hourly = hourlyProfileForNow($profile, $hour);
    $target = (int) ($profile['target_wait'] ?? 15);
    $great = (int) ($profile['great_wait'] ?? 10);
    $typical = $hourly['avg'] ?? ($profile['avg_wait'] ?? $profile['typical_wait'] ?? null);

    if ($item['status'] === 'open' && is_numeric($item['wait_time'])) {
        $wait = (int) $item['wait_time'];

        if ($wait <= $great) {
            return 'Tres bon slot maintenant. Tu es sous la fenetre fan forte pour cette attraction.';
        }

        if ($wait <= $target) {
            return 'Sous le seuil fan vise. Si c est une priorite du jour, c est un vrai bon timing.';
        }

        if ($typical !== null && $wait <= $typical) {
            return 'Pas miraculeux, mais deja plus doux que la norme de cette attraction.';
        }

        return 'Ca reste au-dessus de la fenetre cible. Garde-la en surveillance si c est une priorite absolue.';
    }

    if ($item['status'] === 'closed') {
        return 'Fermee dans le flux live. Tu peux activer une alerte sortie de panne pour la reprendre vite si elle revient.';
    }

    if ($hourly !== null) {
        return 'Pas de retour live pour le moment, mais cette attraction tourne souvent autour de ' . $hourly['avg'] . ' min vers ' . sprintf('%02dh', $hour) . '.';
    }

    if ($typical !== null) {
        return 'Pas de retour live pour le moment. Sa lecture historique tourne plutot autour de ' . $typical . ' min.';
    }

    return 'Pas de retour live pour le moment. Garde surtout cette carte comme repere de zone et de statut.';
}

function waitInfoLine($item, $profile, $hour)
{
    $parts = [];
    $hourly = hourlyProfileForNow($profile, $hour);

    $parts[] = 'Seuil fan ' . (int) ($profile['target_wait'] ?? 15) . ' min';

    if ($hourly !== null) {
        $parts[] = 'Habituel vers ' . sprintf('%02dh', $hour) . ': ' . $hourly['avg'] . ' min';
    } elseif (!empty($profile['avg_wait'])) {
        $parts[] = 'Moyenne observee: ' . $profile['avg_wait'] . ' min';
    }

    if (!empty($profile['days'])) {
        $parts[] = $profile['days'] . ' j de recul';
    }

    return implode(' - ', $parts);
}

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($catalogue_by_park[$park_choisi])) {
    $park_choisi = null;
}

$land_choisi = $_GET['land'] ?? null;
if ($land_choisi !== null && !isset($catalogue_by_land[$land_choisi])) {
    $land_choisi = null;
}

if ($park_choisi !== null && $land_choisi !== null && $land_summaries[$land_choisi]['park'] !== $park_choisi) {
    $land_choisi = null;
}

$view_mode = $_GET['view'] ?? 'all';
$view_labels = [
    'all' => 'Tout voir',
    'open' => 'Ouvertes',
    'closed' => 'Fermees / a verifier',
];

if (!isset($view_labels[$view_mode])) {
    $view_mode = 'all';
}

$current_hour = (int) date('G');
$history_days = 0;
foreach ($wait_history_records as $record) {
    if (!empty($record['date'])) {
        $history_days++;
    }
}
$history_days = $history_days > 0 ? count(array_unique(array_column($wait_history_records, 'date'))) : 0;

$alert_catalogue = [];
foreach ($flat_catalogue as $item) {
    $profile = waitProfileForItem($item, $wait_history_profiles, $wait_alert_profiles);
    $hourly = hourlyProfileForNow($profile, $current_hour);

    $alert_catalogue[] = [
        'id' => attractionStorageKey($item['park'], $item['name']),
        'name' => $item['name'],
        'park' => $item['park'],
        'land' => $item['land'],
        'status' => $item['status'],
        'wait_time' => is_numeric($item['wait_time']) ? (int) $item['wait_time'] : null,
        'target_wait' => (int) ($profile['target_wait'] ?? 15),
        'great_wait' => (int) ($profile['great_wait'] ?? 10),
        'typical_wait' => $hourly['avg'] ?? ($profile['avg_wait'] ?? $profile['typical_wait'] ?? null),
        'avg_wait' => $profile['avg_wait'] ?? null,
        'days' => (int) ($profile['days'] ?? 0),
        'samples' => (int) ($profile['samples'] ?? 0),
        'trend_wait' => $hourly['avg'] ?? null,
        'story' => waitStoryCopy($item, $profile, $current_hour),
    ];
}

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'generated_at' => date('c'),
        'sync_label' => $sync_short_label,
        'live' => $live_is_available,
        'items' => $alert_catalogue,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($land_choisi !== null) {
    $summary_open_count = $land_summaries[$land_choisi]['open_count'];
    $summary_closed_count = $land_summaries[$land_choisi]['closed_count'];
    $summary_untracked_count = $land_summaries[$land_choisi]['untracked_count'];
    $summary_avg_wait = $land_summaries[$land_choisi]['avg_wait'];
} elseif ($park_choisi !== null) {
    $summary_open_count = $park_summaries[$park_choisi]['open_count'];
    $summary_closed_count = $park_summaries[$park_choisi]['closed_count'];
    $summary_untracked_count = $park_summaries[$park_choisi]['untracked_count'];
    $summary_avg_wait = $park_summaries[$park_choisi]['avg_wait'];
} else {
    $summary_open_count = $open_count;
    $summary_closed_count = $closed_count;
    $summary_untracked_count = $untracked_count;
    $summary_avg_wait = $avg_wait;
}

$display_attractions = [];
foreach ($flat_catalogue as $item) {
    if ($park_choisi !== null && $item['park'] !== $park_choisi) {
        continue;
    }
    if ($land_choisi !== null && $item['land'] !== $land_choisi) {
        continue;
    }
    if ($view_mode === 'open' && $item['status'] !== 'open') {
        continue;
    }
    if ($view_mode === 'closed' && $item['status'] === 'open') {
        continue;
    }
    $display_attractions[] = $item;
}

usort($display_attractions, function ($left, $right) use ($wait_history_profiles, $wait_alert_profiles) {
    $statusOrder = ['open' => 0, 'closed' => 1, 'untracked' => 2, 'offline' => 3];
    $leftStatus = $statusOrder[$left['status']] ?? 9;
    $rightStatus = $statusOrder[$right['status']] ?? 9;

    if ($leftStatus !== $rightStatus) {
        return $leftStatus <=> $rightStatus;
    }

    $leftProfile = waitProfileForItem($left, $wait_history_profiles, $wait_alert_profiles);
    $rightProfile = waitProfileForItem($right, $wait_history_profiles, $wait_alert_profiles);

    if ($left['status'] === 'open' && $right['status'] === 'open') {
        $leftScore = waitOpportunityScore($left, $leftProfile);
        $rightScore = waitOpportunityScore($right, $rightProfile);
        if ($leftScore !== $rightScore) {
            return $leftScore <=> $rightScore;
        }
    }

    return strcmp($left['name'], $right['name']);
});

$focus_cards = [];
foreach ($display_attractions as $item) {
    if ($item['status'] === 'open') {
        $focus_cards[] = $item;
    }
    if (count($focus_cards) === 4) {
        break;
    }
}

$watch_cards = array_values(array_filter($alert_catalogue, function ($item) {
    return in_array($item['name'], ['Frozen Ever After', "Crush's Coaster", 'Avengers Assemble: Flight Force', 'Spider-Man W.E.B. Adventure', 'The Twilight Zone Tower of Terror', 'Orbitron'], true);
}));

usort($watch_cards, function ($left, $right) {
    $leftTypical = $left['typical_wait'] ?? 0;
    $rightTypical = $right['typical_wait'] ?? 0;
    if ($leftTypical === $rightTypical) {
        return strcmp($left['name'], $right['name']);
    }
    return $rightTypical <=> $leftTypical;
});

$watch_cards = array_slice($watch_cards, 0, 6);
$orbitron_info = null;
foreach ($alert_catalogue as $item) {
    if ($item['name'] === 'Orbitron') {
        $orbitron_info = $item;
        break;
    }
}

$alert_dataset = [
    'endpoint' => 'wait-times.php?format=json',
    'items' => $alert_catalogue,
];

if ($land_choisi !== null) {
    $page_title = 'Temps d attente - ' . $land_choisi;
    $page_description = 'Lecture detaillee de ' . $land_choisi . ' avec live, seuils d alerte et fiches fan.';
} elseif ($park_choisi !== null) {
    $page_title = 'Temps d attente - ' . $park_choisi;
    $page_description = 'Vue live du parc choisi avec filtres, seuils perso et alertes sur les meilleures fenetres.';
} else {
    $page_title = 'Temps d attente';
    $page_description = 'Vue globale du resort pour reperer rapidement les meilleures opportunites sur les deux parcs.';
}

renderHead($page_title, $page_description, $site);
renderHeader('wait-times', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero compact-hero">
        <div class="section-head">
            <p class="eyebrow"><?php echo $live_is_available ? 'Donnees live resort synchronisees' : 'Mode de secours'; ?></p>
            <h1><?php echo e($page_title); ?></h1>
            <p><?php echo e($page_description); ?></p>
        </div>

        <div class="metric-grid">
            <article class="metric-card">
                <span><?php echo $summary_open_count ?: '--'; ?></span>
                <p>attractions avec un temps remonte</p>
            </article>
            <article class="metric-card">
                <span><?php echo $summary_avg_wait !== null ? e($summary_avg_wait) . ' min' : '--'; ?></span>
                <p>moyenne dans la vue actuelle</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($history_days ?: 0); ?></span>
                <p>jours d historique captures</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($sync_short_label); ?></span>
                <p>etat de la mise a jour</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="wait-times.php?view=<?php echo e($view_mode); ?>" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($catalogue_by_park as $parkName => $items) : ?>
                <a href="wait-times.php?park=<?php echo urlencode($parkName); ?>&view=<?php echo e($view_mode); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <nav class="chip-nav secondary" aria-label="Filtre par land">
            <a href="wait-times.php?<?php echo $park_choisi ? 'park=' . urlencode($park_choisi) . '&' : ''; ?>view=<?php echo e($view_mode); ?>" class="chip-link <?php echo $land_choisi === null ? 'active' : ''; ?>">Tous les lands</a>
            <?php foreach ($catalogue_by_land as $landName => $items) : ?>
                <?php if ($park_choisi !== null && $land_summaries[$landName]['park'] !== $park_choisi) {
                    continue;
                } ?>
                <a href="wait-times.php?<?php echo $park_choisi ? 'park=' . urlencode($park_choisi) . '&' : ''; ?>land=<?php echo urlencode($landName); ?>&view=<?php echo e($view_mode); ?>" class="chip-link <?php echo $land_choisi === $landName ? 'active' : ''; ?>">
                    <?php echo e($landName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <nav class="chip-nav secondary" aria-label="Filtre par etat">
            <?php foreach ($view_labels as $mode => $label) : ?>
                <a href="wait-times.php?<?php echo $park_choisi ? 'park=' . urlencode($park_choisi) . '&' : ''; ?><?php echo $land_choisi ? 'land=' . urlencode($land_choisi) . '&' : ''; ?>view=<?php echo e($mode); ?>" class="chip-link <?php echo $view_mode === $mode ? 'active' : ''; ?>">
                    <?php echo e($label); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="shell section-shell tight-top">
        <div class="two-column-tools wait-tools-layout">
            <article class="tool-panel alert-panel" data-alert-board data-alert-endpoint="wait-times.php?format=json">
                <div class="section-head compact-head">
                    <p class="eyebrow">Alertes perso</p>
                    <h2>Un seuil utile n est pas le meme pour Frozen, Crush ou Orbitron.</h2>
                    <p>Tu peux garder des alertes sur une vraie fenetre de tir par attraction et aussi sur la sortie de panne.</p>
                </div>

                <div class="card-actions">
                    <button type="button" class="btn btn-secondary" data-alert-permission>Activer les notifications</button>
                    <span class="quiet-note">Les alertes tournent tant que cette page reste ouverte.</span>
                </div>

                <div class="alert-watch-grid">
                    <?php foreach ($watch_cards as $item) : ?>
                        <article class="mini-surface alert-watch-card">
                            <div class="card-row">
                                <span class="pill soft-blue"><?php echo e($item['park']); ?></span>
                                <span class="pill soft-gold">Sous <?php echo e($item['target_wait']); ?> min</span>
                            </div>
                            <h3><?php echo e($item['name']); ?></h3>
                            <p><?php echo e($item['story']); ?></p>
                            <div class="watch-actions">
                                <button type="button" class="action-button" data-watch-action="wait" data-watch-id="<?php echo e($item['id']); ?>" data-watch-name="<?php echo e($item['name']); ?>" data-watch-threshold="<?php echo e($item['target_wait']); ?>">Alerte bon slot</button>
                                <button type="button" class="action-button" data-watch-action="reopen" data-watch-id="<?php echo e($item['id']); ?>" data-watch-name="<?php echo e($item['name']); ?>">Sortie de panne</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="alert-live-list" data-alert-live-list>
                    <div class="empty-inline">Aucune alerte encore active sur cet appareil.</div>
                </div>
            </article>

            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Lecture rapide</p>
                    <h2>Ce que la page sait raconter meme quand le live n est pas parfait.</h2>
                </div>

                <div class="stack-grid">
                    <article class="mini-surface">
                        <span class="pill soft-green"><?php echo e($summary_closed_count + $summary_untracked_count); ?> cartes</span>
                        <h3>Fermetures et flux incomplets</h3>
                        <p>Une attraction sans temps ne disparait plus dans la masse. La page garde un contexte et peut la suivre jusqu a la reouverture.</p>
                    </article>

                    <?php if ($orbitron_info) : ?>
                        <article class="mini-surface">
                            <span class="pill soft-blue">Focus Orbitron</span>
                            <h3><?php echo e($orbitron_info['name']); ?></h3>
                            <p><?php echo e($orbitron_info['story']); ?></p>
                            <small class="quiet-note">Seuil fan: <?php echo e($orbitron_info['target_wait']); ?> min.</small>
                        </article>
                    <?php endif; ?>

                    <article class="mini-surface">
                        <span class="pill soft-gold"><?php echo e($history_days ?: 0); ?> jours</span>
                        <h3>Historique heure par heure</h3>
                        <p>La logique d attente se construit au fil des visites et des rafraichissements. Plus tu accumules de jours, plus les seuils deviennent utiles.</p>
                    </article>
                </div>
            </article>
        </div>
        <script type="application/json" id="wait-alert-dataset"><?php echo json_encode($alert_dataset, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    </section>

    <section class="shell section-shell tight-top">
        <div class="park-grid">
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <?php $summary = $park_summaries[$parkName]; ?>
                <article class="park-card">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($parkName); ?></span>
                        <span class="pill soft-green"><?php echo e($summary['open_count']); ?> ouvertes</span>
                    </div>
                    <h3><?php echo e($profile['headline']); ?></h3>
                    <p><?php echo e($profile['focus']); ?></p>
                    <small class="quiet-note"><?php echo $summary['avg_wait'] !== null ? e($summary['avg_wait']) . ' min de moyenne live' : 'Moyenne a confirmer'; ?></small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if (!empty($focus_cards)) : ?>
        <section class="shell section-shell tight-top">
            <div class="section-head inline-head">
                <div>
                    <p class="eyebrow">A traiter d abord</p>
                    <h2>Les cartes qui valent le plus le coup maintenant.</h2>
                </div>
            </div>

            <div class="opportunity-grid">
                <?php foreach ($focus_cards as $item) : ?>
                    <?php $profile = waitProfileForItem($item, $wait_history_profiles, $wait_alert_profiles); ?>
                    <article class="opportunity-card">
                        <div class="card-row">
                            <span class="pill soft-green"><?php echo e($item['wait_time']); ?> min</span>
                            <span class="pill soft-blue"><?php echo e($item['park']); ?></span>
                        </div>
                        <h3><?php echo e($item['name']); ?></h3>
                        <p><?php echo e(waitStoryCopy($item, $profile, $current_hour)); ?></p>
                        <small><?php echo e(waitInfoLine($item, $profile, $current_hour)); ?></small>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="shell section-shell">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Catalogue live</p>
                <h2>Les attractions dans cette vue.</h2>
            </div>
            <span class="quiet-note"><?php echo e($sync_full_label); ?></span>
        </div>

        <?php if (!empty($display_attractions)) : ?>
            <div class="attraction-grid">
                <?php foreach ($display_attractions as $item) : ?>
                    <?php
                    $profile = waitProfileForItem($item, $wait_history_profiles, $wait_alert_profiles);
                    $hourly = hourlyProfileForNow($profile, $current_hour);
                    $id = attractionStorageKey($item['park'], $item['name']);
                    ?>
                    <article class="attraction-card status-<?php echo e($item['status']); ?>">
                        <div class="card-row">
                            <span class="land-mark small"><?php echo e(initials($item['name'])); ?></span>
                            <span class="pill soft-blue"><?php echo e($item['park']); ?></span>
                        </div>

                        <div class="attraction-top">
                            <div>
                                <h3><?php echo e($item['name']); ?></h3>
                                <p><?php echo e($item['summary']); ?></p>
                            </div>

                            <div class="wait-box">
                                <strong><?php echo $item['status'] === 'open' ? e($item['wait_time']) : '--'; ?></strong>
                                <span><?php echo e(waitUnitLabel($item['status'])); ?></span>
                            </div>
                        </div>

                        <div class="attraction-meta">
                            <span class="status-pill <?php echo $item['status'] === 'open' ? 'live' : 'offline'; ?>"><?php echo e(statusLabel($item['status'])); ?></span>
                            <span class="wait-note"><?php echo e(waitInfoLine($item, $profile, $current_hour)); ?></span>
                        </div>

                        <p class="card-note"><?php echo e(waitStoryCopy($item, $profile, $current_hour)); ?></p>

                        <div class="watch-actions">
                            <button type="button" class="action-button" data-watch-action="wait" data-watch-id="<?php echo e($id); ?>" data-watch-name="<?php echo e($item['name']); ?>" data-watch-threshold="<?php echo e($profile['target_wait']); ?>">Sous <?php echo e($profile['target_wait']); ?> min</button>
                            <button type="button" class="action-button" data-watch-action="reopen" data-watch-id="<?php echo e($id); ?>" data-watch-name="<?php echo e($item['name']); ?>">Sortie de panne</button>
                        </div>

                        <div class="attraction-foot">
                            <span class="pill <?php echo $item['status'] === 'open' ? 'soft-green' : ($item['status'] === 'closed' ? 'soft-rose' : 'soft-gold'); ?>">
                                <?php echo e($item['land']); ?>
                            </span>
                            <small><?php echo e($item['tip']); ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <article class="empty-card">
                <h3>Aucune attraction dans ce filtre</h3>
                <p>Essaie un autre parc ou une vue plus large pour retrouver le reste du resort.</p>
            </article>
        <?php endif; ?>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
