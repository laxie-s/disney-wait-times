<?php
include('data.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($site_name); ?> - Guide fan premium pour Disneyland Paris</title>
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
                <li><a href="wait-times.php">Temps d attente</a></li>
                <li><a href="#actus">Ce qu on suit</a></li>
                <li><a href="#secrets">Details caches</a></li>
                <li><a href="#guide">Positionnement</a></li>
            </ul>
        </nav>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">Temps live, conseils terrain et regard fan</p>
            <h1>Un guide Disneyland Paris qui fait tres pro sans jouer au site officiel.</h1>
            <p class="hero-copy">
                Ton vrai angle fort, c est la confiance: une lecture rapide des temps d attente,
                des conseils concrets pour la journee et des details de parc qu un visiteur ne voit
                pas toujours du premier coup.
            </p>

            <div class="hero-actions">
                <a class="btn btn-primary" href="wait-times.php">Voir le tableau live</a>
                <a class="btn btn-secondary" href="#actus">Structurer le contenu</a>
            </div>

            <div class="hero-tags" aria-label="Points forts du guide">
                <span>Lecture mobile rapide</span>
                <span>Actus terrain</span>
                <span>Secrets par land</span>
                <span>Base pour future appli</span>
            </div>
        </div>

        <div class="hero-visual" aria-label="Apercu du tableau de bord visiteur">
            <div class="live-panel">
                <div class="panel-header">
                    <div>
                        <span class="panel-label">Etat du parc</span>
                        <strong class="panel-title"><?php echo $live_is_available ? 'Flux live actif' : 'Mode de secours'; ?></strong>
                    </div>
                    <span class="status-pill <?php echo $live_is_available ? 'is-live' : 'is-offline'; ?>">
                        <?php echo e($sync_short_label); ?>
                    </span>
                </div>

                <div class="panel-grid">
                    <div>
                        <span><?php echo $open_count ?: '--'; ?></span>
                        <small>ouvertes</small>
                    </div>
                    <div>
                        <span><?php echo $avg_wait !== null ? e($avg_wait) : '--'; ?></span>
                        <small>min moyen</small>
                    </div>
                    <div>
                        <span><?php echo e($closed_count); ?></span>
                        <small>fermees</small>
                    </div>
                    <div>
                        <span><?php echo e($best_land_name ?? '--'); ?></span>
                        <small>land a regarder</small>
                    </div>
                </div>

                <div class="spotlight-card">
                    <div>
                        <span class="panel-label">Meilleur pari du moment</span>
                        <h2><?php echo $best_pick ? e($best_pick['name']) : 'En attente de synchronisation'; ?></h2>
                        <p>
                            <?php if ($best_pick) : ?>
                                <?php echo e($best_pick['wait_time']); ?> min dans <?php echo e($best_pick['land']); ?>.
                            <?php else : ?>
                                Les cartes restent consultables meme si le flux live ne remonte pas.
                            <?php endif; ?>
                        </p>
                    </div>
                    <a href="wait-times.php">Ouvrir le detail</a>
                </div>

                <div class="queue-preview">
                    <?php if (!empty($top_picks)) : ?>
                        <?php foreach ($top_picks as $item) : ?>
                            <article class="queue-row">
                                <div>
                                    <strong><?php echo e($item['name']); ?></strong>
                                    <small><?php echo e($item['land']); ?></small>
                                </div>
                                <span><?php echo e($item['wait_time']); ?> min</span>
                            </article>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <article class="queue-row empty-row">
                            <div>
                                <strong>Synchronisation indisponible</strong>
                                <small>Le guide reste pret pour les contenus editoriaux et les fiches lands.</small>
                            </div>
                        </article>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="signal-strip" aria-label="Indicateurs principaux">
        <article>
            <span><?php echo e($total_attractions); ?></span>
            <p>attractions suivies dans la base</p>
        </article>
        <article>
            <span><?php echo e($best_land_name ?? '--'); ?></span>
            <p>land le plus exploitable en ce moment</p>
        </article>
        <article>
            <span><?php echo $peak_pick ? e($peak_pick['wait_time']) . ' min' : '--'; ?></span>
            <p>plus grosse attente visible</p>
        </article>
        <article>
            <span><?php echo e($sync_short_label); ?></span>
            <p>etat de la synchronisation sur cette page</p>
        </article>
    </section>

    <section id="actus" class="content-band">
        <div class="section-heading">
            <p class="eyebrow">Ce qu on suit</p>
            <h2>Le site gagne en credibilite quand il aide tout de suite, sans surjouer l officiel.</h2>
            <p>
                L objectif n est pas de copier Disney. L objectif est de proposer une experience plus
                rapide a lire, plus nette et plus proche de ce qu un visiteur veut savoir au bon moment.
            </p>
        </div>

        <div class="feature-grid">
            <?php foreach ($content_highlights as $card) : ?>
                <article class="feature-card">
                    <span class="feature-kicker"><?php echo e($card['tag']); ?></span>
                    <h3><?php echo e($card['title']); ?></h3>
                    <p><?php echo e($card['copy']); ?></p>
                    <a href="<?php echo e($card['href']); ?>"><?php echo e($card['cta']); ?></a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="content-band compact">
        <div class="section-heading">
            <p class="eyebrow">Priorites live</p>
            <h2>Les opportunites qui meritent le premier coup d oeil.</h2>
        </div>

        <div class="pick-grid">
            <?php if (!empty($top_picks)) : ?>
                <?php foreach ($top_picks as $item) : ?>
                    <article class="pick-card">
                        <div class="pick-card-top">
                            <span class="wait-badge"><?php echo e($item['wait_time']); ?> min</span>
                            <span class="mini-land"><?php echo e($item['land']); ?></span>
                        </div>
                        <h3><?php echo e($item['name']); ?></h3>
                        <p><?php echo e($item['summary']); ?></p>
                        <a href="wait-times.php?land=<?php echo urlencode($item['land']); ?>">Voir dans le land</a>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <article class="empty-state">
                    <h3>Flux live momentanement indisponible</h3>
                    <p>Le site garde sa structure premium, et les pages restent prêtes pour accueillir les prochaines donnees.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>

    <section id="lands" class="content-band">
        <div class="section-heading">
            <p class="eyebrow">Lands</p>
            <h2>Un point d entree par zone, plus naturel pour des visiteurs en preparation.</h2>
        </div>

        <div class="land-grid">
            <?php foreach ($land_summaries as $landName => $summary) : ?>
                <a href="wait-times.php?land=<?php echo urlencode($landName); ?>" class="land-card">
                    <div class="land-card-head">
                        <span class="land-badge"><?php echo e(initials($landName)); ?></span>
                        <span class="land-chip"><?php echo e($summary['open_count']); ?> ouvertes</span>
                    </div>
                    <h3><?php echo e($landName); ?></h3>
                    <p><?php echo e($summary['count']); ?> experiences suivies, avec synthese et lecture terrain.</p>
                    <div class="land-meta">
                        <span><?php echo $summary['avg_wait'] !== null ? e($summary['avg_wait']) . ' min moyen' : 'Attente a confirmer'; ?></span>
                        <span>
                            <?php if (!empty($summary['best_pick'])) : ?>
                                Pick: <?php echo e($summary['best_pick']['name']); ?>
                            <?php else : ?>
                                Pas de pick live
                            <?php endif; ?>
                        </span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="secrets" class="content-band alt-band">
        <div class="section-heading">
            <p class="eyebrow">Details caches</p>
            <h2>Le vrai territoire d un fan site: raconter ce que le visiteur ne voit pas encore.</h2>
        </div>

        <div class="insight-grid">
            <?php foreach ($easter_egg_cards as $card) : ?>
                <article class="insight-card">
                    <span class="feature-kicker"><?php echo e($card['land']); ?></span>
                    <h3><?php echo e($card['title']); ?></h3>
                    <p><?php echo e($card['copy']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="timeline" class="content-band">
        <div class="section-heading">
            <p class="eyebrow">Rythme visite</p>
            <h2>Une ligne editoriale pensee pour la vraie journee d un visiteur.</h2>
        </div>

        <div class="timeline-grid">
            <?php foreach ($visit_timeline as $step) : ?>
                <article class="timeline-card">
                    <span class="timeline-moment"><?php echo e($step['moment']); ?></span>
                    <h3><?php echo e($step['title']); ?></h3>
                    <p><?php echo e($step['copy']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="guide" class="promise-band">
        <div class="promise-copy">
            <p class="eyebrow">Positionnement</p>
            <h2>Professionnel, utile, distinct, et donc beaucoup plus defendable.</h2>
            <p>
                Je ne t aide pas a faire croire que tu es Disney. En revanche, je t aide a construire
                un site fan qui donne envie de revenir, qui rassure et qui pourrait tres bien te faire reperer
                pour la qualite du travail.
            </p>
            <p class="sync-copy"><?php echo e($sync_full_label); ?></p>
        </div>

        <div class="promise-grid">
            <?php foreach ($brand_principles as $item) : ?>
                <article class="promise-card">
                    <h3><?php echo e($item['title']); ?></h3>
                    <p><?php echo e($item['copy']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer>
    <strong><?php echo e($site_name); ?></strong>
    <span><?php echo e($legal_notice); ?></span>
</footer>

</body>
</html>