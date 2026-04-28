<?php
include 'includes/data.php';
include 'includes/layout.php';

function clockToMinutes($value)
{
    if (!preg_match('/^\d{2}:\d{2}$/', (string) $value)) {
        return null;
    }

    [$hours, $minutes] = explode(':', $value);
    return ((int) $hours * 60) + (int) $minutes;
}

function showSessionCards($shows, $focusMinutes)
{
    $sessions = [];

    foreach ($shows as $show) {
        foreach ($show['times'] as $time) {
            $start = clockToMinutes($time);
            if ($start === null) {
                continue;
            }

            $distance = abs($start - $focusMinutes);
            $sessions[] = [
                'id' => $show['id'] . '-' . str_replace(':', '', $time),
                'name' => $show['name'],
                'park' => $show['park'],
                'kind' => $show['kind'],
                'time' => $time,
                'location' => $show['location'],
                'booking' => $show['booking'],
                'distance' => $distance,
            ];
        }
    }

    usort($sessions, function ($left, $right) {
        if ($left['distance'] === $right['distance']) {
            return strcmp($left['time'], $right['time']);
        }
        return $left['distance'] <=> $right['distance'];
    });

    return $sessions;
}

$park_choisi = $_GET['park'] ?? null;
if ($park_choisi !== null && !isset($park_profiles[$park_choisi])) {
    $park_choisi = null;
}

$time_focus = $_GET['time'] ?? '17:30';
if (!preg_match('/^\d{2}:\d{2}$/', $time_focus)) {
    $time_focus = '17:30';
}

$focus_minutes = clockToMinutes($time_focus) ?? (17 * 60 + 30);

$visible_shows = array_values(array_filter($show_reference, function ($show) use ($park_choisi) {
    return $park_choisi === null || $show['park'] === $park_choisi;
}));

$show_sessions = showSessionCards($visible_shows, $focus_minutes);
$nearest_sessions = array_slice($show_sessions, 0, 6);
$program_dataset = [
    'focus' => $time_focus,
    'sessions' => $show_sessions,
];

renderHead('Shows', 'Programme en grille pour visualiser les shows les plus proches de l heure visee.', $site);
renderHeader('shows', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Programme des shows</p>
            <h1>Visualiser la journee en grille plutot que chasser les horaires show par show.</h1>
            <p>
                Cette page sert a voir vite ce qui tombe autour d une heure cible.
                Les horaires exacts restent a verifier le jour meme, mais la lecture devient beaucoup plus confortable.
            </p>
        </div>

        <div class="metric-grid">
            <article class="metric-card">
                <span><?php echo e(count($visible_shows)); ?></span>
                <p>shows dans cette vue</p>
            </article>
            <article class="metric-card">
                <span><?php echo e(count($show_sessions)); ?></span>
                <p>creaneaux visibles</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($time_focus); ?></span>
                <p>heure cible actuelle</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($park_choisi ? '1' : '2'); ?></span>
                <p>parc(s) dans le programme</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="shows.php?time=<?php echo e($time_focus); ?>" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout le resort</a>
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <a href="shows.php?park=<?php echo urlencode($parkName); ?>&time=<?php echo e($time_focus); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </section>

    <section class="shell section-shell tight-top">
        <div class="two-column-tools">
            <article class="tool-panel" data-show-program>
                <div class="section-head compact-head">
                    <p class="eyebrow">Viseur horaire</p>
                    <h2>Je sais quand je veux voir un show, pas encore lequel.</h2>
                    <p><?php echo e($source_notes['shows']); ?></p>
                </div>

                <form class="show-focus-form" method="get">
                    <?php if ($park_choisi) : ?>
                        <input type="hidden" name="park" value="<?php echo e($park_choisi); ?>">
                    <?php endif; ?>
                    <label class="select-field show-time-field">
                        <span>Je vise vers</span>
                        <input type="time" name="time" value="<?php echo e($time_focus); ?>" data-show-target>
                    </label>
                    <button class="btn btn-secondary" type="submit">Mettre a jour</button>
                </form>

                <div class="alert-live-list" data-show-nearest-list>
                    <?php foreach ($nearest_sessions as $session) : ?>
                        <div class="alert-live-item">
                            <strong><?php echo e($session['time'] . ' - ' . $session['name']); ?></strong>
                            <span><?php echo e($session['park'] . ' - ' . $session['location']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="tool-panel">
                <div class="section-head compact-head">
                    <p class="eyebrow">Repere rapide</p>
                    <h2>Les sessions les plus proches de <?php echo e($time_focus); ?>.</h2>
                </div>

                <div class="stack-grid">
                    <?php foreach ($nearest_sessions as $session) : ?>
                        <article class="mini-surface">
                            <div class="card-row">
                                <span class="pill soft-blue"><?php echo e($session['park']); ?></span>
                                <span class="pill soft-gold"><?php echo e($session['time']); ?></span>
                            </div>
                            <h3><?php echo e($session['name']); ?></h3>
                            <p><?php echo e($session['location']); ?></p>
                            <small class="quiet-note"><?php echo e($session['kind'] . ' - ' . $session['booking']); ?></small>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
        <script type="application/json" id="show-program-dataset"><?php echo json_encode($program_dataset, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    </section>

    <section class="shell section-shell">
        <div class="show-program-board">
            <?php foreach ($visible_shows as $show) : ?>
                <article class="show-row">
                    <div class="show-row-copy">
                        <div class="card-row">
                            <span class="pill soft-blue"><?php echo e($show['park']); ?></span>
                            <span class="pill soft-gold"><?php echo e($show['kind']); ?></span>
                        </div>
                        <h3><?php echo e($show['name']); ?></h3>
                        <p><?php echo e($show['location']); ?></p>
                        <small class="quiet-note"><?php echo e(($show['duration'] ?? 30) . ' min - ' . $show['booking']); ?></small>
                        <?php if (!empty($show['break_notice'])) : ?>
                            <small class="quiet-note"><?php echo e($show['break_notice']); ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="show-session-grid">
                        <?php foreach ($show['times'] as $time) : ?>
                            <?php
                            $minutes = clockToMinutes($time);
                            if ($minutes === null) {
                                continue;
                            }
                            $distance = abs($minutes - $focus_minutes);
                            ?>
                            <button
                                type="button"
                                class="show-slot<?php echo $distance <= 20 ? ' is-near' : ''; ?>"
                                data-show-slot
                                data-show-time="<?php echo e($time); ?>"
                                data-show-name="<?php echo e($show['name']); ?>"
                                data-show-park="<?php echo e($show['park']); ?>"
                                data-show-location="<?php echo e($show['location']); ?>"
                                data-show-kind="<?php echo e($show['kind']); ?>"
                                data-show-booking="<?php echo e($show['booking']); ?>"
                            >
                                <strong><?php echo e($time); ?></strong>
                                <span><?php echo e($show['kind']); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
