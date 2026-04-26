<?php
function renderHead($title, $description, $site)
{
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?> - <?php echo e($site['name']); ?></title>
    <meta name="description" content="<?php echo e($description ?: $site['meta_description']); ?>">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php
}

function renderHeader($currentPage, $site, $navItems)
{
    ?>
<body data-page="<?php echo e($currentPage); ?>">
<div class="notice-bar">
    <?php echo e($site['legal_notice']); ?>
</div>

<header id="main-header">
    <div class="shell header-shell">
        <a class="brand" href="index.php" aria-label="<?php echo e($site['name']); ?>">
            <span class="brand-mark">DG</span>
            <span class="brand-copy">
                <strong><?php echo e($site['name']); ?></strong>
                <small>Guide fan independant</small>
            </span>
        </a>

        <nav class="main-nav" aria-label="Navigation principale">
            <ul>
                <?php foreach ($navItems as $item) : ?>
                    <li>
                        <a href="<?php echo e($item['href']); ?>" class="<?php echo $currentPage === $item['id'] ? 'is-active' : ''; ?>">
                            <?php echo e($item['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
<?php
}

function renderFooter($site, $footerLinks)
{
    ?>
<footer class="site-footer">
    <div class="shell footer-shell">
        <div class="footer-brand">
            <strong><?php echo e($site['name']); ?></strong>
            <p><?php echo e($site['legal_notice']); ?></p>
        </div>

        <div class="footer-links">
            <?php foreach ($footerLinks as $link) : ?>
                <a href="<?php echo e($link['href']); ?>"><?php echo e($link['label']); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</footer>
<script src="assets/js/app.js" defer></script>
</body>
</html>
<?php
}

