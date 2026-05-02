<?php
include 'includes/data.php';
include 'includes/layout.php';

function homeFindAttraction($name, $catalogue)
{
    foreach ($catalogue as $item) {
        if (($item['name'] ?? '') === $name) {
            return $item;
        }
    }

    return null;
}

function homeFindDiningSpot($id, $spots)
{
    foreach ($spots as $spot) {
        if (($spot['id'] ?? '') === $id) {
            return $spot;
        }
    }

    return null;
}

function homeBuildShowCards($shows)
{
    $now = new DateTime('now', new DateTimeZone('Europe/Paris'));
    $nowMinutes = ((int) $now->format('H') * 60) + (int) $now->format('i');
    $cards = [];

    foreach ($shows as $show) {
        if (!empty($show['times'])) {
            foreach ($show['times'] as $time) {
                if (!preg_match('/^(\d{2}):(\d{2})$/', $time, $matches)) {
                    continue;
                }

                $minutes = ((int) $matches[1] * 60) + (int) $matches[2];
                $delta = $minutes - $nowMinutes;
                if ($delta < 0) {
                    $delta += 24 * 60;
                }

                $cards[] = [
                    'id' => $show['id'] . '-' . $time,
                    'name' => $show['name'],
                    'park' => $show['park'],
                    'kind' => $show['kind'],
                    'time_label' => $time,
                    'location' => $show['location'],
                    'delta' => $delta,
                ];
            }
            continue;
        }

        $cards[] = [
            'id' => $show['id'] . '-note',
            'name' => $show['name'],
            'park' => $show['park'],
            'kind' => $show['kind'],
            'time_label' => 'Horaire a verifier',
            'location' => $show['location'],
            'delta' => 9999,
        ];
    }

    usort($cards, function ($left, $right) {
        if ($left['delta'] === $right['delta']) {
            return strcmp($left['name'], $right['name']);
        }

        return $left['delta'] <=> $right['delta'];
    });

    return array_slice($cards, 0, 4);
}

$featuredArticle = $news_articles[0] ?? null;
$homeHeroVisual = siteEditorialImage('adventure-world');

$waitNames = [
    'Frozen Ever After',
    "Crush's Coaster",
    'Big Thunder Mountain',
    'Spider-Man W.E.B. Adventure',
];

$waitSpotlights = [];
foreach ($waitNames as $name) {
    $found = homeFindAttraction($name, $flat_catalogue);
    if ($found) {
        $waitSpotlights[] = $found;
    }
}

$foodIds = ['regal-view', 'captain-jacks', 'pym-kitchen', 'hakuna-matata'];
$foodSpotlights = [];
foreach ($foodIds as $id) {
    $found = homeFindDiningSpot($id, $dining_spots);
    if ($found) {
        $foodSpotlights[] = $found;
    }
}

$showSpotlights = homeBuildShowCards($show_reference);
$newsSpotlights = array_slice($news_articles, 0, 4);
$parkCards = [];

foreach ($park_profiles as $parkName => $profile) {
    $parkCards[] = [
        'name' => $parkName,
        'headline' => $profile['headline'],
        'focus' => $profile['focus'],
        'open_count' => $park_summaries[$parkName]['open_count'] ?? 0,
        'avg_wait' => $park_summaries[$parkName]['avg_wait'] ?? null,
    ];
}

renderHead('Accueil', $site['meta_description'], $site, ['assets/css/pages/home.css']);
renderHeader('home', $site, $nav_items);
?>
<main class="page-main">
    <section class="home-hero">
        <div class="shell home-hero-shell">
            <div class="home-hero-stage">
                <img src="<?php echo e($homeHeroVisual); ?>" alt="Vue editoriale de Disney Adventure World">
                <div class="home-hero-overlay"></div>

                <div class="home-hero-copy">
                    <span class="eyebrow">Deux parcs, live, food, stats et secrets</span>
                    <h1>Le guide fan qui aide a lire Disneyland Paris avec plus de style et plus de clarte.</h1>
                    <p class="lead">
                        Une lecture resort pensee comme une vraie destination :
                        plus de reperes, des choix plus rapides et une vue plus lisible sur Disneyland Park
                        et Disney Adventure World.
                    </p>
                    <div class="hero-actions">
                        <a class="btn btn-primary" href="wait-times.php">Ouvrir le live</a>
                        <a class="btn btn-secondary" href="planner.php">Preparer sa visite</a>
                    </div>
                    <div class="tag-row hero-tag-row">
                        <span>Guide fan independant</span>
                        <span>Deux parcs</span>
                        <span>Lecture terrain</span>
                        <span>Actus resort</span>
                    </div>
                </div>

                <aside class="home-hero-aside">
                    <span class="pill soft-gold">Focus du moment</span>
                    <h2><?php echo e($best_park_name ?: 'Disney Adventure World'); ?></h2>
                    <p>
                        <?php if ($best_pick) : ?>
                            <?php echo e($best_pick['name']); ?> reste l une des meilleures lectures du moment avec
                            <?php echo e((string) $best_pick['wait_time']); ?> min.
                        <?php else : ?>
                            Meme quand le flux live ralentit, le site garde sa lecture pratique, editoriale et fan.
                        <?php endif; ?>
                    </p>
                    <div class="hero-inline-stats">
                        <article>
                            <strong><?php echo e((string) $open_count); ?></strong>
                            <small>attractions ouvertes</small>
                        </article>
                        <article>
                            <strong><?php echo $avg_wait !== null ? e((string) $avg_wait) . ' min' : '--'; ?></strong>
                            <small>moyenne resort</small>
                        </article>
                    </div>
                </aside>
            </div>

            <section class="hero-dock" data-home-dock>
                <div class="hero-dock-tabs" role="tablist" aria-label="Apercu accueil">
                    <button type="button" class="hero-dock-tab is-active" data-home-tab="waits" aria-selected="true">Temps d attente</button>
                    <button type="button" class="hero-dock-tab" data-home-tab="food" aria-selected="false">Restauration</button>
                    <button type="button" class="hero-dock-tab" data-home-tab="shows" aria-selected="false">Programme shows</button>
                    <button type="button" class="hero-dock-tab" data-home-tab="news" aria-selected="false">Actus</button>
                </div>

                <div class="hero-dock-panels">
                    <div class="hero-dock-panel" data-home-panel="waits">
                        <div class="hero-dock-grid hero-dock-grid-rich">
                            <?php foreach ($waitSpotlights as $item) : ?>
                                <a class="hero-dock-card hero-dock-card-rich" href="wait-times.php">
                                    <img src="<?php echo e(parkEditorialImage($item['park'])); ?>" alt="<?php echo e($item['park']); ?>">
                                    <div>
                                        <span class="meta-label"><?php echo e($item['park']); ?></span>
                                        <strong><?php echo e($item['name']); ?></strong>
                                        <small>
                                            <?php echo e($item['land']); ?> -
                                            <?php echo $item['status'] === 'open' && is_numeric($item['wait_time']) ? e((string) $item['wait_time']) . ' min en ce moment' : 'Statut a surveiller'; ?>
                                        </small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="hero-dock-panel" data-home-panel="food" hidden>
                        <div class="hero-dock-grid hero-dock-grid-rich">
                            <?php foreach ($foodSpotlights as $spot) : ?>
                                <a class="hero-dock-card hero-dock-card-rich" href="food.php">
                                    <img src="<?php echo e(featureEditorialImage('food')); ?>" alt="Restauration Disneyland Paris">
                                    <div>
                                        <span class="meta-label"><?php echo e($spot['land']); ?></span>
                                        <strong><?php echo e($spot['name']); ?></strong>
                                        <small><?php echo e($spot['service']); ?> - <?php echo e($spot['booking']); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="hero-dock-panel" data-home-panel="shows" hidden>
                        <div class="hero-dock-grid hero-dock-grid-rich">
                            <?php foreach ($showSpotlights as $show) : ?>
                                <a class="hero-dock-card hero-dock-card-rich" href="shows.php">
                                    <img src="<?php echo e(parkEditorialImage($show['park'])); ?>" alt="<?php echo e($show['park']); ?>">
                                    <div>
                                        <span class="meta-label"><?php echo e($show['park']); ?></span>
                                        <strong><?php echo e($show['time_label']); ?> - <?php echo e($show['name']); ?></strong>
                                        <small><?php echo e($show['kind']); ?> - <?php echo e($show['location']); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="hero-dock-panel" data-home-panel="news" hidden>
                        <div class="hero-dock-grid hero-dock-grid-rich">
                            <?php foreach ($newsSpotlights as $article) : ?>
                                <a class="hero-dock-card hero-dock-card-rich" href="article.php?slug=<?php echo urlencode($article['slug']); ?>">
                                    <img src="<?php echo e(articleEditorialImage($article)); ?>" alt="<?php echo e($article['title']); ?>">
                                    <div>
                                        <span class="meta-label"><?php echo e($article['park']); ?></span>
                                        <strong><?php echo e($article['title']); ?></strong>
                                        <small><?php echo e($article['date']); ?> - <?php echo e($article['reading_time']); ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <div class="metric-grid">
            <article class="metric-card">
                <span>2</span>
                <p>parcs lus dans une meme logique de visite</p>
            </article>
            <article class="metric-card">
                <span><?php echo e((string) $total_attractions); ?></span>
                <p>attractions suivies dans cette base</p>
            </article>
            <article class="metric-card">
                <span><?php echo $peak_pick ? e((string) $peak_pick['wait_time']) . ' min' : '--'; ?></span>
                <p>plus forte attente visible</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($sync_short_label); ?></span>
                <p>etat du flux live</p>
            </article>
        </div>

        <div class="page-anchor-wrap">
            <span class="meta-label">Sommaire de la page</span>
            <nav class="chip-nav page-anchor-nav" aria-label="Sommaire accueil">
                <a href="#home-features" class="chip-link">Ce que le site fait mieux</a>
                <a href="#home-parks" class="chip-link">Lire les deux parcs</a>
                <a href="#home-news" class="chip-link">Actus</a>
            </nav>
        </div>
    </section>

    <section class="shell section-shell" id="home-features">
        <div class="section-head">
            <p class="eyebrow">Lecture resort</p>
            <h2>Deux parcs, deux rythmes, deux promesses de visite.</h2>
            <p>
                Le site doit aider a passer de l un a l autre sans confusion:
                priorites, pauses, spectacles, restauration et nouvelles zones ont besoin d une lecture differente.
            </p>
        </div>

        <div class="feature-grid">
            <?php foreach (array_slice($home_features, 0, 4) as $feature) : ?>
                <article class="feature-card">
                    <span class="pill soft-gold"><?php echo e($feature['tag']); ?></span>
                    <h3><?php echo e($feature['title']); ?></h3>
                    <p><?php echo e($feature['copy']); ?></p>
                    <a href="<?php echo e($feature['href']); ?>"><?php echo e($feature['cta']); ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="home-parks">
        <div class="section-head">
            <p class="eyebrow">Les deux parcs</p>
            <h2>Une lecture claire pour choisir le bon rythme de visite.</h2>
            <p>Chaque parc a ses priorites, ses respirations et ses bons reflexes. La page d accueil doit deja poser cette difference.</p>
        </div>

        <div class="park-grid">
            <?php foreach ($parkCards as $park) : ?>
                <article class="park-card">
                    <div class="card-row">
                        <span class="pill soft-blue"><?php echo e($park['name']); ?></span>
                        <span class="pill soft-green"><?php echo e((string) $park['open_count']); ?> ouvertes</span>
                    </div>
                    <h3><?php echo e($park['headline']); ?></h3>
                    <p><?php echo e($park['focus']); ?></p>
                    <small class="quiet-note">
                        <?php echo $park['avg_wait'] !== null ? e((string) $park['avg_wait']) . ' min de moyenne live' : 'Moyenne live non disponible'; ?>
                    </small>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell" id="home-news">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Actus et lecture fan</p>
                <h2>Les sujets qui donnent envie de rester sur le site.</h2>
            </div>
            <a class="btn btn-secondary" href="news.php">Voir toutes les actus</a>
        </div>

        <?php if ($featuredArticle) : ?>
            <div class="story-grid">
                <article class="story-card story-card--feature">
                    <img class="card-cover" src="<?php echo e(articleEditorialImage($featuredArticle)); ?>" alt="<?php echo e($featuredArticle['title']); ?>">
                    <div class="card-row">
                        <span class="pill soft-gold"><?php echo e($featuredArticle['category']); ?></span>
                        <span class="pill soft-blue"><?php echo e($featuredArticle['park']); ?></span>
                    </div>
                    <h3><?php echo e($featuredArticle['title']); ?></h3>
                    <p><?php echo e($featuredArticle['excerpt']); ?></p>
                    <div class="card-row">
                        <small class="quiet-note"><?php echo e($featuredArticle['date']); ?></small>
                        <small class="quiet-note"><?php echo e($featuredArticle['reading_time']); ?></small>
                    </div>
                    <a href="article.php?slug=<?php echo urlencode($featuredArticle['slug']); ?>">Lire l article</a>
                </article>

                <div class="stack-grid">
                    <?php foreach (array_slice($newsSpotlights, 1, 3) as $article) : ?>
                        <article class="mini-surface">
                            <div class="card-row">
                                <span class="pill soft-gold"><?php echo e($article['category']); ?></span>
                                <span class="pill soft-blue"><?php echo e($article['park']); ?></span>
                            </div>
                            <h3><?php echo e($article['title']); ?></h3>
                            <p><?php echo e($article['excerpt']); ?></p>
                            <div class="card-row">
                                <small class="quiet-note"><?php echo e($article['date']); ?></small>
                                <small class="quiet-note"><?php echo e($article['reading_time']); ?></small>
                            </div>
                            <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>">Ouvrir</a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

</main>
<?php renderFooter($site, $footer_links, ['assets/js/pages/home.js']); ?>
