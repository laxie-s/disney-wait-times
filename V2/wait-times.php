<?php
include('data.php');

$land_choisi = $_GET['land'] ?? null;
if ($land_choisi !== null && !isset($catalogue_by_land[$land_choisi])) {
    $land_choisi = null;
}

$view_mode = $_GET['view'] ?? 'all';
$view_labels = [
    'all' => 'Tout voir',
    'open' => 'Ouvertes',
    'closed' => 'Fermees et a verifier',
];

if (!isset($view_labels[$view_mode])) {
    $view_mode = 'all';
}

$summary_open_count = $land_choisi ? $land_summaries[$land_choisi]['open_count'] : $open_count;
$summary_closed_count = $land_choisi ? $land_summaries[$land_choisi]['closed_count'] : $closed_count;
$summary_untracked_count = $land_choisi ? $land_summaries[$land_choisi]['untracked_count'] : $untracked_count;
$summary_avg_wait = $land_choisi ? $land_summaries[$land_choisi]['avg_wait'] : $avg_wait;

$display_attractions = [];
foreach ($flat_catalogue as $item) {
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

usort($display_attractions, function ($left, $right) {
    $statusOrder = [
        'open' => 0,
        'closed' => 1,
        'untracked' => 2,
        'offline' => 3,
    ];

    $leftStatus = $statusOrder[$left['status']] ?? 9;
    $rightStatus = $statusOrder[$right['status']] ?? 9;

    if ($leftStatus !== $rightStatus) {
        return $leftStatus <=> $rightStatus;
    }

    if ($left['status'] === 'open' && $right['status'] === 'open') {
        if ($left['wait_time'] === $right['wait_time']) {
            return strcmp($left['name'], $right['name']);
        }

        return $left['wait_time'] <=> $right['wait_time'];
    }

    return strcmp($left['name'], $right['name']);
});

$focus_cards = [];
$focus_source = $land_choisi ? $catalogue_by_land[$land_choisi] : $top_picks;
foreach ($focus_source as $item) {
    if ($item['status'] === 'open') {
        $focus_cards[] = $item;
    }

    if (count($focus_cards) === 3) {
        break;
    }
}

$page_title = $land_choisi ? 'Temps d attente - ' . $land_choisi : 'Temps d attente';
$page_intro = $land_choisi
    ? 'Lecture detaillee du land avec les attractions ouvertes, fermees et a surveiller.'
    : 'Vue globale du parc pour reperer vite les meilleurs choix et les points de blocage.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> - <?php echo e($site_name); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="notice-bar">
    <?php echo e($legal_notice); ?>
</div>

<header id="main-header">
    <div class="header-container">
        <a class="brand" href="index.php" aria-label="<?php echo e($site_name); ?>">
            <span class="brand-mark">DL</span>
            <span>
                <strong><?php echo e($site_name); ?></strong>
                <small>Guide fan independant</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Navigation principale">
            <ul>
                <li><a class="is-active" href="wait-times.php">Temps d attente</a></li>
                <li><a href="index.php#actus">Ce qu on suit</a></li>
                <li><a href="index.php#secrets">Details caches</a></li>
                <li><a href="index.php#guide">Positionnement</a></li>
            </ul>
        </nav>
    </div>
</header>

<main class="container-app">
    <header class="page-header">
        <p class="eyebrow"><?php echo $live_is_available ? 'Donnees live synchronisees' : 'Mode de secours'; ?></p>
        <h1><?php echo e($page_title); ?></h1>
        <p><?php echo e($page_intro); ?></p>
    </header>

    <section class="summary-grid" aria-label="Resume du tableau">
        <article class="summary-card">
            <span class="summary-label">Synchronisation</span>
            <strong><?php echo e($sync_short_label); ?></strong>
            <p><?php echo e($sync_full_label); ?></p>
        </article>
        <article class="summary-card">
            <span class="summary-label">Ouvertes</span>
            <strong><?php echo $summary_open_count ?: '--'; ?></strong>
            <p>attractions avec un temps d attente remonte</p>
        </article>
        <article class="summary-card">
            <span class="summary-label">Attente moyenne</span>
            <strong><?php echo $summary_avg_wait !== null ? e($summary_avg_wait) . ' min' : '--'; ?></strong>
            <p>lecture rapide pour situer la densite du moment</p>
        </article>
        <article class="summary-card">
            <span class="summary-label">Fermetures / a verifier</span>
            <strong><?php echo e($summary_closed_count + $summary_untracked_count); ?></strong>
            <p><?php echo e($summary_closed_count); ?> fermees, <?php echo e($summary_untracked_count); ?> a verifier</p>
        </article>
    </section>

    <nav class="lands-filter" aria-label="Filtres du tableau">
        <div class="filter-scroll">
            <a href="wait-times.php?view=<?php echo e($view_mode); ?>" class="filter-item <?php echo $land_choisi === null ? 'active' : ''; ?>">Tout le parc</a>
            <?php foreach ($catalogue_by_land as $landName => $items) : ?>
                <a
                    href="wait-times.php?land=<?php echo urlencode($landName); ?>&view=<?php echo e($view_mode); ?>"
                    class="filter-item <?php echo $land_choisi === $landName ? 'active' : ''; ?>"
                >
                    <?php echo e($landName); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <nav class="mode-filter" aria-label="Etat des attractions">
        <?php foreach ($view_labels as $mode => $label) : ?>
            <a
                href="wait-times.php?<?php echo $land_choisi ? 'land=' . urlencode($land_choisi) . '&' : ''; ?>view=<?php echo e($mode); ?>"
                class="mode-pill <?php echo $view_mode === $mode ? 'active' : ''; ?>"
            >
                <?php echo e($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if (!empty($focus_cards)) : ?>
        <section class="focus-grid" aria-label="Bonnes opportunites">
            <?php foreach ($focus_cards as $item) : ?>
                <article class="focus-card">
                    <span class="wait-badge"><?php echo e($item['wait_time']); ?> min</span>
                    <h2><?php echo e($item['name']); ?></h2>
                    <p><?php echo e($item['summary']); ?></p>
                    <small><?php echo e($item['land']); ?></small>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($display_attractions)) : ?>
        <section class="attractions-grid">
            <?php foreach ($display_attractions as $item) : ?>
                <article class="attraction-card status-<?php echo e($item['status']); ?>">
                    <div class="attraction-main">
                        <div class="attraction-symbol">
                            <span><?php echo e(initials($item['name'])); ?></span>
                        </div>

                        <div class="card-info">
                            <div class="text-group">
                                <div class="title-row">
                                    <h3><?php echo e($item['name']); ?></h3>
                                    <?php if ($land_choisi === null) : ?>
                                        <span class="mini-land"><?php echo e($item['land']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo e($item['summary']); ?></p>
                            </div>

                            <div class="wait-time-box">
                                <span class="time-value"><?php echo $item['status'] === 'open' ? e($item['wait_time']) : '--'; ?></span>
                                <span class="time-unit"><?php echo e(waitUnitLabel($item['status'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span class="status-chip status-<?php echo e($item['status']); ?>">
                            <?php echo e(statusLabel($item['status'])); ?>
                        </span>
                        <p><?php echo e($item['tip']); ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php else : ?>
        <div class="empty-state">
            <h3>Aucune carte a afficher dans ce filtre</h3>
            <p>Essaie un autre land ou repasse sur la vue complete pour retrouver le reste du parc.</p>
        </div>
    <?php endif; ?>
</main>

<footer>
    <strong><?php echo e($site_name); ?></strong>
    <span><?php echo e($legal_notice); ?></span>
</footer>

</body>
</html>
