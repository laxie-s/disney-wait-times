<?php
include 'includes/data.php';
include 'includes/layout.php';

$slug = $_GET['slug'] ?? '';
$article = findNewsArticle($slug, $news_articles);

if (!$article) {
    http_response_code(404);
    renderHead('Article introuvable', 'Article introuvable sur le site fan.', $site);
    renderHeader('news', $site, $nav_items);
    ?>
    <main class="page-main">
        <section class="shell page-hero">
            <article class="empty-card">
                <h1>Article introuvable</h1>
                <p>Le lien demande ne correspond a aucun article actuellement disponible.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="news.php">Retour aux actus</a>
                    <a class="btn btn-secondary" href="index.php">Retour a l accueil</a>
                </div>
            </article>
        </section>
    </main>
    <?php
    renderFooter($site, $footer_links);
    return;
}

renderHead($article['title'], $article['excerpt'], $site);
renderHeader('news', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <article class="article-hero">
            <div class="card-row">
                <span class="pill soft-blue"><?php echo e($article['category']); ?></span>
                <span class="pill soft-gold"><?php echo e($article['park']); ?></span>
                <span class="story-date"><?php echo e($article['date']); ?></span>
            </div>
            <h1><?php echo e($article['title']); ?></h1>
            <p class="lead"><?php echo e($article['excerpt']); ?></p>
            <div class="story-meta">
                <span><?php echo e($article['reading_time']); ?></span>
                <span>Par DLP Fan Guide</span>
            </div>
        </article>

        <div class="page-anchor-wrap">
            <span class="meta-label">Sommaire de la page</span>
            <nav class="chip-nav secondary page-anchor-nav" aria-label="Sommaire article">
                <a href="#article-reading" class="chip-link">Lecture</a>
                <a href="#article-points" class="chip-link">A retenir</a>
                <a href="#article-next" class="chip-link">Continuer</a>
            </nav>
        </div>
    </section>

    <section class="shell section-shell tight-top">
        <div class="article-layout">
            <article class="article-body" id="article-reading">
                <?php foreach ($article['body'] as $paragraph) : ?>
                    <p><?php echo e($paragraph); ?></p>
                <?php endforeach; ?>
            </article>
            <aside class="article-side">
                <div class="side-card">
                    <span class="meta-label">Repere article</span>
                    <ul class="mini-list">
                        <li><?php echo e($article['category']); ?></li>
                        <li><?php echo e($article['park']); ?></li>
                        <li><?php echo e($article['reading_time']); ?></li>
                    </ul>
                </div>
                <div class="side-card" id="article-points">
                    <span class="meta-label">A retenir</span>
                    <ul class="mini-list">
                        <?php foreach ($article['points'] as $point) : ?>
                            <li><?php echo e($point); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="side-card" id="article-next">
                    <span class="meta-label">Continuer</span>
                    <div class="stack-grid">
                        <a class="text-link" href="news.php?park=<?php echo urlencode($article['park']); ?>">Voir les actus de ce parc</a>
                        <a class="text-link" href="news.php?category=<?php echo urlencode($article['category']); ?>">Voir cette rubrique</a>
                        <a class="text-link" href="news.php">Retour a toutes les actus</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
