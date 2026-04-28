<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

function trendProfileForAttraction($name)
{
    $headliners = ['Big Thunder Mountain', 'Star Wars Hyperspace Mountain', "Peter Pan's Flight", 'Avengers Assemble: Flight Force', "Crush's Coaster", 'Frozen Ever After'];
    $family = ["it's a small world", 'Spider-Man W.E.B. Adventure', "Ratatouille : L'Aventure Totalement Toquee de Remy", 'Cars ROAD TRIP', 'Toy Soldiers Parachute Drop', 'Raiponce Tangled Spin', 'Orbitron'];

    if (in_array($name, $headliners, true)) {
        return 'headliner';
    }
    if (in_array($name, $family, true)) {
        return 'family';
    }
    return 'steady';
}

function buildHeuristicTrendSeries($waitTime, $name, $currentHour)
{
    $profiles = [
        'headliner' => [9 => 0.48, 10 => 0.72, 11 => 0.96, 12 => 1.12, 13 => 1.20, 14 => 1.26, 15 => 1.18, 16 => 1.06, 17 => 0.92, 18 => 0.78, 19 => 0.64, 20 => 0.50],
        'family' => [9 => 0.56, 10 => 0.78, 11 => 0.94, 12 => 1.08, 13 => 1.15, 14 => 1.18, 15 => 1.08, 16 => 0.98, 17 => 0.88, 18 => 0.76, 19 => 0.64, 20 => 0.52],
        'steady' => [9 => 0.62, 10 => 0.82, 11 => 0.94, 12 => 1.02, 13 => 1.08, 14 => 1.10, 15 => 1.04, 16 => 0.94, 17 => 0.86, 18 => 0.78, 19 => 0.68, 20 => 0.54],
    ];

    $profileKey = trendProfileForAttraction($name);
    $profile = $profiles[$profileKey];
    $normalizedHour = max(9, min(20, $currentHour));
    $baseline = max(8, (int) round($waitTime / ($profile[$normalizedHour] ?? 1)));
    $series = [];

    foreach ($profile as $hour => $factor) {
        $series[] = ['hour' => $hour, 'label' => sprintf('%02dh', $hour), 'wait' => (int) round($baseline * $factor)];
    }

    return ['profile' => $profileKey, 'points' => $series];
}

function buildHistoryTrendSeries($profile)
{
    $series = [];
    $overall = $profile['avg_wait'] ?? $profile['typical_wait'] ?? 20;

    for ($hour = 9; $hour <= 20; $hour++) {
        $bucket = $profile['hourly'][$hour] ?? null;
        $value = $bucket['avg'] ?? $overall;
        $series[] = ['hour' => $hour, 'label' => sprintf('%02dh', $hour), 'wait' => (int) $value];
    }

    return ['profile' => 'history', 'points' => $series];
}

function trendDirectionLabel($series, $currentHour)
{
    $normalizedHour = max(10, min(19, $currentHour));
    $currentValue = null;
    $nextValue = null;
    foreach ($series as $point) {
        if ($point['hour'] === $normalizedHour) {
            $currentValue = $point['wait'];
        }
        if ($point['hour'] === $normalizedHour + 1) {
            $nextValue = $point['wait'];
        }
    }
    if ($currentValue === null || $nextValue === null) {
        return 'stable';
    }
    $delta = $nextValue - $currentValue;
    if ($delta >= 6) {
        return 'monte';
    }
    if ($delta <= -6) {
        return 'redescend';
    }
    return 'stable';
}

function crowdScoreForDate(DateTimeImmutable $date, $windows)
{
    $score = 32;
    $signals = [];
    $weekday = (int) $date->format('N');

    if ($weekday >= 6) {
        $score += 14;
        $signals[] = 'week-end';
    }

    foreach ($windows as $window) {
        if ($date->format('Y-m-d') >= $window['start'] && $date->format('Y-m-d') <= $window['end']) {
            $score += $window['weight'];
            $signals[] = $window['country'];
        }
    }

    if ($date->format('m') === '07' || $date->format('m') === '08') {
        $score += 8;
        $signals[] = 'haute saison';
    } elseif ($date->format('m') === '06') {
        $score += 4;
    } elseif ($date->format('m') === '10') {
        $score += 6;
        $signals[] = 'Halloween';
    } elseif ($date->format('m') === '12') {
        $score += 9;
        $signals[] = 'Noel';
    }

    return ['score' => min(95, $score), 'signals' => array_values(array_unique($signals))];
}

function crowdLevelFromScore($score)
{
    if ($score >= 78) {
        return 'peak';
    }
    if ($score >= 62) {
        return 'high';
    }
    if ($score >= 46) {
        return 'busy';
    }
    return 'calm';
}

function crowdLabelFromScore($score)
{
    if ($score >= 78) {
        return 'Tres forte';
    }
    if ($score >= 62) {
        return 'Chargee';
    }
    if ($score >= 46) {
        return 'Soutenue';
    }
    return 'Souple';
}

function buildCrowdMonths($startMonth, $count, $windows)
{
    $labels = [1 => 'Janvier', 2 => 'Fevrier', 3 => 'Mars', 4 => 'Avril', 5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Aout', 9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Decembre'];
    $months = [];
    $cursor = new DateTimeImmutable($startMonth . '-01');

    for ($i = 0; $i < $count; $i++) {
        $month = $cursor->modify('+' . $i . ' month');
        $firstDay = $month->modify('first day of this month');
        $lastDay = $month->modify('last day of this month');
        $padding = (int) $firstDay->format('N') - 1;
        $days = [];
        $peakCount = 0;
        $highCount = 0;
        $signalCount = 0;

        for ($pad = 0; $pad < $padding; $pad++) {
            $days[] = ['pad' => true];
        }

        $dayCursor = $firstDay;
        while ($dayCursor <= $lastDay) {
            $scoreData = crowdScoreForDate($dayCursor, $windows);
            $level = crowdLevelFromScore($scoreData['score']);
            if ($level === 'peak') {
                $peakCount++;
            } elseif ($level === 'high') {
                $highCount++;
            }
            if (!empty($scoreData['signals'])) {
                $signalCount++;
            }

            $days[] = [
                'pad' => false,
                'day' => (int) $dayCursor->format('j'),
                'score' => $scoreData['score'],
                'level' => $level,
                'label' => crowdLabelFromScore($scoreData['score']),
                'signals' => $scoreData['signals'],
            ];
            $dayCursor = $dayCursor->modify('+1 day');
        }

        $months[] = [
            'title' => $labels[(int) $month->format('n')] . ' ' . $month->format('Y'),
            'days' => $days,
            'peak_count' => $peakCount,
            'high_count' => $highCount,
            'signal_count' => $signalCount,
        ];
    }

    return $months;
}

$months_view = (int) ($_GET['months'] ?? 6);
if (!in_array($months_view, [3, 6, 12], true)) {
    $months_view = 6;
}

$current_hour = (int) date('G');
$history_days = 0;
foreach ($wait_history_records as $record) {
    if (!empty($record['date'])) {
        $history_days++;
    }
}
$history_days = $history_days > 0 ? count(array_unique(array_column($wait_history_records, 'date'))) : 0;

$trend_candidates = array_values(array_filter($flat_catalogue, function ($item) use ($park_choisi) {
    if ($park_choisi !== null && $item['park'] !== $park_choisi) {
        return false;
    }
    return $item['status'] === 'open' || in_array($item['name'], ['Frozen Ever After', 'Orbitron', 'Avengers Assemble: Flight Force', "Crush's Coaster"], true);
}));

usort($trend_candidates, function ($left, $right) {
    $leftWait = is_numeric($left['wait_time']) ? (int) $left['wait_time'] : 999;
    $rightWait = is_numeric($right['wait_time']) ? (int) $right['wait_time'] : 999;
    if ($leftWait === $rightWait) {
        return strcmp($left['name'], $right['name']);
    }
    return $rightWait <=> $leftWait;
});

$trend_candidates = array_slice($trend_candidates, 0, 8);
if (empty($trend_candidates)) {
    $trend_candidates = [
        ['name' => 'Big Thunder Mountain', 'land' => 'Frontierland', 'park' => 'Disneyland Park', 'wait_time' => 40, 'status' => 'open'],
        ['name' => 'Avengers Assemble: Flight Force', 'land' => 'Marvel Avengers Campus', 'park' => 'Disney Adventure World', 'wait_time' => 35, 'status' => 'open'],
        ['name' => 'Frozen Ever After', 'land' => 'World of Frozen', 'park' => 'Disney Adventure World', 'wait_time' => 45, 'status' => 'open'],
    ];
}

$trend_payload = [];
$threshold_cards = [];
foreach ($trend_candidates as $candidate) {
    $id = attractionStorageKey($candidate['park'], $candidate['name']);
    if (isset($wait_history_profiles[$id])) {
        $profile = $wait_history_profiles[$id];
    } else {
        $fallback = waitAlertProfileForAttraction($candidate['name'], $wait_alert_profiles);
        $profile = [
            'days' => 0,
            'hourly' => [],
            'avg_wait' => null,
            'typical_wait' => $fallback['typical'],
            'target_wait' => $fallback['target'],
            'great_wait' => $fallback['great'],
        ];
    }
    $waitTime = is_numeric($candidate['wait_time'] ?? null) ? (int) $candidate['wait_time'] : ($profile['avg_wait'] ?? $profile['typical_wait'] ?? 25);
    $hasHistory = !empty($profile['hourly']) && (int) ($profile['days'] ?? 0) >= 2;
    $trendData = $hasHistory ? buildHistoryTrendSeries($profile) : buildHeuristicTrendSeries($waitTime, $candidate['name'], $current_hour);

    $trend_payload[] = [
        'id' => normalizeName($candidate['name']),
        'name' => $candidate['name'],
        'land' => $candidate['land'],
        'park' => $candidate['park'],
        'wait_time' => $waitTime,
        'profile' => $trendData['profile'],
        'basis' => $hasHistory ? 'history' : 'estimate',
        'days' => (int) ($profile['days'] ?? 0),
        'direction' => trendDirectionLabel($trendData['points'], $current_hour),
        'target_wait' => (int) ($profile['target_wait'] ?? 15),
        'typical_wait' => (int) ($profile['typical_wait'] ?? $profile['avg_wait'] ?? $waitTime),
        'points' => $trendData['points'],
    ];

    $threshold_cards[] = [
        'name' => $candidate['name'],
        'park' => $candidate['park'],
        'target_wait' => (int) ($profile['target_wait'] ?? 15),
        'typical_wait' => (int) ($profile['typical_wait'] ?? $profile['avg_wait'] ?? $waitTime),
        'days' => (int) ($profile['days'] ?? 0),
    ];
}

usort($threshold_cards, function ($left, $right) {
    if ($left['typical_wait'] === $right['typical_wait']) {
        return strcmp($left['name'], $right['name']);
    }
    return $right['typical_wait'] <=> $left['typical_wait'];
});
$threshold_cards = array_slice($threshold_cards, 0, 6);

$crowd_months = buildCrowdMonths(date('Y-m'), $months_view, $holiday_windows);

$page_title = $park_choisi ? 'Stats - ' . $park_choisi : 'Stats et affluence';
$page_description = $park_choisi
    ? 'Lecture des tendances et de l affluence pour ' . $park_choisi . '.'
    : 'Courbes d attente indicatives et estimation d affluence sur les deux parcs.';

renderHead($page_title, $page_description, $site);
renderHeader('insights', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Stats et previsions</p>
            <h1><?php echo e($park_choisi ? 'Lire ' . $park_choisi . ' avec plus de recul.' : 'Lire les flux resort avec plus de profondeur et plus d horizon.'); ?></h1>
            <p>
                La vraie difference ici, c est qu on ne regarde plus seulement le live du moment.
                On garde une memoire heure par heure et un calendrier qui peut partir plus loin.
            </p>
        </div>

        <div class="metric-grid">
            <article class="metric-card">
                <span><?php echo e(count($trend_payload)); ?></span>
                <p>attractions suivies dans cette lecture</p>
            </article>
            <article class="metric-card">
                <span><?php echo $park_choisi && $park_summaries[$park_choisi]['avg_wait'] !== null ? e($park_summaries[$park_choisi]['avg_wait']) . ' min' : ($avg_wait !== null ? e($avg_wait) . ' min' : '--'); ?></span>
                <p><?php echo e($park_choisi ? 'moyenne live du parc' : 'moyenne live resort'); ?></p>
            </article>
            <article class="metric-card">
                <span><?php echo e($months_view); ?> mois</span>
                <p>de projection calendrier</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($history_days ?: 0); ?></span>
                <p>jours d historique deja en memoire</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="insights.php?months=<?php echo e($months_view); ?>" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="insights.php?park=<?php echo urlencode($parkName); ?>&months=<?php echo e($months_view); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <nav class="chip-nav secondary" aria-label="Horizon du calendrier">
            <?php foreach ([3, 6, 12] as $option) : ?>
                <a href="insights.php?<?php echo $park_choisi ? 'park=' . urlencode($park_choisi) . '&' : ''; ?>months=<?php echo e($option); ?>" class="chip-link <?php echo $months_view === $option ? 'active' : ''; ?>">
                    <?php echo e($option); ?> mois
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

    <section class="shell section-shell">
        <div class="two-column-tools trend-layout">
            <article class="tool-panel" data-trend-app>
                <div class="section-head compact-head">
                    <p class="eyebrow">Historique heure par heure</p>
                    <h2>La file monte ou se detend generalement a cette heure-ci ?</h2>
                </div>

                <div class="trend-toolbar">
                    <label class="select-field">
                        <span>Attraction suivie</span>
                        <select data-trend-select>
                            <?php foreach ($trend_payload as $item) : ?>
                                <option value="<?php echo e($item['id']); ?>"><?php echo e($item['name'] . ' - ' . $item['park']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <div class="trend-summary">
                        <span class="meta-label">Lecture</span>
                        <strong data-trend-status>--</strong>
                        <small data-trend-caption>--</small>
                    </div>
                </div>

                <div class="trend-chart-card">
                    <svg viewBox="0 0 720 260" class="trend-svg" data-trend-svg aria-label="Courbe d attente"></svg>
                    <div class="trend-axis" data-trend-axis></div>
                </div>
            </article>

            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Calendrier d affluence</p>
                    <h2>Un horizon plus long pour les visiteurs qui veulent se projeter loin.</h2>
                    <p><?php echo e($source_notes['crowd']); ?></p>
                </div>

                <div class="calendar-legend">
                    <span class="legend-chip level-calm">Souple</span>
                    <span class="legend-chip level-busy">Soutenue</span>
                    <span class="legend-chip level-high">Chargee</span>
                    <span class="legend-chip level-peak">Tres forte</span>
                </div>

                <div class="stack-grid">
                    <?php foreach ($threshold_cards as $item) : ?>
                        <article class="mini-surface">
                            <div class="card-row">
                                <span class="pill soft-blue"><?php echo e($item['park']); ?></span>
                                <span class="pill soft-gold">Vise <?php echo e($item['target_wait']); ?> min</span>
                            </div>
                            <h3><?php echo e($item['name']); ?></h3>
                            <p>Lecture typique autour de <?php echo e($item['typical_wait']); ?> min quand le resort est dans une journee standard.</p>
                            <small class="quiet-note"><?php echo e($item['days']); ?> jour(s) de recul pour cette estimation.</small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
        <script type="application/json" id="trend-dataset"><?php echo json_encode($trend_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    </section>

    <section class="shell section-shell">
        <div class="calendar-grid">
            <?php foreach ($crowd_months as $month) : ?>
                <article class="calendar-card">
                    <div class="calendar-card-head">
                        <h3><?php echo e($month['title']); ?></h3>
                        <span class="pill soft-gold"><?php echo e($month['peak_count']); ?> pics</span>
                    </div>
                    <div class="calendar-summary-row">
                        <span><?php echo e($month['high_count']); ?> jours charges</span>
                        <span><?php echo e($month['signal_count']); ?> jours a signaux exterieurs</span>
                    </div>
                    <div class="calendar-weekdays">
                        <span>Lun</span><span>Mar</span><span>Mer</span><span>Jeu</span><span>Ven</span><span>Sam</span><span>Dim</span>
                    </div>
                    <div class="calendar-days">
                        <?php foreach ($month['days'] as $day) : ?>
                            <?php if (!empty($day['pad'])) : ?>
                                <div class="calendar-day is-pad"></div>
                            <?php else : ?>
                                <div class="calendar-day level-<?php echo e($day['level']); ?>" title="<?php echo e($day['label']); ?>">
                                    <strong><?php echo e($day['day']); ?></strong>
                                    <span><?php echo e($day['score']); ?></span>
                                    <small><?php echo e(implode(' / ', array_slice($day['signals'], 0, 2))); ?></small>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
