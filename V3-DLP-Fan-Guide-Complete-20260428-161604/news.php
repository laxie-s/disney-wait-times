<?php
include 'includes/data.php';
include 'includes/layout.php';

$park_choisi = $_GET['park'] ?? null;
$allowed_news_parks = ['Disneyland Park', 'Disney Adventure World', 'Resort'];
if ($park_choisi !== null && !in_array($park_choisi, $allowed_news_parks, true)) {
    $park_choisi = null;
}

$category_choisie = $_GET['category'] ?? null;
$news_categories = [];
foreach ($news_articles as $article) {
    $news_categories[$article['category']] = $article['category'];
}
ksort($news_categories);
if ($category_choisie !== null && !isset($news_categories[$category_choisie])) {
    $category_choisie = null;
}

$visible_articles = array_values(array_filter($news_articles, function ($article) use ($park_choisi) {
    return $park_choisi === null || ($article['park'] ?? 'Resort') === $park_choisi;
}));

$visible_articles = array_values(array_filter($visible_articles, function ($article) use ($category_choisie) {
    return $category_choisie === null || ($article['category'] ?? null) === $category_choisie;
}));

if (empty($visible_articles)) {
    $visible_articles = $news_articles;
}

$visible_category_counts = [];
foreach ($visible_articles as $article) {
    $category = $article['category'] ?? 'General';
    if (!isset($visible_category_counts[$category])) {
        $visible_category_counts[$category] = 0;
    }
    $visible_category_counts[$category]++;
}

$featuredArticle = $visible_articles[0];
$remainingArticles = array_slice($visible_articles, 1);

renderHead('Actus', 'Actus fan, lecture resort et vision produit autour de Disneyland Paris.', $site);
renderHeader('news', $site, $nav_items);
?>
<main class="page-main">
    <section class="shell page-hero">
        <div class="section-head">
            <p class="eyebrow">Actus et regard fan</p>
            <h1>Une rubrique editoriale pour suivre un resort qui change vite.</h1>
            <p>Le retour en force du deuxieme parc change la maniere de raconter la destination. On peut maintenant lire les actus par parc, par rubrique et par angle.</p>
        </div>

        <nav class="chip-nav" aria-label="Filtre par parc">
            <a href="news.php<?php echo $category_choisie ? '?' . http_build_query(['category' => $category_choisie]) : ''; ?>" class="chip-link <?php echo $park_choisi === null ? 'active' : ''; ?>">Tout</a>
            <?php foreach ($allowed_news_parks as $parkName) : ?>
                <a href="news.php?<?php echo e(http_build_query(array_filter(['park' => $parkName, 'category' => $category_choisie]))); ?>" class="chip-link <?php echo $park_choisi === $parkName ? 'active' : ''; ?>">
                    <?php echo e($parkName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <nav class="chip-nav secondary" aria-label="Filtre par rubrique">
            <a href="news.php<?php echo $park_choisi ? '?' . http_build_query(['park' => $park_choisi]) : ''; ?>" class="chip-link <?php echo $category_choisie === null ? 'active' : ''; ?>">Toutes les rubriques</a>
            <?php foreach ($news_categories as $categoryName) : ?>
                <a href="news.php?<?php echo e(http_build_query(array_filter(['park' => $park_choisi, 'category' => $categoryName]))); ?>" class="chip-link <?php echo $category_choisie === $categoryName ? 'active' : ''; ?>">
                    <?php echo e($categoryName); ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="page-anchor-wrap">
            <span class="meta-label">Sommaire de la page</span>
            <nav class="chip-nav secondary page-anchor-nav" aria-label="Sommaire actus">
                <a href="#news-categories" class="chip-link">Rubriques</a>
                <a href="#news-featured" class="chip-link">A la une</a>
                <a href="#news-list" class="chip-link">Toutes les actus</a>
            </nav>
        </div>

        <div class="feature-grid compact-feature-grid" id="news-categories">
            <?php foreach (array_slice($visible_category_counts, 0, 4, true) as $categoryName => $count) : ?>
                <article class="feature-card">
                    <span class="pill soft-gold"><?php echo e($count); ?> article<?php echo $count > 1 ? 's' : ''; ?></span>
                    <h3><?php echo e($categoryName); ?></h3>
                    <p>Une porte d entree utile si tu veux suivre ce sujet sans passer par toute la grille d articles.</p>
                    <a href="news.php?<?php echo e(http_build_query(array_filter(['park' => $park_choisi, 'category' => $categoryName]))); ?>">Ouvrir la rubrique</a>
                </article>
            <?php endforeach; ?>
        </div>

        <article class="hero-article news-featured-story" id="news-featured">
            <div class="card-row">
                <span class="pill soft-blue"><?php echo e($featuredArticle['category']); ?></span>
                <span class="pill soft-gold"><?php echo e($featuredArticle['park']); ?></span>
            </div>
            <h2><?php echo e($featuredArticle['title']); ?></h2>
            <p><?php echo e($featuredArticle['excerpt']); ?></p>
            <div class="story-meta">
                <span><?php echo e($featuredArticle['date']); ?></span>
                <span><?php echo e($featuredArticle['reading_time']); ?></span>
            </div>
            <a class="btn btn-primary" href="article.php?slug=<?php echo urlencode($featuredArticle['slug']); ?>">Lire l article</a>
        </article>
    </section>

    <section class="shell section-shell tight-top" id="news-list">
        <div class="story-grid">
            <?php foreach ($remainingArticles as $article) : ?>
                <article class="story-card news-story-card">
                    <div class="card-row">
                        <span class="pill soft-gold"><?php echo e($article['cover_label']); ?></span>
                        <span class="pill soft-blue"><?php echo e($article['park']); ?></span>
                        <span class="story-date"><?php echo e($article['date']); ?></span>
                    </div>
                    <h3><?php echo e($article['title']); ?></h3>
                    <p><?php echo e($article['excerpt']); ?></p>
                    <div class="story-meta">
                        <span><?php echo e($article['category']); ?></span>
                        <span><?php echo e($article['reading_time']); ?></span>
                    </div>
                    <a href="article.php?slug=<?php echo urlencode($article['slug']); ?>">Ouvrir</a>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php renderFooter($site, $footer_links); ?>
