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
    $navLookup = [];
    foreach ($navItems as $item) {
        $navLookup[$item['id']] = $item;
    }

    $navGroups = [
        ['id' => 'home', 'label' => 'Accueil', 'items' => ['home']],
        ['id' => 'explore', 'label' => 'Explorer', 'items' => ['wait-times', 'insights', 'shows', 'news', 'secrets']],
        ['id' => 'food', 'label' => 'Manger', 'items' => ['food']],
        ['id' => 'community', 'label' => 'Communaute', 'items' => ['community']],
        ['id' => 'practical', 'label' => 'Pratique', 'items' => ['practical', 'maintenance']],
        ['id' => 'visit', 'label' => 'Visite', 'items' => ['planner', 'about']],
    ];

    $currentGroup = 'home';
    foreach ($navGroups as $group) {
        if (in_array($currentPage, $group['items'], true)) {
            $currentGroup = $group['id'];
            break;
        }
    }

    $currentSubnav = [];
    foreach ($navGroups as $group) {
        if ($group['id'] === $currentGroup) {
            $currentSubnav = $group['items'];
            break;
        }
    }

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
            <ul class="nav-groups">
                <?php foreach ($navGroups as $group) : ?>
                    <?php
                    $firstItemId = $group['items'][0];
                    $firstItem = $navLookup[$firstItemId] ?? null;
                    if (!$firstItem) {
                        continue;
                    }
                    ?>
                    <li>
                        <a href="<?php echo e($firstItem['href']); ?>" class="nav-group-link <?php echo $currentGroup === $group['id'] ? 'is-active' : ''; ?>">
                            <?php echo e($group['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</header>
<?php if (count($currentSubnav) > 1) : ?>
    <div class="subnav-bar">
        <div class="shell subnav-shell">
            <span class="subnav-label"><?php echo e($currentGroup === 'explore' ? 'Explorer' : ($currentGroup === 'practical' ? 'Pratique' : 'Visite')); ?></span>
            <nav class="subnav-links" aria-label="Sous-navigation">
                <?php foreach ($currentSubnav as $itemId) : ?>
                    <?php if (!isset($navLookup[$itemId])) {
                        continue;
                    } ?>
                    <a href="<?php echo e($navLookup[$itemId]['href']); ?>" class="<?php echo $currentPage === $itemId ? 'is-active' : ''; ?>">
                        <?php echo e($navLookup[$itemId]['label']); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
<?php endif; ?>
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
