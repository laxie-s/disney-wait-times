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
                <p>Le lien demande ne correspond a aucun article de cette version du site.</p>
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
    </section>

    <section class="shell section-shell tight-top">
        <div class="article-layout">
            <article class="article-body">
                <?php foreach ($article['body'] as $paragraph) : ?>
                    <p><?php echo e($paragraph); ?></p>
                <?php endforeach; ?>
            </article>
            <aside class="article-side">
                <div class="side-card">
                    <span class="meta-label">A retenir</span>
                    <ul class="mini-list">
                        <?php foreach ($article['points'] as $point) : ?>
                            <li><?php echo e($point); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
