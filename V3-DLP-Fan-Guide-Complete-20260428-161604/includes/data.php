<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Paris');

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function normalizeName($value)
{
    $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '', $value);

    return $value ?? '';
}

function initials($value)
{
    preg_match_all('/\b([A-Za-z])/', (string) $value, $matches);
    if (!empty($matches[1])) {
        return strtoupper(implode('', array_slice($matches[1], 0, 2)));
    }

    return strtoupper(substr((string) $value, 0, 2));
}

function totalAttractions($lands)
{
    $total = 0;
    foreach ($lands as $attractions) {
        $total += count($attractions);
    }

    return $total;
}

function extractLiveAttractions($payload, &$results)
{
    if (!is_array($payload)) {
        return;
    }

    if (($payload['entityType'] ?? null) === 'ATTRACTION' && !empty($payload['name'])) {
        $waitTime = $payload['queue']['STANDBY']['waitTime'] ?? null;
        $results[$payload['name']] = is_numeric($waitTime) ? (int) $waitTime : null;
    }

    foreach ($payload as $value) {
        if (is_array($value)) {
            extractLiveAttractions($value, $results);
        }
    }
}

function buildLiveLookup($rawAttractions)
{
    $lookup = [];

    foreach ($rawAttractions as $name => $waitTime) {
        $key = normalizeName($name);
        if ($key === '') {
            continue;
        }

        $candidate = [
            'source_name' => $name,
            'wait_time' => is_numeric($waitTime) ? (int) $waitTime : null,
            'status' => is_numeric($waitTime) ? 'open' : 'closed',
        ];

        if (!isset($lookup[$key]) || $candidate['status'] === 'open') {
            $lookup[$key] = $candidate;
        }
    }

    return $lookup;
}

function resolveAttractionState($attraction, $liveLookup, $liveAvailable)
{
    $candidateNames = array_merge([$attraction['name']], $attraction['aliases'] ?? []);

    foreach ($candidateNames as $candidateName) {
        $key = normalizeName($candidateName);
        if ($key !== '' && isset($liveLookup[$key])) {
            return array_merge($attraction, [
                'status' => $liveLookup[$key]['status'],
                'wait_time' => $liveLookup[$key]['wait_time'],
                'live_name' => $liveLookup[$key]['source_name'],
            ]);
        }
    }

    return array_merge($attraction, [
        'status' => $liveAvailable ? 'untracked' : 'offline',
        'wait_time' => null,
        'live_name' => null,
    ]);
}

function waitUnitLabel($status)
{
    if ($status === 'open') {
        return 'MIN';
    }

    if ($status === 'closed') {
        return 'FERME';
    }

    return 'INFO';
}

function statusLabel($status)
{
    if ($status === 'open') {
        return 'Ouverte';
    }

    if ($status === 'closed') {
        return 'Fermee';
    }

    if ($status === 'offline') {
        return 'Flux indisponible';
    }

    return 'A verifier';
}

function findNewsArticle($slug, $articles)
{
    foreach ($articles as $article) {
        if (($article['slug'] ?? '') === $slug) {
            return $article;
        }
    }

    return null;
}

function attractionStorageKey($park, $name)
{
    return normalizeName((string) $park . '-' . (string) $name);
}

function storageDirectory()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
}

function readJsonStore($filename, $fallback)
{
    $path = storageDirectory() . DIRECTORY_SEPARATOR . $filename;
    if (!is_file($path)) {
        return $fallback;
    }

    $raw = @file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $fallback;
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : $fallback;
}

function writeJsonStore($filename, $payload)
{
    $directory = storageDirectory();
    if (!is_dir($directory)) {
        @mkdir($directory, 0777, true);
    }

    if (!is_dir($directory)) {
        return false;
    }

    $path = $directory . DIRECTORY_SEPARATOR . $filename;
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return @file_put_contents($path, $encoded, LOCK_EX) !== false;
}

function waitAlertProfileForAttraction($name, $profiles)
{
    if (isset($profiles[$name])) {
        return $profiles[$name];
    }

    $headliners = ['Big Thunder Mountain', 'Star Wars Hyperspace Mountain', "Peter Pan's Flight", 'Avengers Assemble: Flight Force', "Crush's Coaster", 'Frozen Ever After', 'The Twilight Zone Tower of Terror'];
    $families = ["it's a small world", 'Spider-Man W.E.B. Adventure', "Ratatouille : L'Aventure Totalement Toquee de Remy", 'Cars ROAD TRIP', 'Toy Soldiers Parachute Drop', 'Raiponce Tangled Spin', 'Orbitron'];

    if (in_array($name, $headliners, true)) {
        return ['target' => 30, 'great' => 20, 'typical' => 50];
    }

    if (in_array($name, $families, true)) {
        return ['target' => 20, 'great' => 10, 'typical' => 30];
    }

    return ['target' => 15, 'great' => 10, 'typical' => 22];
}

function recordWaitHistory($catalogue)
{
    if (empty($catalogue)) {
        return [];
    }

    $records = readJsonStore('wait-history.json', []);
    $lastRecord = !empty($records) ? $records[count($records) - 1] : null;
    $now = time();

    if (is_array($lastRecord) && !empty($lastRecord['timestamp'])) {
        $lastTimestamp = strtotime($lastRecord['timestamp']);
        if ($lastTimestamp !== false && ($now - $lastTimestamp) < 900) {
            return $records;
        }
    }

    $snapshot = [
        'timestamp' => date('c', $now),
        'date' => date('Y-m-d', $now),
        'hour' => (int) date('G', $now),
        'items' => [],
    ];

    foreach ($catalogue as $item) {
        $snapshot['items'][] = [
            'id' => attractionStorageKey($item['park'], $item['name']),
            'park' => $item['park'],
            'land' => $item['land'],
            'name' => $item['name'],
            'status' => $item['status'],
            'wait_time' => is_numeric($item['wait_time']) ? (int) $item['wait_time'] : null,
        ];
    }

    $records[] = $snapshot;
    $records = array_values(array_slice($records, -1600));
    writeJsonStore('wait-history.json', $records);

    return $records;
}

function buildWaitHistoryProfiles($records, $catalogue, $alertProfiles)
{
    $profiles = [];

    foreach ($catalogue as $item) {
        $key = attractionStorageKey($item['park'], $item['name']);
        $alertProfile = waitAlertProfileForAttraction($item['name'], $alertProfiles);

        $profiles[$key] = [
            'id' => $key,
            'name' => $item['name'],
            'park' => $item['park'],
            'samples' => 0,
            'days' => [],
            'avg_wait' => null,
            'min_wait' => null,
            'max_wait' => null,
            'target_wait' => $alertProfile['target'],
            'great_wait' => $alertProfile['great'],
            'typical_wait' => $alertProfile['typical'],
            'hourly' => [],
        ];
    }

    foreach ($records as $record) {
        $hour = isset($record['hour']) ? (int) $record['hour'] : null;
        $date = $record['date'] ?? null;

        if (!isset($record['items']) || !is_array($record['items'])) {
            continue;
        }

        foreach ($record['items'] as $item) {
            $key = $item['id'] ?? attractionStorageKey($item['park'] ?? '', $item['name'] ?? '');
            if (!isset($profiles[$key])) {
                continue;
            }

            if (($item['status'] ?? '') !== 'open' || !is_numeric($item['wait_time'] ?? null)) {
                continue;
            }

            $waitTime = (int) $item['wait_time'];
            $profiles[$key]['samples']++;
            if ($date) {
                $profiles[$key]['days'][$date] = true;
            }
            $profiles[$key]['min_wait'] = $profiles[$key]['min_wait'] === null ? $waitTime : min($profiles[$key]['min_wait'], $waitTime);
            $profiles[$key]['max_wait'] = $profiles[$key]['max_wait'] === null ? $waitTime : max($profiles[$key]['max_wait'], $waitTime);
            $profiles[$key]['avg_wait'] = $profiles[$key]['avg_wait'] === null
                ? $waitTime
                : $profiles[$key]['avg_wait'] + $waitTime;

            if ($hour !== null) {
                if (!isset($profiles[$key]['hourly'][$hour])) {
                    $profiles[$key]['hourly'][$hour] = ['sum' => 0, 'count' => 0, 'min' => null, 'max' => null];
                }

                $profiles[$key]['hourly'][$hour]['sum'] += $waitTime;
                $profiles[$key]['hourly'][$hour]['count']++;
                $profiles[$key]['hourly'][$hour]['min'] = $profiles[$key]['hourly'][$hour]['min'] === null ? $waitTime : min($profiles[$key]['hourly'][$hour]['min'], $waitTime);
                $profiles[$key]['hourly'][$hour]['max'] = $profiles[$key]['hourly'][$hour]['max'] === null ? $waitTime : max($profiles[$key]['hourly'][$hour]['max'], $waitTime);
            }
        }
    }

    foreach ($profiles as $key => $profile) {
        if ($profile['samples'] > 0 && $profile['avg_wait'] !== null) {
            $profiles[$key]['avg_wait'] = (int) round($profile['avg_wait'] / $profile['samples']);
            $profiles[$key]['days'] = count($profile['days']);

            foreach ($profile['hourly'] as $hour => $bucket) {
                $profiles[$key]['hourly'][$hour] = [
                    'avg' => (int) round($bucket['sum'] / max(1, $bucket['count'])),
                    'count' => $bucket['count'],
                    'min' => $bucket['min'],
                    'max' => $bucket['max'],
                ];
            }

            $profiles[$key]['typical_wait'] = $profiles[$key]['avg_wait'];
            $profiles[$key]['target_wait'] = min($profiles[$key]['target_wait'], max(10, (int) round($profiles[$key]['avg_wait'] * 0.62 / 5) * 5));
            $profiles[$key]['great_wait'] = min($profiles[$key]['great_wait'], max(5, (int) round($profiles[$key]['target_wait'] * 0.7 / 5) * 5));
        } else {
            $profiles[$key]['days'] = 0;
        }
    }

    return $profiles;
}

$site = [
    'name' => 'DLP Fan Guide',
    'tagline' => 'Guide fan independant pour preparer sa journee sur les deux parcs de Disneyland Paris',
    'legal_notice' => 'Site fan independant et non officiel, non affilie a Disneyland Paris, Euro Disney Associes S.A.S. ou The Walt Disney Company.',
    'meta_description' => 'Guide fan premium pour Disneyland Park et Disney Adventure World: temps d attente, restauration, stats, secrets et conseils de visite.',
];

$nav_items = [
    ['id' => 'home', 'label' => 'Accueil', 'href' => 'index.php'],
    ['id' => 'wait-times', 'label' => 'Temps d attente', 'href' => 'wait-times.php'],
    ['id' => 'food', 'label' => 'Restauration', 'href' => 'food.php'],
    ['id' => 'insights', 'label' => 'Stats', 'href' => 'insights.php'],
    ['id' => 'shows', 'label' => 'Shows', 'href' => 'shows.php'],
    ['id' => 'news', 'label' => 'Actus', 'href' => 'news.php'],
    ['id' => 'secrets', 'label' => 'Secrets', 'href' => 'secrets.php'],
    ['id' => 'community', 'label' => 'Communaute', 'href' => 'community.php'],
    ['id' => 'practical', 'label' => 'Sur place', 'href' => 'practical.php'],
    ['id' => 'maintenance', 'label' => 'Fermetures', 'href' => 'maintenance.php'],
    ['id' => 'planner', 'label' => 'Preparer sa visite', 'href' => 'planner.php'],
    ['id' => 'about', 'label' => 'A propos', 'href' => 'about.php'],
];

$footer_links = [
    ['label' => 'Mentions et clarte fan site', 'href' => 'legal.php'],
    ['label' => 'Disneyland Park', 'href' => 'wait-times.php?park=Disneyland+Park'],
    ['label' => 'Disney Adventure World', 'href' => 'wait-times.php?park=Disney+Adventure+World'],
    ['label' => 'Programme des shows', 'href' => 'shows.php'],
    ['label' => 'Restauration', 'href' => 'food.php'],
    ['label' => 'Communaute', 'href' => 'community.php'],
];

$park_profiles = [
    'Disneyland Park' => [
        'headline' => 'Le parc iconique, structure par ses lands et ses grands classiques.',
        'focus' => 'Ideal pour les priorites absolues, le vrai storytelling land par land et la gestion des grosses icones.',
    ],
    'Disney Adventure World' => [
        'headline' => 'Le deuxieme parc, transforme autour de Pixar, MARVEL, Frozen et des nouveaux parcours.',
        'focus' => 'Parfait pour les visiteurs qui veulent alterner sensations, experiences immersives et nouveaux repères depuis la renaissance du parc.',
    ],
];

$land_profiles = [
    'Frontierland' => [
        'park' => 'Disneyland Park',
        'headline' => 'Le land des grands recits et des transitions fortes.',
        'focus' => 'Tres bon terrain pour raconter la coherence narrative entre le decor, Phantom Manor et Big Thunder Mountain.',
    ],
    'Discoveryland' => [
        'park' => 'Disneyland Park',
        'headline' => 'Une science-fiction retro, plus litteraire que purement spatiale.',
        'focus' => 'Parfait pour melanger grosses attractions, observation du design et conseils de circulation.',
    ],
    'Fantasyland' => [
        'park' => 'Disneyland Park',
        'headline' => 'Le coeur familial, avec des priorites a lire finement.',
        'focus' => 'Un excellent terrain pour aider les visiteurs a arbitrer entre icones absolues et respiration.',
    ],
    'Adventureland' => [
        'park' => 'Disneyland Park',
        'headline' => 'Le land qui gagne a etre explore, pas seulement consomme.',
        'focus' => 'Ideal pour une rubrique details caches et pour recommander des pauses intelligentes.',
    ],
    'World Premiere Plaza' => [
        'park' => 'Disney Adventure World',
        'headline' => 'La facade la plus theatrale du second parc et un bon repere de bascule.',
        'focus' => 'Utile pour orienter les visiteurs entre l entree, Tower of Terror et les grands flux du parc.',
    ],
    'Marvel Avengers Campus' => [
        'park' => 'Disney Adventure World',
        'headline' => 'La zone la plus frontale en sensations et en energie.',
        'focus' => 'Aide beaucoup pour choisir le bon moment sur Flight Force ou Spider-Man W.E.B. Adventure.',
    ],
    'Worlds of Pixar' => [
        'park' => 'Disney Adventure World',
        'headline' => 'La zone la plus dense en attractions famille du second parc.',
        'focus' => 'Parfait pour les arbitrages famille, les pauses plus ludiques et la lecture des files moins intuitives.',
    ],
    'Adventure Way' => [
        'park' => 'Disney Adventure World',
        'headline' => 'La nouvelle promenade qui recoud le parc et donne un autre rythme de visite.',
        'focus' => 'Tres bon angle pour parler circulation, show nocturne et nouvelles habitudes de parcours.',
    ],
    'World of Frozen' => [
        'park' => 'Disney Adventure World',
        'headline' => 'La nouvelle vitrine immersive du parc.',
        'focus' => 'A suivre de pres pour les flux, la curiosite visiteur et la pression sur Frozen Ever After.',
    ],
];

$structure_land = [
    'Frontierland' => [
        ['name' => 'Big Thunder Mountain', 'aliases' => [], 'summary' => 'Headliner sensations a surveiller toute la journee.', 'tip' => 'Une baisse nette du temps d attente vaut souvent une decision rapide.'],
        ['name' => 'Phantom Manor', 'aliases' => [], 'summary' => 'Dark ride majeur relie au recit de Thunder Mesa.', 'tip' => 'Parfait pour introduire le lore de Frontierland sans alourdir la visite.'],
        ['name' => 'Thunder Mesa Riverboat Landing', 'aliases' => [], 'summary' => 'Pause paisible avec vues larges sur le land.', 'tip' => 'Tres utile pour relancer un groupe sans ajouter une grosse file.'],
        ['name' => 'Disneyland Railroad - Frontierland Depot', 'aliases' => ['Disneyland Railroad Frontierland Depot'], 'summary' => 'A la fois attraction et vrai outil de deplacement.', 'tip' => 'Bon levier pour sortir d une zone trop dense sans casser le rythme.'],
        ['name' => 'Frontierland Playground', 'aliases' => [], 'summary' => 'Pause famille pratique dans une journee chargee.', 'tip' => 'A recommander quand le groupe a besoin d une respiration courte.'],
        ['name' => "Rustler Roundup Shootin' Gallery", 'aliases' => [], 'summary' => 'Activite courte qui change du pur enchainement de waits.', 'tip' => 'Bonne transition entre deux experiences plus lourdes.'],
    ],
    'Discoveryland' => [
        ['name' => 'Star Wars Hyperspace Mountain', 'aliases' => [], 'summary' => 'La grosse cible sensations du land.', 'tip' => 'Quand la file se detend, mieux vaut agir vite car le pic remonte souvent fort.'],
        ['name' => 'Star Tours: The Adventures Continue', 'aliases' => ['Star Tours: The Adventures Continue*'], 'summary' => 'Simulateur qui peut devenir un excellent plan de repli.', 'tip' => 'Tres bon compromis quand on veut garder l energie du land sans tout ralentir.'],
        ['name' => 'Buzz Lightyear Laser Blast', 'aliases' => [], 'summary' => 'Valeur sure famille et competitivite legere.', 'tip' => 'A glisser facilement entre deux priorites plus tendues.'],
        ['name' => 'Les Mysteres du Nautilus', 'aliases' => ['Les Mysteres du Nautilus'], 'summary' => 'Walkthrough calme, dense visuellement et tres fan-compatible.', 'tip' => 'Excellent candidat pour un format details caches par scene.'],
        ['name' => 'Orbitron', 'aliases' => ['Orbitron'], 'summary' => 'Attraction courte avec point de vue utile sur la zone.', 'tip' => 'Le bon choix quand on veut du visuel sans trop immobiliser le groupe.'],
        ['name' => 'Autopia', 'aliases' => ['Autopia, presented by Avis'], 'summary' => 'Classique familial a lire avec un vrai contexte d attente.', 'tip' => 'A recommander surtout quand l intention famille est deja tres claire.'],
    ],
    'Fantasyland' => [
        ['name' => "Peter Pan's Flight", 'aliases' => [], 'summary' => 'Attraction iconique ou la strategie change vraiment la journee.', 'tip' => 'Une bonne fenetre sur Peter Pan peut te sauver beaucoup de temps.'],
        ['name' => "it's a small world", 'aliases' => ['"it\'s a small world"'], 'summary' => 'Tres bon repere confort quand le parc durcit.', 'tip' => 'Souvent utile pour reprendre le controle d une journee chargee.'],
        ['name' => 'Dumbo the Flying Elephant', 'aliases' => [], 'summary' => 'Classique visuel, fort pour les familles et les premiers sejours.', 'tip' => 'A mieux planifier qu a subir au hasard.'],
        ['name' => "Mad Hatter's Tea Cups", 'aliases' => [], 'summary' => 'Parenthese fun et rapide entre deux grosses decisions.', 'tip' => 'Tres bien pour casser une serie de longues files.'],
        ['name' => 'Le Carrousel de Lancelot', 'aliases' => [], 'summary' => 'Pivot central entre photo, symbole et petite respiration.', 'tip' => 'Une bonne carte a jouer quand le groupe a besoin de souffler sans quitter le land.'],
        ['name' => 'Casey Jr. - le Petit Train du Cirque', 'aliases' => ['Casey Jr. - le Petit Train du Cirque'], 'summary' => 'Petit coaster famille a surveiller avec attention.', 'tip' => 'Le ressentir comme un choix et pas comme une improvisation change tout.'],
        ['name' => 'Le Pays des Contes de Fees', 'aliases' => ['Le Pays des Contes de Fees, presented by Vittel'], 'summary' => 'Balade douce et tres utile en reequilibrage.', 'tip' => 'Une excellente proposition quand il faut redescendre en intensite.'],
        ['name' => 'Blanche-Neige et les Sept Nains', 'aliases' => ['Blanche-Neige et les Sept Nains'], 'summary' => 'Attraction patrimoniale, courte mais forte en aura.', 'tip' => 'Elle prend encore plus de sens quand tu l abordes comme heritage du parc.'],
        ['name' => 'Les Voyages de Pinocchio', 'aliases' => [], 'summary' => 'Dark ride compact, souvent sous-estime par les visiteurs presses.', 'tip' => 'Parfait pour une rubrique anecdotes courtes a lire en file.'],
        ['name' => "Alice's Curious Labyrinth", 'aliases' => [], 'summary' => 'Exploration libre ideale pour varier le tempo.', 'tip' => 'Tres utile quand on veut bouger sans se remettre tout de suite dans une file.'],
    ],
    'Adventureland' => [
        ['name' => 'Pirates of the Caribbean', 'aliases' => [], 'summary' => 'Immersion massive et excellente carte d arbitrage.', 'tip' => 'Tres souvent un meilleur choix qu on ne l imagine au premier regard.'],
        ['name' => 'Indiana Jones and the Temple of Peril', 'aliases' => ['Indiana Jones and the Temple of Peril'], 'summary' => 'Coaster court, net et tres lisible cote sensations.', 'tip' => 'Un bon candidat des qu une fenetre d attente raisonnable se presente.'],
        ['name' => 'Adventure Isle', 'aliases' => [], 'summary' => 'Zone libre a forte valeur d exploration.', 'tip' => 'Super terrain pour faire respirer une journee sans la vider.'],
        ['name' => 'La Cabane des Robinson', 'aliases' => [], 'summary' => 'Parcours vertical qui enrichit la visite au-dela des rides.', 'tip' => 'Tres bon angle pour des contenus fan originaux.'],
        ['name' => "Pirates' Beach", 'aliases' => [], 'summary' => 'Pause simple et efficace pour les groupes avec enfants.', 'tip' => 'A garder comme soupape quand la densite monte partout.'],
    ],
    'World Premiere Plaza' => [
        ['name' => 'The Twilight Zone Tower of Terror', 'aliases' => ['The Twilight Zone Tower of Terror™'], 'summary' => 'La grande ancre sensations a l entree du second parc.', 'tip' => 'Quand la file reste sage en debut ou fin de journee, il faut en profiter vite.'],
    ],
    'Marvel Avengers Campus' => [
        ['name' => 'Avengers Assemble: Flight Force', 'aliases' => ['Avengers Assemble: Flight Force'], 'summary' => 'Le gros pic sensations du parc cote MARVEL.', 'tip' => 'A traiter comme un headliner pur, avec une vraie veille sur les baisses.'],
        ['name' => 'Spider-Man W.E.B. Adventure', 'aliases' => ['Spider-Man WEB Adventure', 'Spider-Man W.E.B Adventure'], 'summary' => 'Attraction famille competitive tres populaire.', 'tip' => 'Excellente alternative quand on veut garder de l energie sans repartir sur un coaster intense.'],
    ],
    'Worlds of Pixar' => [
        ['name' => "Ratatouille : L'Aventure Totalement Toquee de Remy", 'aliases' => ['Ratatouille : L’Aventure Totalement Toquée de Rémy', 'Ratatouille: The Adventure'], 'summary' => 'Dark ride famille a tres forte notoriete.', 'tip' => 'Souvent plus utile qu attendu dans une strategie famille bien menee.'],
        ['name' => "Crush's Coaster", 'aliases' => ["Crush's Coaster®"], 'summary' => 'La file du second parc qui peut punir une journee mal lue.', 'tip' => 'A surveiller de tres pres car les variations valent de l or.'],
        ['name' => 'Cars ROAD TRIP', 'aliases' => ['Cars Road Trip'], 'summary' => 'Pause familiale plus souple et utile en reequilibrage.', 'tip' => 'Tres bon mouvement quand il faut faire redescendre l intensite du groupe.'],
        ['name' => 'RC Racer', 'aliases' => [], 'summary' => 'Mini dose sensations a fort impact visuel.', 'tip' => 'A glisser quand le timing Pixar est favorable.'],
        ['name' => 'Toy Soldiers Parachute Drop', 'aliases' => [], 'summary' => 'Repere famille qui peut monter vite dans le desordre.', 'tip' => 'A traiter comme une vraie priorite si le groupe est tres Toy Story.'],
        ['name' => 'Slinky Dog Zigzag Spin', 'aliases' => [], 'summary' => 'Attraction courte et simple pour les plus jeunes.', 'tip' => 'Bonne relance entre deux files plus engageantes.'],
        ['name' => 'Cars Quatre Roues Rallye', 'aliases' => ['Cars Quatre Roues Rallye'], 'summary' => 'Classique famille compact et efficace.', 'tip' => 'A garder comme soupape de milieu de parcours.'],
    ],
    'Adventure Way' => [
        ['name' => 'Raiponce Tangled Spin', 'aliases' => ['Rapunzel Tangled Spin'], 'summary' => 'Nouvelle attraction familiale de promenade.', 'tip' => 'Bon marqueur de parcours doux et de respiration au coeur du nouveau parc.'],
    ],
    'World of Frozen' => [
        ['name' => 'Frozen Ever After', 'aliases' => [], 'summary' => 'La grande nouveaute immersive a tres forte demande.', 'tip' => 'A surveiller comme une attraction signature avec une vraie pression de curiosite visiteur.'],
    ],
];

$home_features = [
    ['tag' => 'Deux parcs', 'title' => 'Le site lit maintenant Disneyland Park et Disney Adventure World', 'copy' => 'Le vrai gain, ce n est pas seulement plus d attractions. C est une lecture plus claire de deux rythmes de visite tres differents.', 'href' => 'wait-times.php', 'cta' => 'Voir les parcs'],
    ['tag' => 'Food', 'title' => 'Restauration sur les deux parcs', 'copy' => 'Tu peux comparer plus vite les pauses repas entre Main Street, Adventureland, Adventure Way ou World of Frozen.', 'href' => 'food.php', 'cta' => 'Voir les restos'],
    ['tag' => 'Stats', 'title' => 'Comprendre les pics sur les deux parcs', 'copy' => 'Le second parc a ses propres logiques de flux. Les stats doivent donc aider a les lire sans les confondre avec Disneyland Park.', 'href' => 'insights.php', 'cta' => 'Voir les stats'],
    ['tag' => 'Communaute', 'title' => 'Des checklists et notes sur toute la destination', 'copy' => 'Les visiteurs peuvent maintenant cocher et noter aussi bien Frozen Ever After que Big Thunder Mountain.', 'href' => 'community.php', 'cta' => 'Ouvrir la communaute'],
    ['tag' => 'Pratique', 'title' => 'Des repères terrain sur l ensemble du resort', 'copy' => 'Quand les visiteurs changent de parc, ils gardent leurs reperes utiles sans changer de logique de lecture.', 'href' => 'practical.php', 'cta' => 'Voir les reperes'],
    ['tag' => 'Watch', 'title' => 'Une veille plus claire sur les fermetures et transformations', 'copy' => 'C est particulierement utile maintenant que le second parc est devenu Disney Adventure World.', 'href' => 'maintenance.php', 'cta' => 'Voir les fermetures'],
];

$wait_alert_profiles = [
    'Frozen Ever After' => ['target' => 30, 'great' => 20, 'typical' => 60],
    "Crush's Coaster" => ['target' => 35, 'great' => 25, 'typical' => 55],
    'Avengers Assemble: Flight Force' => ['target' => 25, 'great' => 15, 'typical' => 40],
    'Spider-Man W.E.B. Adventure' => ['target' => 20, 'great' => 10, 'typical' => 30],
    'The Twilight Zone Tower of Terror' => ['target' => 25, 'great' => 15, 'typical' => 40],
    "Ratatouille : L'Aventure Totalement Toquee de Remy" => ['target' => 20, 'great' => 10, 'typical' => 32],
    'Big Thunder Mountain' => ['target' => 30, 'great' => 20, 'typical' => 45],
    "Peter Pan's Flight" => ['target' => 25, 'great' => 15, 'typical' => 40],
    'Star Wars Hyperspace Mountain' => ['target' => 30, 'great' => 20, 'typical' => 48],
    'Orbitron' => ['target' => 10, 'great' => 5, 'typical' => 15],
];

$show_reference = [
    [
        'id' => 'disney-stars-on-parade',
        'name' => 'Disney Stars on Parade',
        'park' => 'Disneyland Park',
        'kind' => 'Parade',
        'location' => 'Main Street U.S.A. / Central Plaza',
        'duration' => 30,
        'booking' => 'Acces libre',
        'target_days' => 'Tous les jours selon calendrier officiel',
        'times' => ['17:30'],
        'busy_times' => ['17:30'],
        'soft_times' => ['17:30'],
        'source_label' => 'Disneyland Paris officiel',
        'source_url' => 'https://www.disneylandparis.com/en-usd/entertainment/disney-stars-on-parade/',
    ],
    [
        'id' => 'disney-tales-of-magic',
        'name' => 'Disney Tales of Magic',
        'park' => 'Disneyland Park',
        'kind' => 'Nocturne',
        'location' => 'Chateau + Main Street U.S.A.',
        'duration' => 24,
        'booking' => 'Zone reservee optionnelle',
        'target_days' => 'Tous les soirs selon calendrier officiel',
        'times' => ['21:45'],
        'busy_times' => ['21:55'],
        'soft_times' => ['21:35'],
        'source_label' => 'Disneyland Paris officiel',
        'source_url' => 'https://www.disneylandparis.com/fr-fr/divertissements/spectacle-nocturne',
    ],
    [
        'id' => 'lion-king',
        'name' => 'The Lion King: Rhythms of the Pride Lands',
        'park' => 'Disneyland Park',
        'kind' => 'Show',
        'location' => 'Frontierland Theater',
        'duration' => 30,
        'booking' => 'Garantie d acces optionnelle',
        'target_days' => 'Actif hors pause annoncee',
        'times' => ['12:00', '13:30', '15:15', '17:00'],
        'busy_times' => ['11:45', '13:15', '15:00', '16:45', '18:15'],
        'soft_times' => ['12:30', '15:00', '17:15'],
        'break_notice' => 'Pause officielle du 4 mai au 12 juin 2026.',
        'source_label' => 'Disneyland Paris officiel',
        'source_url' => 'https://www.disneylandparis.com/en-int/entertainment/disneyland-park/lion-king-show',
    ],
    [
        'id' => 'mickey-and-the-magician',
        'name' => 'Mickey and the Magician',
        'park' => 'Disney Adventure World',
        'kind' => 'Show',
        'location' => 'Animagique Theater',
        'duration' => 30,
        'booking' => 'Acces libre selon capacite',
        'target_days' => 'Actif hors pause annoncee',
        'times' => ['11:30', '13:00', '14:30', '16:00', '17:30'],
        'busy_times' => ['11:15', '12:45', '14:15', '15:45', '17:15', '18:30'],
        'soft_times' => ['12:00', '14:30', '17:00'],
        'break_notice' => 'Pause officielle du 15 juin au 31 juillet 2026.',
        'source_label' => 'Disneyland Paris officiel',
        'source_url' => 'https://www.disneylandparis.com/en-usd/entertainment/walt-disney-studios-park/mickey-and-the-magician-show',
    ],
    [
        'id' => 'pixar-musical-adventure',
        'name' => 'TOGETHER: a Pixar Musical Adventure',
        'park' => 'Disney Adventure World',
        'kind' => 'Show',
        'location' => 'Studio Theater',
        'duration' => 30,
        'booking' => 'Garantie d acces optionnelle',
        'target_days' => 'Tous les jours selon calendrier officiel',
        'times' => ['11:45', '13:15', '14:45', '16:15', '17:45'],
        'busy_times' => ['11:15', '12:45', '14:15', '15:45', '17:15', '18:30'],
        'soft_times' => ['12:15', '14:30', '17:00'],
        'source_label' => 'Disneyland Paris officiel',
        'source_url' => 'https://www.disneylandparis.com/en-int/entertainment/disney-adventure-world/pixar-show',
    ],
    [
        'id' => 'celebration-in-arendelle',
        'name' => 'A Celebration in Arendelle',
        'park' => 'Disney Adventure World',
        'kind' => 'Show',
        'location' => 'Arendelle Bay',
        'duration' => 20,
        'booking' => 'Acces libre sur zone',
        'target_days' => 'Tous les jours selon meteo et calendrier officiel',
        'times' => ['12:20', '14:10', '16:00', '18:20'],
        'busy_times' => ['12:00', '13:50', '15:40', '17:20', '18:50'],
        'soft_times' => ['12:45', '15:30', '18:00'],
        'source_label' => 'Disneyland Paris officiel',
        'source_url' => 'https://www.disneylandparis.com/en-int/entertainment/disney-adventure-world/animation-celebration-frozen-a-musical-invitation',
    ],
];

$news_articles = [
    [
        'slug' => 'disney-adventure-world-change-la-donne',
        'park' => 'Disney Adventure World',
        'category' => 'Destination',
        'date' => '28 avril 2026',
        'title' => 'Pourquoi Disney Adventure World change vraiment la lecture du resort',
        'excerpt' => 'Le deuxieme parc n est plus une simple annexe. Il faut maintenant le lire comme un vrai parc avec ses propres priorites, ses nouveaux mondes et ses flux a part.',
        'reading_time' => '5 min',
        'cover_label' => 'Deuxieme parc',
        'body' => [
            'Disney Adventure World ouvre un nouveau chapitre a Disneyland Paris. Pour un guide fan, ca change beaucoup de choses: la destination ne se resume plus a opposer le grand parc iconique et un second parc plus faible.',
            'Les visiteurs doivent maintenant arbitrer entre deux rythmes distincts. Disneyland Park reste tres structure par ses lands. Disney Adventure World, lui, demande une lecture plus modulaire, plus contemporaine et plus sensible aux nouvelles zones.',
            'Un bon site fan doit donc parler des deux parcs avec la meme exigence de lisibilite, sans forcer les memes reflexes partout.',
        ],
        'points' => [
            'Deux parcs, deux rythmes de visite.',
            'Le second parc devient enfin une vraie destination en soi.',
            'Le site doit aider a passer de l un a l autre sans confusion.',
        ],
    ],
    [
        'slug' => 'frontierland-reste-une-masterclass',
        'park' => 'Disneyland Park',
        'category' => 'Culture parc',
        'date' => '26 avril 2026',
        'title' => 'Pourquoi Frontierland reste une masterclass de lecture terrain',
        'excerpt' => 'Meme avec la montee de Disney Adventure World, Disneyland Park garde des zones ou le site fan peut faire la difference par la qualite du regard.',
        'reading_time' => '4 min',
        'cover_label' => 'Disneyland Park',
        'body' => [
            'Disney Adventure World attire a juste titre beaucoup de curiosite. Mais Disneyland Park garde des zones ou la lecture fan reste presque inepuisable, et Frontierland en fait partie.',
            'C est un land parfait pour montrer que le site ne fait pas que suivre des nouveautes. Il sait aussi expliquer pourquoi certains espaces du resort restent des references absolues de coherence visuelle et narrative.',
            'Ce type de contenu renforce le projet: tu sais couvrir le neuf sans oublier le patrimonial.',
        ],
        'points' => [
            'Le nouveau ne remplace pas la valeur du parc historique.',
            'Frontierland reste une zone ideale pour le contenu fan.',
            'Le site doit faire vivre les deux parcs avec la meme exigence.',
        ],
    ],
    [
        'slug' => 'savoir-lire-une-grosse-journee',
        'park' => 'Resort',
        'category' => 'Strategie',
        'date' => '27 avril 2026',
        'title' => 'Savoir lire une grosse journee sans perdre son energie',
        'excerpt' => 'Un visiteur n a pas besoin d entendre que le parc est charge. Il a besoin de savoir quoi faire ensuite.',
        'reading_time' => '4 min',
        'cover_label' => 'Focus terrain',
        'body' => [
            'Quand le resort se charge, beaucoup de visiteurs perdent surtout du temps a hesiter. Un bon guide fan doit donc reduire cette hesitation et proposer des choix immediats.',
            'Le vrai service a rendre, ce n est pas seulement de montrer les grosses attentes. C est d expliquer quelles alternatives gardent de la valeur sans donner l impression de renoncer.',
            'Si ton site devient cet outil de reequilibrage, il prend une vraie place dans la journee des gens.',
        ],
        'points' => [
            'Montrer les grosses files ne suffit pas.',
            'Il faut proposer des substituts intelligents par zone.',
            'Le ton doit rester calme, utile et rapide a lire.',
        ],
    ],
    [
        'slug' => 'garder-une-identite-claire-et-defendable',
        'park' => 'Resort',
        'category' => 'Positionnement',
        'date' => '24 avril 2026',
        'title' => 'Garder une identite claire et defendable face a une grande marque',
        'excerpt' => 'Le bon move n est pas de semer le doute. C est d etre excellent tout en restant tres clair sur ton statut.',
        'reading_time' => '4 min',
        'cover_label' => 'Clarte',
        'body' => [
            'Plus un site fan devient propre, plus il doit affirmer sa difference de facon tranquille. C est bon pour la confiance et plus sain juridiquement.',
            'Cela n empeche en rien de faire un site superbe. Au contraire, ca rend le projet plus mature et plus credible.',
            'Le bon pari, c est de se faire remarquer pour la qualite du service rendu aux visiteurs.',
        ],
        'points' => [
            'Toujours afficher clairement le statut fan site.',
            'Eviter logos et codes officiels trop proches.',
            'Faire de la valeur utilisateur le coeur du projet.',
        ],
    ],
];

$secret_sections = [
    'Frontierland' => [
        ['title' => 'Thunder Mesa fonctionne comme un seul grand recit', 'copy' => 'Le manoir, la montagne et la ville miniere dialoguent entre eux. C est un super exemple de storytelling a montrer a tes visiteurs.'],
        ['title' => 'Les transitions valent autant que les attractions', 'copy' => 'Dans ce land, les vues et les axes racontent deja quelque chose avant meme d entrer en file.'],
    ],
    'Discoveryland' => [
        ['title' => 'Le futur imagine ici n est pas un futur lisse', 'copy' => 'On y sent Jules Verne, l industrie, la machine et un imaginaire retro-futuriste plus riche que le simple theme spatial.'],
        ['title' => 'Les details de structures valent une vraie lecture sur place', 'copy' => 'Entre Nautilus, Orbitron et les grandes places, il y a matiere a guider le regard de maniere tres concrete.'],
    ],
    'Fantasyland' => [
        ['title' => 'Les icones du land gagnent a etre remises dans le rythme de la journee', 'copy' => 'Aider un visiteur a comprendre quand faire Peter Pan ou quand respirer avec small world, c est deja une forme de curation fan.'],
        ['title' => 'Le patrimoine visuel se cache souvent dans les details les plus simples', 'copy' => 'Carrousel, labyrinthes et dark rides offrent plein d observations courtes parfaites pour une rubrique mobile.'],
    ],
    'Adventureland' => [
        ['title' => 'Ce land se savoure aussi en dehors des files', 'copy' => 'Ponts, grottes, cabanes et vues inattendues permettent de produire des contenus fan immediatement utiles.'],
        ['title' => 'Le plaisir de l exploration peut devenir un vrai marqueur du site', 'copy' => 'Si tu racontes bien Adventureland, les visiteurs comprendront vite que ton site apporte quelque chose d unique.'],
    ],
    'Marvel Avengers Campus' => [
        ['title' => 'Tout est concu pour vendre de l energie', 'copy' => 'Architecture, musique et trajectoires te poussent a ressentir le Campus comme une zone d activation continue.'],
        ['title' => 'Spider-Man et Flight Force racontent deux tonalites MARVEL tres differentes', 'copy' => 'L une est ludique et cooperative, l autre plus frontale et martiale. C est un bon angle editorial pour parler du parc.'],
    ],
    'Worlds of Pixar' => [
        ['title' => 'C est la zone la plus heterogene du parc', 'copy' => 'On y passe d une ambiance de fete foraine a une poursuite miniature ou a un dark ride culinaire. C est parfait pour une lecture fan plus fine.'],
        ['title' => 'Les visiteurs y sous-estiment souvent Crush', 'copy' => 'La zone semble famille, mais certaines files y changent totalement la strategie de journee.'],
    ],
    'Adventure Way' => [
        ['title' => 'Le parcours devient presque une experience en soi', 'copy' => 'Le lieu sert a relier les mondes, mais aussi a imposer un nouveau rythme de promenade au second parc.'],
        ['title' => 'Le soir change la perception du parc', 'copy' => 'Avec le lac et le show nocturne, la zone peut devenir un vrai point d ancrage de fin de journee.'],
    ],
    'World of Frozen' => [
        ['title' => 'Arendelle est pensee comme un paysage de destination', 'copy' => 'On ne vient pas seulement pour un ride, mais pour entrer dans une carte postale tres lisible pour le public.'],
        ['title' => 'La curiosite grand public vaut autant que la fan culture', 'copy' => 'C est une zone ou l attente peut exploser parce que les visiteurs veulent juste la voir, meme sans grosse strategie.'],
    ],
];

$planner_guides = [
    ['title' => '1 jour, 2 parcs', 'audience' => 'Pour ceux qui veulent gouter aux deux sans s epuiser.', 'steps' => ['Choisir un parc d ouverture fort et un deuxieme parc plus cible.', 'Eviter les allers-retours inutiles entre les deux avant la mi-journee.', 'Reserver une ou deux priorites absolues maximum par parc.']],
    ['title' => 'Disneyland Park d abord', 'audience' => 'Pour les premieres visites ou ceux qui veulent les icones.', 'steps' => ['Traiter tres tot une grosse priorite comme Peter Pan ou Big Thunder.', 'Utiliser l apres-midi pour des valeurs de respiration bien choisies.', 'Garder le second parc pour une cible claire, pas pour tout voir.']],
    ['title' => 'Disney Adventure World cible', 'audience' => 'Pour ceux qui veulent Frozen, MARVEL ou Pixar.', 'steps' => ['Decider si la journee tourne d abord autour de Frozen Ever After, Crush ou Flight Force.', 'Lire le parc par zones plutot que par reflexe d ancien Walt Disney Studios.', 'Profiter du soir pour relancer une ou deux cartes majeures.']],
    ['title' => 'Journee famille', 'audience' => 'Pour les groupes qui veulent du confort avant tout.', 'steps' => ['Alterner une file marquee et une experience respiration.', 'Faire de vrais blocs de zone plutot que des zigzags.', 'Ne pas sous-estimer le poids logistique du changement de parc.']],
];

$faq_items = [
    ['question' => 'Le site est-il officiel ?', 'answer' => 'Non. Il s agit volontairement d un site fan independant. L objectif est d aider les visiteurs avec un regard terrain et une presentation premium.'],
    ['question' => 'Pourquoi integrer Disney Adventure World aussi vite ?', 'answer' => 'Parce qu a partir du printemps 2026, le deuxieme parc change suffisamment pour meriter une vraie lecture propre.'],
    ['question' => 'Le tableau live couvre-t-il les deux parcs ?', 'answer' => 'Oui, la base fan lit le flux resort et rattache ensuite les attractions a Disneyland Park ou Disney Adventure World.'],
];

$brand_principles = [
    ['title' => 'Deux parcs lus avec la meme exigence', 'copy' => 'Le visiteur doit sentir que le site comprend autant le parc iconique que le deuxieme parc transforme.'],
    ['title' => 'Un vrai service rendu sur le terrain', 'copy' => 'Temps live, arbitrages, restauration, parcours, actus et secrets se completent pour devenir utiles ensemble.'],
    ['title' => 'Une base saine pour la future appli', 'copy' => 'Cette version pose deja les briques pour une experience mobile capable de suivre les deux parcs.'],
];

$food_filters = [
    'vegan' => 'Vegan',
    'vegetarian' => 'Vegetarien',
    'gluten_support' => 'Sans gluten / allergenes',
];

$dining_spots = [
    ['id' => 'hakuna-matata', 'park' => 'Disneyland Park', 'name' => 'Hakuna Matata Restaurant', 'land' => 'Adventureland', 'service' => 'Comptoir', 'booking' => 'Sans reservation', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Allergenes consultables sur demande', 'why' => 'Un des spots les plus lisibles pour manger vite sans perdre les visiteurs qui cherchent une option vegetale claire.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Chakalaka', 'price' => '14 EUR', 'type' => 'Plat vegan'], ['name' => 'Vegan Magnum Mango Yuzu', 'price' => '4.50 EUR', 'type' => 'Snack vegan']]],
    ['id' => 'captain-jacks', 'park' => 'Disneyland Park', 'name' => "Captain Jack's - Restaurant des Pirates", 'land' => 'Adventureland', 'service' => 'Service a table', 'booking' => 'Reservation conseillee', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Verifier les allergenes avec l equipe sur place', 'why' => 'Une reservation difficile a decrocher, mais avec de vraies options vegetales annoncees a la carte.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Caribbean Quinoa Salad', 'price' => '17 EUR', 'type' => 'Entree vegetale'], ['name' => "Jack's Potatoes and Vegetable Curry", 'price' => '30 EUR', 'type' => 'Plat vegetal']]],
    ['id' => 'walts', 'park' => 'Disneyland Park', 'name' => "Walt's - An American Restaurant", 'land' => 'Main Street, U.S.A.', 'service' => 'Service a table', 'booking' => 'Reservation quasi obligatoire', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Liste allergenes et solution sans allergenes majeurs sur demande', 'why' => 'Adresse signature pour une pause calme et premium.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => "Menu Walt's", 'price' => '55 EUR', 'type' => 'Menu avec option vegetarienne selon carte']]],
    ['id' => 'plaza-gardens', 'park' => 'Disneyland Park', 'name' => 'Plaza Gardens Restaurant', 'land' => 'Main Street, U.S.A.', 'service' => 'Buffet', 'booking' => 'Reservation conseillee', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Buffet plus souple pour groupes mixtes', 'why' => 'Un bon filet de securite quand tu veux une grande capacite, un service lisible et des options plus faciles pour tout le monde.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Buffet international', 'price' => 'Selon service', 'type' => 'Buffet famille']]],
    ['id' => 'bella-notte', 'park' => 'Disneyland Park', 'name' => 'Bella Notte', 'land' => 'Fantasyland', 'service' => 'Comptoir', 'booking' => 'Sans reservation', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Verifier les allergenes sur place', 'why' => 'Tres pratique pour une pause Fantasyland sans casser le bloc du land quand les familles veulent rester proches de Peter Pan ou Dumbo.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Pasta et pizza du jour', 'price' => 'Selon carte', 'type' => 'Repas rapide']]],
    ['id' => 'colonel-hathi', 'park' => 'Disneyland Park', 'name' => "Colonel Hathi's Pizza Outpost", 'land' => 'Adventureland', 'service' => 'Comptoir', 'booking' => 'Sans reservation', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Liste allergenes a demander', 'why' => 'Une solution simple pour poser un groupe a Adventureland sans basculer sur une table plus lourde.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Pizza et salade', 'price' => 'Selon carte', 'type' => 'Repas rapide']]],
    ['id' => 'fuente-del-oro', 'park' => 'Disneyland Park', 'name' => 'Fuente del Oro Restaurante', 'land' => 'Frontierland', 'service' => 'Comptoir', 'booking' => 'Sans reservation', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Reperes allergenes a verifier', 'why' => 'A garder en tete pour un repas rapide quand Frontierland devient un vrai bloc de visite et que tu veux eviter un grand detour.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Tex-Mex et options du jour', 'price' => 'Selon carte', 'type' => 'Repas rapide']]],
    ['id' => 'regal-view', 'park' => 'Disney Adventure World', 'name' => 'The Regal View Restaurant & Lounge', 'land' => 'Adventure Way', 'service' => 'Service a table', 'booking' => 'Reservation tres conseillee', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Carte a verifier en direct, restaurant ouvert depuis le 29 mars 2026', 'why' => 'Une des nouvelles tables signatures du second parc, avec vue sur le lac et positionnement premium.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Cuisine de table avec vue lac', 'price' => 'Carte du moment', 'type' => 'Nouveau restaurant']]],
    ['id' => 'nordic-crowns', 'park' => 'Disney Adventure World', 'name' => 'Nordic Crowns Tavern', 'land' => 'World of Frozen', 'service' => 'Quick service', 'booking' => 'Sans reservation', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Verifier la carte et les allergenes sur place', 'why' => 'Point cle pour les visiteurs qui veulent pousser la visite Frozen au-dela du seul ride avec une vraie pause thematique.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Specialites nordiques et option vegan de meatballs', 'price' => 'Carte du moment', 'type' => 'Quick service']]],
    ['id' => 'pym-kitchen', 'park' => 'Disney Adventure World', 'name' => 'PYM Kitchen', 'land' => 'Marvel Avengers Campus', 'service' => 'Buffet', 'booking' => 'Reservation conseillee', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Buffet pratique pour un groupe heterogene', 'why' => 'Bonne option quand le groupe veut un repas plus simple a arbitrer cote MARVEL.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Buffet thematise MARVEL', 'price' => 'Selon formule du jour', 'type' => 'Buffet']]],
    ['id' => 'stark-factory', 'park' => 'Disney Adventure World', 'name' => 'Stark Factory', 'land' => 'Marvel Avengers Campus', 'service' => 'Comptoir', 'booking' => 'Sans reservation', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Pizza, pasta et salade avec alternatives selon carte', 'why' => 'Un bon compromis quand tu veux asseoir un groupe MARVEL sans imposer ni buffet ni reservation.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Pizza, pasta et salades', 'price' => 'Selon carte', 'type' => 'Repas rapide']]],
    ['id' => 'super-diner', 'park' => 'Disney Adventure World', 'name' => 'Super Diner', 'land' => 'Marvel Avengers Campus', 'service' => 'Comptoir', 'booking' => 'Sans reservation', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Shawarma et alternatives vegetales selon carte', 'why' => 'Tres utile pour un snack-repas rapide quand tu veux rester mobile dans Avengers Campus.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Shawarma et version vegan', 'price' => 'Selon carte', 'type' => 'Snack-repas']]],
    ['id' => 'web-food-truck', 'park' => 'Disney Adventure World', 'name' => 'WEB Food Truck', 'land' => 'Marvel Avengers Campus', 'service' => 'Food truck', 'booking' => 'Sans reservation', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => false, 'support_label' => 'Tofu fume et allergenes a confirmer sur place', 'why' => 'Parfait quand le groupe veut garder le rythme du Campus sans couper la dynamique pour une vraie pause a table.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Nouilles poulet, crevettes ou tofu fume', 'price' => 'Selon carte', 'type' => 'Food truck']]],
    ['id' => 'fan-tastic', 'park' => 'Disney Adventure World', 'name' => 'FAN-tastic Food Truck', 'land' => 'Marvel Avengers Campus', 'service' => 'Food truck', 'booking' => 'Sans reservation', 'vegan' => true, 'vegetarian' => true, 'gluten_support' => false, 'support_label' => 'Hot-dog veggie et produits sucres selon carte', 'why' => 'Le spot le plus pratique pour un snack mobile si tu surveilles une baisse de wait sur Spider-Man ou Flight Force.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026', 'items' => [['name' => 'Hot-dogs gourmands et vegan cookie', 'price' => 'Selon carte', 'type' => 'Food truck']]],
    ['id' => 'chez-remy', 'park' => 'Disney Adventure World', 'name' => 'Bistrot Chez Remy', 'land' => 'Worlds of Pixar', 'service' => 'Service a table', 'booking' => 'Reservation tres conseillee', 'vegan' => false, 'vegetarian' => true, 'gluten_support' => true, 'support_label' => 'Verifier les options du jour et allergenes', 'why' => 'Toujours une des tables les plus demandees du deuxieme parc, avec un vrai capital affectif pour les visiteurs Pixar.', 'source' => 'Menu officiel consulte le 28 avril 2026', 'items' => [['name' => 'Cuisine de bistrot inspiree de Ratatouille', 'price' => 'Selon carte', 'type' => 'Service a table']]],
];

$seasonal_snacks = [
    ['id' => 'curious-labyrinth-ice-cream', 'park' => 'Disneyland Park', 'name' => 'Curious Labyrinth Ice Cream', 'location' => 'March Hare Refreshments', 'land' => 'Fantasyland', 'price' => '7 EUR', 'season' => 'Alice / Printemps', 'image' => 'assets/images/snack-curious-labyrinth.png', 'summary' => 'Le snack photo le plus facile a montrer dans une rubrique saisonniere.', 'source' => 'Carte officielle March Hare Refreshments'],
    ['id' => 'cheshire-choco-shake', 'park' => 'Disneyland Park', 'name' => 'Cheshire Choco Shake', 'location' => 'March Hare Refreshments', 'land' => 'Fantasyland', 'price' => '9 EUR', 'season' => 'Alice / Printemps', 'image' => 'assets/images/snack-cheshire-shake.png', 'summary' => 'Boisson dessert tres forte visuellement, parfaite pour une carte coup de coeur.', 'source' => 'Carte officielle March Hare Refreshments'],
    ['id' => 'mickey-waffle', 'park' => 'Disneyland Park', 'name' => 'Mickey Waffle', 'location' => "Victoria's Home-Style Restaurant", 'land' => 'Main Street, U.S.A.', 'price' => '9 EUR', 'season' => 'Classique signature', 'image' => 'assets/images/snack-mickey-waffle.png', 'summary' => 'Le genre de repere simple qui rassure beaucoup les familles.', 'source' => "Carte officielle Victoria's Home-Style Restaurant"],
    ['id' => 'adventure-way-sweet-break', 'park' => 'Disney Adventure World', 'name' => 'Pause sucree Adventure Way', 'location' => 'Snack bars Adventure Way', 'land' => 'Adventure Way', 'price' => 'Carte du moment', 'season' => 'Nouvelle zone 2026', 'image' => 'assets/images/snack-unbirthday.png', 'summary' => 'Repere fan pour suivre les nouveautes gourmandes autour de la promenade et du lac.', 'source' => 'Offre snack officielle a surveiller sur place'],
    ['id' => 'frozen-snack-watch', 'park' => 'Disney Adventure World', 'name' => 'Douceur World of Frozen', 'location' => 'Quick service World of Frozen', 'land' => 'World of Frozen', 'price' => 'Carte du moment', 'season' => 'World of Frozen', 'image' => 'assets/images/snack-milkshake.png', 'summary' => 'Un slot parfait pour enrichir plus tard la page avec tes propres photos terrain du nouveau land.', 'source' => 'Offre snack officielle a surveiller sur place'],
    ['id' => 'liberty-lollipop', 'park' => 'Disney Adventure World', 'name' => 'Liberty Lollipop', 'location' => 'FAN-tastic Food Truck', 'land' => 'Marvel Avengers Campus', 'price' => 'Carte du moment', 'season' => 'Campus snack', 'image' => 'assets/images/snack-unbirthday.png', 'summary' => 'Le genre de petit produit visuel qui marche tres bien en repere mobile quand on veut grignoter sans quitter Avengers Campus.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026'],
    ['id' => 'choco-blast', 'park' => 'Disney Adventure World', 'name' => 'Choco-Blast', 'location' => 'Super Diner', 'land' => 'Marvel Avengers Campus', 'price' => 'Carte du moment', 'season' => 'Campus snack', 'image' => 'assets/images/snack-milkshake.png', 'summary' => 'Un vrai repere fan pour les visiteurs qui veulent un snack identitaire plutot qu un simple cafe de passage.', 'source' => 'Page officielle Disneyland Paris consultee le 28 avril 2026'],
];

$reservation_tips = [
    ['tag' => 'Officiel', 'title' => 'Les reservations ouvrent tot', 'copy' => 'Disneyland Paris indique que la plupart des restaurants ouvrent les reservations jusqu a 2 mois a l avance, ou des la confirmation du sejour pour les visiteurs des Hotels Disney.'],
    ['tag' => 'Fan move', 'title' => 'Walt s, Captain Jack s et Regal View meritent un rappel calendrier', 'copy' => 'Place-toi un rappel le jour d ouverture des reservations, puis reviens verifier la veille et le matin meme car les annulations tombent souvent par vagues.'],
    ['tag' => 'Officiel', 'title' => 'Tu peux encore tenter sur place', 'copy' => 'Le resort rappelle qu il est possible de reserver a l arrivee a la reception des Hotels Disney ou directement dans certains restaurants si des places reapparaissent.'],
    ['tag' => 'Fan move', 'title' => 'Vise les horaires moins attaques', 'copy' => 'Un dejeuner vers 11 h 30 ou apres 14 h et un diner assez tot donnent souvent plus de marge qu un coeur de service trop vise.'],
];

$meal_budget_defaults = [
    'adults' => 2,
    'kids' => 2,
    'quick_adult' => 18,
    'quick_kid' => 12,
    'table_adult' => 55,
    'table_kid' => 28,
    'signature_adult' => 68,
    'signature_kid' => 35,
    'snack' => 8,
];

$holiday_windows = [
    ['label' => 'Pays-Bas - meivakantie', 'country' => 'Pays-Bas', 'start' => '2026-04-25', 'end' => '2026-05-03', 'weight' => 22, 'source_label' => 'Rijksoverheid 2025-2026'],
    ['label' => 'Royaume-Uni - half term de fin mai', 'country' => 'Royaume-Uni', 'start' => '2026-05-25', 'end' => '2026-05-29', 'weight' => 18, 'source_label' => 'Calendrier scolaire local type 2025-2026'],
    ['label' => 'Espagne - depart grandes vacances (proxy regional)', 'country' => 'Espagne', 'start' => '2026-06-20', 'end' => '2026-07-31', 'weight' => 16, 'source_label' => 'Calendriers regionaux 2025-2026'],
    ['label' => 'Pays-Bas - vacances d ete nord', 'country' => 'Pays-Bas', 'start' => '2026-07-04', 'end' => '2026-07-16', 'weight' => 14, 'source_label' => 'Rijksoverheid 2025-2026'],
    ['label' => 'Pays-Bas - vacances d ete sud', 'country' => 'Pays-Bas', 'start' => '2026-07-11', 'end' => '2026-07-23', 'weight' => 18, 'source_label' => 'Rijksoverheid 2025-2026'],
    ['label' => 'Pays-Bas - vacances d ete centre', 'country' => 'Pays-Bas', 'start' => '2026-07-18', 'end' => '2026-07-31', 'weight' => 22, 'source_label' => 'Rijksoverheid 2025-2026'],
];

$community_seed_ratings = [
    ['id' => 'big-thunder-mountain', 'name' => 'Big Thunder Mountain', 'type' => 'Attraction', 'zone' => 'Disneyland Park', 'rating' => 4.9, 'votes' => 284],
    ['id' => 'phantom-manor', 'name' => 'Phantom Manor', 'type' => 'Attraction', 'zone' => 'Disneyland Park', 'rating' => 4.8, 'votes' => 241],
    ['id' => 'pirates-of-the-caribbean', 'name' => 'Pirates of the Caribbean', 'type' => 'Attraction', 'zone' => 'Disneyland Park', 'rating' => 4.8, 'votes' => 228],
    ['id' => 'flight-force', 'name' => 'Avengers Assemble: Flight Force', 'type' => 'Attraction', 'zone' => 'Disney Adventure World', 'rating' => 4.6, 'votes' => 172],
    ['id' => 'spiderman-web', 'name' => 'Spider-Man W.E.B. Adventure', 'type' => 'Attraction', 'zone' => 'Disney Adventure World', 'rating' => 4.7, 'votes' => 165],
    ['id' => 'frozen-ever-after', 'name' => 'Frozen Ever After', 'type' => 'Attraction', 'zone' => 'Disney Adventure World', 'rating' => 4.7, 'votes' => 149],
    ['id' => 'crush-coaster', 'name' => "Crush's Coaster", 'type' => 'Attraction', 'zone' => 'Disney Adventure World', 'rating' => 4.8, 'votes' => 214],
    ['id' => 'captain-jacks-rating', 'name' => "Captain Jack's - Restaurant des Pirates", 'type' => 'Restaurant', 'zone' => 'Disneyland Park', 'rating' => 4.7, 'votes' => 132],
    ['id' => 'regal-view-rating', 'name' => 'The Regal View Restaurant & Lounge', 'type' => 'Restaurant', 'zone' => 'Disney Adventure World', 'rating' => 4.5, 'votes' => 74],
    ['id' => 'pym-kitchen-rating', 'name' => 'PYM Kitchen', 'type' => 'Restaurant', 'zone' => 'Disney Adventure World', 'rating' => 4.4, 'votes' => 88],
];

$secret_hunt_tasks = [
    ['id' => 'phantom-ring', 'land' => 'Frontierland', 'difficulty' => 'Iconique', 'title' => 'La bague de la mariee', 'prompt' => 'As-tu repere la bague posee au sol pres de Phantom Manor ?'],
    ['id' => 'thunder-mesa-links', 'land' => 'Frontierland', 'difficulty' => 'Lore', 'title' => 'Le recit commun de Thunder Mesa', 'prompt' => 'As-tu observe comment le manoir, la montagne et la ville se repondent visuellement ?'],
    ['id' => 'nautilus-details', 'land' => 'Discoveryland', 'difficulty' => 'Exploration', 'title' => 'Les details du Nautilus', 'prompt' => 'As-tu pris le temps de lire les textures, cadrans et machines du Nautilus ?'],
    ['id' => 'alice-viewpoint', 'land' => 'Fantasyland', 'difficulty' => 'Photo', 'title' => 'Le point de vue du Labyrinthe', 'prompt' => 'As-tu cherche un beau point de vue sur le chateau depuis le Labyrinthe d Alice ?'],
    ['id' => 'adventure-isle-bridges', 'land' => 'Adventureland', 'difficulty' => 'Exploration', 'title' => 'Le plaisir des passages caches', 'prompt' => 'As-tu fait un vrai tour des ponts, grottes et passages d Adventure Isle ?'],
    ['id' => 'campus-energy', 'land' => 'Marvel Avengers Campus', 'difficulty' => 'Ambiance', 'title' => 'Lire l energie du Campus', 'prompt' => 'As-tu pris le temps de sentir comment la zone te pousse a rester en mouvement ?'],
    ['id' => 'crush-pressure', 'land' => 'Worlds of Pixar', 'difficulty' => 'Fan radar', 'title' => 'La vraie pression sur Crush', 'prompt' => 'As-tu repere a quel point la file de Crush change le tempo du parc ?'],
    ['id' => 'adventure-way-lake', 'land' => 'Adventure Way', 'difficulty' => 'Promenade', 'title' => 'Le nouveau role du lac', 'prompt' => 'As-tu observe comment Adventure Way recentre le parc autour du lac et du show ?'],
    ['id' => 'frozen-arrival', 'land' => 'World of Frozen', 'difficulty' => 'Immersion', 'title' => 'Entrer dans Arendelle', 'prompt' => 'As-tu pris le temps de regarder comment le paysage de World of Frozen joue la carte de la destination complete ?'],
];

$practical_sections = [
    'Recharge' => [
        ['zone' => 'Disneyland Park', 'name' => 'Cafe Hyperion et grandes pauses couvertes', 'detail' => 'Repere fan souvent cite pour s asseoir et tenter une recharge pendant une pause plus confortable.', 'note' => 'A confirmer le jour meme car la disponibilite peut varier.'],
        ['zone' => 'Disney Adventure World', 'name' => 'Restaurants a table et zones de pause Adventure Way', 'detail' => 'Le second parc gagne en lieux de pause plus lisibles autour des nouvelles zones et des restaurants recents.', 'note' => 'Le plus fiable reste souvent une vraie pause repas.'],
    ],
    'Ombre et pluie' => [
        ['zone' => 'Disneyland Park', 'name' => 'Les Arcades de Main Street', 'detail' => 'Le meilleur reflexe quand il pleut fort ou quand la rue centrale devient trop chargee.', 'note' => 'Excellent aussi pour traverser le parc plus calmement.'],
        ['zone' => 'Disney Adventure World', 'name' => 'World Premiere Plaza et circulations abritees', 'detail' => 'Bon repere au debut du second parc quand la meteo tourne ou que le groupe a besoin de souffler.', 'note' => 'A combiner avec une pause snack ou show.'],
        ['zone' => 'Disney Adventure World', 'name' => 'Adventure Way en fin de journee', 'detail' => 'La promenade change la facon de se poser et de repartir dans le parc, surtout quand la chaleur retombe.', 'note' => 'Tres utile avant le show nocturne.'],
    ],
    'Fontaines a eau' => [
        ['zone' => 'Disneyland Park', 'name' => 'Autour des zones familiales et sanitaires', 'detail' => 'Les familles cherchent souvent ces points en priorite. Cette page te donne des reperes simples plutot qu une carte lourde.', 'note' => 'Toujours verifier le fonctionnement sur place.'],
        ['zone' => 'Disney Adventure World', 'name' => 'Pres des grands flux Pixar et Marvel', 'detail' => 'Pratique pour remplir rapidement une gourde avant de repartir sur un bloc d attractions.', 'note' => 'Tres utile dans le second parc si les files se tendent et que tu veux rester mobile.'],
    ],
];

$maintenance_watchlist = [
    ['park' => 'Disney Adventure World', 'title' => 'Disney Adventure World vient juste d entrer dans sa nouvelle phase', 'window' => 'Printemps 2026', 'status' => 'Officiel', 'impact' => 'Le deuxieme parc accueille depuis le 29 mars 2026 World of Frozen, Adventure Way et de nouvelles experiences. Les habitudes de circulation et la pression de visite sont encore en train de se stabiliser.', 'source_label' => 'Disneyland Paris / Business Solutions', 'source_url' => 'https://business.disneylandparis.com/en/world-of-frozen-opens-march-29-2026-at-disney-adventure-world/'],
    ['park' => 'Resort', 'title' => 'Transformation de Disney Village', 'window' => 'Mai a juillet 2026', 'status' => 'Officiel', 'impact' => 'Travaux pluriannuels toujours en cours. La zone reste ouverte mais l experience visuelle et certains parcours continuent d evoluer.', 'source_label' => 'Page travaux a venir Disneyland Paris', 'source_url' => 'https://www.disneylandparis.com/fr-fr/destinations/travaux-a-venir-a-disneyland-paris'],
    ['park' => 'Disney Adventure World', 'title' => 'Le futur land Roi Lion reste un point de veille', 'window' => 'Au-dela de l ete 2026', 'status' => 'Officiel', 'impact' => 'Disney a deja confirme qu un quatrieme land inspire du Roi Lion rejoindra plus tard Disney Adventure World. Cela nourrit deja l interet du second parc dans la duree.', 'source_label' => 'Disneyland Paris Business Solutions', 'source_url' => 'https://business.disneylandparis.com/en/world-of-frozen-opens-march-29-2026-at-disney-adventure-world/'],
];

$source_notes = [
    'food' => 'Menus et FAQ officiels Disneyland Paris consultes le 28 avril 2026. Les cartes peuvent evoluer, surtout pour les produits saisonniers et les nouvelles adresses de Disney Adventure World.',
    'crowd' => 'Estimation fan fondee sur les calendriers 2025-2026 publics des Pays-Bas et sur des fenetres proxy pour le Royaume-Uni et l Espagne.',
    'shows' => 'Programme fan base sur les pages officielles de spectacles consultees le 28 avril 2026. Les horaires exacts restent a verifier le jour meme dans l app ou le calendrier officiel.',
    'practical' => 'Les reperes recharge, ombre et fontaines servent de guide terrain et doivent etre verifies sur place.',
    'maintenance' => 'Les fermetures du jour viennent du flux live resort. Le prospectif melange annonces officielles et veille fan clairement etiquees.',
];

$resort_live_url = 'https://api.themeparks.wiki/v1/entity/e8d0207f-da8a-4048-bec8-117aa946b2c2/live';
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: DLP-Fan-Guide/1.0\r\n",
        'timeout' => 8,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
];

$context = stream_context_create($opts);
$response = @file_get_contents($resort_live_url, false, $context);

if ($response === false) {
    $opts['ssl'] = [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($resort_live_url, false, $context);
}

$live_raw_attractions = [];
$live_lookup = [];
$live_is_available = false;

if ($response) {
    $payload = json_decode($response, true);
    extractLiveAttractions($payload, $live_raw_attractions);
    $live_lookup = buildLiveLookup($live_raw_attractions);
    $live_is_available = !empty($live_lookup);
}

$catalogue_by_land = [];
$catalogue_by_park = [];
$flat_catalogue = [];
$wait_values = [];
$open_count = 0;
$closed_count = 0;
$untracked_count = 0;

foreach ($structure_land as $landName => $attractions) {
    $catalogue_by_land[$landName] = [];
    $parkName = $land_profiles[$landName]['park'];

    if (!isset($catalogue_by_park[$parkName])) {
        $catalogue_by_park[$parkName] = [];
    }

    foreach ($attractions as $attraction) {
        $resolved = resolveAttractionState($attraction, $live_lookup, $live_is_available);
        $resolved['land'] = $landName;
        $resolved['park'] = $parkName;

        $catalogue_by_land[$landName][] = $resolved;
        $catalogue_by_park[$parkName][] = $resolved;
        $flat_catalogue[] = $resolved;

        if ($resolved['status'] === 'open' && is_numeric($resolved['wait_time'])) {
            $wait_values[] = (int) $resolved['wait_time'];
            $open_count++;
        } elseif ($resolved['status'] === 'closed') {
            $closed_count++;
        } else {
            $untracked_count++;
        }
    }
}

$total_attractions = totalAttractions($structure_land);
$avg_wait = !empty($wait_values) ? round(array_sum($wait_values) / count($wait_values)) : null;
$wait_history_records = recordWaitHistory($flat_catalogue);
$wait_history_profiles = buildWaitHistoryProfiles($wait_history_records, $flat_catalogue, $wait_alert_profiles);

$top_picks = array_values(array_filter($flat_catalogue, function ($attraction) {
    return $attraction['status'] === 'open' && is_numeric($attraction['wait_time']);
}));

usort($top_picks, function ($left, $right) {
    if ($left['wait_time'] === $right['wait_time']) {
        return strcmp($left['name'], $right['name']);
    }
    return $left['wait_time'] <=> $right['wait_time'];
});

$top_picks = array_slice($top_picks, 0, 8);
$best_pick = $top_picks[0] ?? null;

$peak_pick = null;
if (!empty($wait_values)) {
    $open_sorted_desc = array_values(array_filter($flat_catalogue, function ($attraction) {
        return $attraction['status'] === 'open' && is_numeric($attraction['wait_time']);
    }));

    usort($open_sorted_desc, function ($left, $right) {
        if ($left['wait_time'] === $right['wait_time']) {
            return strcmp($left['name'], $right['name']);
        }
        return $right['wait_time'] <=> $left['wait_time'];
    });

    $peak_pick = $open_sorted_desc[0] ?? null;
}

$land_summaries = [];
$park_summaries = [];
$best_land_name = null;
$best_land_score = null;

foreach ($catalogue_by_land as $landName => $attractions) {
    $landOpen = array_values(array_filter($attractions, function ($attraction) {
        return $attraction['status'] === 'open' && is_numeric($attraction['wait_time']);
    }));
    $landClosed = array_values(array_filter($attractions, function ($attraction) {
        return $attraction['status'] === 'closed';
    }));
    $landUntracked = array_values(array_filter($attractions, function ($attraction) {
        return $attraction['status'] !== 'open' && $attraction['status'] !== 'closed';
    }));

    usort($landOpen, function ($left, $right) {
        if ($left['wait_time'] === $right['wait_time']) {
            return strcmp($left['name'], $right['name']);
        }
        return $left['wait_time'] <=> $right['wait_time'];
    });

    $landAvgWait = !empty($landOpen) ? round(array_sum(array_column($landOpen, 'wait_time')) / count($landOpen)) : null;

    $land_summaries[$landName] = [
        'park' => $land_profiles[$landName]['park'],
        'count' => count($attractions),
        'open_count' => count($landOpen),
        'closed_count' => count($landClosed),
        'untracked_count' => count($landUntracked),
        'avg_wait' => $landAvgWait,
        'best_pick' => $landOpen[0] ?? null,
    ];

    if ($land_summaries[$landName]['open_count'] > 0) {
        $score = ($land_summaries[$landName]['open_count'] * 1000) - ($landAvgWait ?? 999);
        if ($best_land_score === null || $score > $best_land_score) {
            $best_land_score = $score;
            $best_land_name = $landName;
        }
    }
}

$best_park_name = null;
$best_park_score = null;

foreach ($catalogue_by_park as $parkName => $attractions) {
    $parkOpen = array_values(array_filter($attractions, function ($attraction) {
        return $attraction['status'] === 'open' && is_numeric($attraction['wait_time']);
    }));
    $parkClosed = array_values(array_filter($attractions, function ($attraction) {
        return $attraction['status'] === 'closed';
    }));
    $parkUntracked = array_values(array_filter($attractions, function ($attraction) {
        return $attraction['status'] !== 'open' && $attraction['status'] !== 'closed';
    }));

    usort($parkOpen, function ($left, $right) {
        if ($left['wait_time'] === $right['wait_time']) {
            return strcmp($left['name'], $right['name']);
        }
        return $left['wait_time'] <=> $right['wait_time'];
    });

    $parkAvgWait = !empty($parkOpen) ? round(array_sum(array_column($parkOpen, 'wait_time')) / count($parkOpen)) : null;

    $park_summaries[$parkName] = [
        'count' => count($attractions),
        'open_count' => count($parkOpen),
        'closed_count' => count($parkClosed),
        'untracked_count' => count($parkUntracked),
        'avg_wait' => $parkAvgWait,
        'best_pick' => $parkOpen[0] ?? null,
    ];

    if ($park_summaries[$parkName]['open_count'] > 0) {
        $score = ($park_summaries[$parkName]['open_count'] * 1000) - ($parkAvgWait ?? 999);
        if ($best_park_score === null || $score > $best_park_score) {
            $best_park_score = $score;
            $best_park_name = $parkName;
        }
    }
}

$current_closures = array_values(array_filter($flat_catalogue, function ($attraction) {
    return $attraction['status'] === 'closed';
}));

usort($current_closures, function ($left, $right) {
    if ($left['park'] === $right['park']) {
        return strcmp($left['name'], $right['name']);
    }
    return strcmp($left['park'], $right['park']);
});

$insight_open_candidates = array_values(array_filter($flat_catalogue, function ($attraction) {
    return $attraction['status'] === 'open' && is_numeric($attraction['wait_time']);
}));

usort($insight_open_candidates, function ($left, $right) {
    if ($left['wait_time'] === $right['wait_time']) {
        return strcmp($left['name'], $right['name']);
    }
    return $right['wait_time'] <=> $left['wait_time'];
});

$sync_time = date('H:i');
$sync_short_label = $live_is_available ? 'Mis a jour ' . $sync_time : 'Sync en pause';
$sync_full_label = $live_is_available
    ? 'Derniere mise a jour de la page a ' . $sync_time . ' (heure de Paris).'
    : 'La source live ne repond pas pour le moment. Le site reste consultable avec ses fiches et conseils.';
