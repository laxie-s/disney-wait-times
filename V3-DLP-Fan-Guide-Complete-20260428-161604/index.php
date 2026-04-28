<?php
include 'includes/data.php';
include 'includes/layout.php';

$featuredArticle = $news_articles[0];
$secondaryArticles = array_slice($news_articles, 1, 2);
$adventure_world_food = array_values(array_filter($dining_spots, function ($spot) {
    return $spot['park'] === 'Disney Adventure World';
}));
$adventure_world_picks = array_values(array_filter($top_picks, function ($item) {
    return $item['park'] === 'Disney Adventure World';
}));

renderHead('Accueil', $site['meta_description'], $site);
renderHeader('home', $site, $nav_items);
?>
<main class="page-main">
    <section class="hero-band">
        <div class="shell hero-shell">
            <div class="hero-copy">
                <p class="eyebrow">Deux parcs, temps live, food, stats et secrets</p>
                <h1>Le guide fan qui aide maintenant sur Disneyland Park et Disney Adventure World.</h1>
                <p class="lead">
                    Une base plus complete pour suivre les temps d attente, mieux lire les differences entre les deux parcs,
                    organiser sa restauration et garder une vraie couche fan sur tout le resort.
                </p>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="wait-times.php">Ouvrir le tableau live</a>
                    <a class="btn btn-secondary" href="planner.php">Preparer sa visite</a>
                </div>

                <div class="tag-row">
                    <span>Disneyland Park</span>
                    <span>Disney Adventure World</span>
                    <span>Site fan assume</span>
                    <span>Base pour future appli</span>
                </div>
            </div>

            <div class="hero-visual">
                <img src="assets/images/hero-dual-park.png" alt="Illustration fan de deux univers de parc a theme">

                <div class="glass-panel">
                    <div class="panel-top">
                        <div>
                            <span class="meta-label">Etat du site</span>
                            <strong><?php echo $live_is_available ? 'Flux live resort actif' : 'Mode de secours'; ?></strong>
                        </div>
                        <span class="status-pill <?php echo $live_is_available ? 'live' : 'offline'; ?>">
                            <?php echo e($sync_short_label); ?>
                        </span>
                    </div>

                    <div class="mini-stats">
                        <article>
                            <span><?php echo $open_count ?: '--'; ?></span>
                            <small>ouvertes</small>
                        </article>
                        <article>
                            <span><?php echo $avg_wait !== null ? e($avg_wait) : '--'; ?></span>
                            <small>min moyen</small>
                        </article>
                        <article>
                            <span><?php echo e($best_park_name ?? '--'); ?></span>
                            <small>parc fort</small>
                        </article>
                    </div>

                    <div class="panel-highlight">
                        <span class="meta-label">Pick du moment</span>
                        <h2><?php echo $best_pick ? e($best_pick['name']) : 'En attente de synchronisation'; ?></h2>
                        <p>
                            <?php if ($best_pick) : ?>
                                <?php echo e($best_pick['wait_time']); ?> min dans <?php echo e($best_pick['park']); ?>.
                            <?php else : ?>
                                Le site garde ses fiches et ses conseils meme sans live.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="shell metric-shell">
        <div class="metric-grid">
            <article class="metric-card">
                <span>2</span>
                <p>parcs lus dans la meme base fan</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($total_attractions); ?></span>
                <p>attractions suivies dans le resort</p>
            </article>
            <article class="metric-card">
                <span><?php echo $peak_pick ? e($peak_pick['wait_time']) . ' min' : '--'; ?></span>
                <p>plus grosse attente visible</p>
            </article>
            <article class="metric-card">
                <span><?php echo e($sync_short_label); ?></span>
                <p>etat de la synchronisation</p>
            </article>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="section-head">
            <p class="eyebrow">Choisir son parc</p>
            <h2>Deux parcs, deux rythmes, deux promesses de visite.</h2>
            <p>
                Le site doit maintenant aider les visiteurs a passer de l un a l autre sans confusion.
                Disneyland Park garde ses icones et ses lands. Disney Adventure World demande une lecture plus modulaire.
            </p>
        </div>

        <div class="park-grid">
            <?php foreach ($park_profiles as $parkName => $profile) : ?>
                <?php $summary = $park_summaries[$parkName]; ?>
                <article class="park-card">
                    <div class="card-row">
                        <span class="land-mark"><?php echo e(initials($parkName)); ?></span>
                        <span class="pill soft-blue"><?php echo e($summary['count']); ?> attractions</span>
                    </div>
                    <h3><?php echo e($parkName); ?></h3>
                    <p><?php echo e($profile['headline']); ?></p>
                    <ul class="mini-list">
                        <li><?php echo e($profile['focus']); ?></li>
                        <li><?php echo $summary['avg_wait'] !== null ? e($summary['avg_wait']) . ' min de moyenne live' : 'Moyenne a confirmer'; ?></li>
                    </ul>
                    <div class="card-actions">
                        <a href="wait-times.php?park=<?php echo urlencode($parkName); ?>">Temps d attente</a>
                        <a href="food.php?park=<?php echo urlencode($parkName); ?>">Restauration</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Disney Adventure World</p>
                <h2>Le deuxieme parc a maintenant sa propre lecture dans le site.</h2>
            </div>
            <a class="text-link" href="wait-times.php?park=<?php echo urlencode('Disney Adventure World'); ?>">Ouvrir le parc</a>
        </div>

        <div class="story-grid">
            <article class="story-card">
                <span class="pill soft-blue">Temps d attente</span>
                <h3><?php echo e($park_summaries['Disney Adventure World']['count']); ?> attractions deja suivies</h3>
                <p>Flight Force, Spider-Man, Crush, Ratatouille, Frozen Ever After et les nouvelles zones sont deja lisibles comme un vrai parc a part entiere.</p>
                <a href="wait-times.php?park=<?php echo urlencode('Disney Adventure World'); ?>">Voir les waits</a>
            </article>
            <article class="story-card">
                <span class="pill soft-gold">Restauration</span>
                <h3><?php echo e(count($adventure_world_food)); ?> adresses reperees dans le deuxieme parc</h3>
                <p>Regal View, Nordic Crowns Tavern et PYM Kitchen sont integres dans la couche resto du site.</p>
                <a href="food.php?park=<?php echo urlencode('Disney Adventure World'); ?>">Voir les restos</a>
            </article>
            <article class="story-card">
                <span class="pill soft-green">Focus fan</span>
                <h3><?php echo !empty($adventure_world_picks) ? e($adventure_world_picks[0]['name']) : 'World of Frozen et Pixar en focus'; ?></h3>
                <p>Le parc ne sert plus de simple complement. Il devient un terrain editorial, pratique et communautaire a part entiere.</p>
                <a href="secrets.php?park=<?php echo urlencode('Disney Adventure World'); ?>">Voir les secrets</a>
            </article>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="section-head">
            <p class="eyebrow">Ce que le site fait mieux</p>
            <h2>Une vraie lecture resort, pas juste une copie de chiffres.</h2>
        </div>

        <div class="feature-grid">
            <?php foreach ($home_features as $feature) : ?>
                <article class="feature-card">
                    <span class="pill soft-gold"><?php echo e($feature['tag']); ?></span>
                    <h3><?php echo e($feature['title']); ?></h3>
                    <p><?php echo e($feature['copy']); ?></p>
                    <a href="<?php echo e($feature['href']); ?>">Voir plus</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Bonnes opportunites</p>
                <h2>Les cartes a regarder en premier.</h2>
            </div>
            <a class="text-link" href="wait-times.php">Voir tout le tableau</a>
        </div>

        <div class="opportunity-grid">
            <?php if (!empty($top_picks)) : ?>
                <?php foreach (array_slice($top_picks, 0, 4) as $item) : ?>
                    <article class="opportunity-card">
                        <div class="card-row">
                            <span class="pill soft-green"><?php echo e($item['wait_time']); ?> min</span>
                            <span class="pill soft-blue"><?php echo e($item['park']); ?></span>
                        </div>
                        <h3><?php echo e($item['name']); ?></h3>
                        <p><?php echo e($item['summary']); ?></p>
                        <small><?php echo e($item['tip']); ?></small>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <article class="empty-card">
                    <h3>Le live ne remonte pas pour le moment</h3>
                    <p>Le site reste complet pour les contenus, les lands et la preparation de visite.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <section class="shell section-shell">
        <div class="section-head inline-head">
            <div>
                <p class="eyebrow">Actus</p>
                <h2>Une vraie couche editoriale pour suivre la destination qui change.</h2>
            </div>
            <a class="text-link" href="news.php">Toutes les actus</a>
        </div>

        <div class="story-grid news-home-grid">
            <article class="featured-story featured-story-compact">
                <div class="card-row">
                    <span class="pill soft-blue"><?php echo e($featuredArticle['category']); ?></span>
                    <span class="pill soft-gold"><?php echo e($featuredArticle['park']); ?></span>
                </div>
                <h3><?php echo e($featuredArticle['title']); ?></h3>
                <p><?php echo e($featuredArticle['excerpt']); ?></p>
                <div class="story-meta">
                    <span><?php echo e($featuredArticle['date']); ?></span>
                    <span><?php echo e($featuredArticle['reading_time']); ?></span>
                </div>
                <a href="article.php?slug=<?php echo urlencode($featuredArticle['slug']); ?>">Lire l article</a>
            </article>

            <?php foreach ($secondaryArticles as $article) : ?>
                <article class="story-card">
                    <div class="card-row">
                        <span class="pill soft-gold"><?php echo e($article['cover_label']); ?></span>
                        <span class="pill soft-blue"><?php echo e($article['park']); ?></span>
                    </div>
                    <h3><?php echo e($article['title']); ?></h3>
                    <p><?php echo e($article['excerpt']); ?></p>
                    <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>">Ouvrir</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
