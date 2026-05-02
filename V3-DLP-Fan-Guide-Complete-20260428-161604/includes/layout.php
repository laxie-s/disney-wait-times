<?php
function renderHead($title, $description, $site, array $extraStyles = [])
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
    <?php foreach ($extraStyles as $href) : ?>
        <link rel="stylesheet" href="<?php echo e($href); ?>">
    <?php endforeach; ?>
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
    $currentGroupLabel = 'Accueil';
    foreach ($navGroups as $group) {
        if ($group['id'] === $currentGroup) {
            $currentSubnav = $group['items'];
            $currentGroupLabel = $group['label'];
            break;
        }
    }

    ?>
<body data-page="<?php echo e($currentPage); ?>">
<header id="main-header">
    <div class="shell header-shell">
        <div class="header-top-row">
            <a class="brand brand-centered" href="index.php" aria-label="<?php echo e($site['name']); ?>">
                <span class="brand-copy brand-copy-centered">
                    <strong><?php echo e($site['name']); ?></strong>
                    <small>Disneyland Paris, lu avec un regard fan, clair et utile</small>
                </span>
            </a>
        </div>

        <div class="header-nav-row">
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
    </div>
</header>
<?php if (count($currentSubnav) > 1) : ?>
    <div class="subnav-bar">
        <div class="shell subnav-shell">
            <span class="subnav-label"><?php echo e($currentGroupLabel); ?></span>
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

function renderFooter($site, $footerLinks, array $extraScripts = [])
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
<script src="assets/js/layout.js" defer></script>
<?php foreach ($extraScripts as $src) : ?>
    <script src="<?php echo e($src); ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
<?php
}
