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

$site_name = 'DLP Live Guide';
$site_tagline = 'Guide fan independant pour mieux vivre Disneyland Paris';
$legal_notice = 'Guide fan independant, non affilie a Disneyland Paris, Euro Disney Associes S.A.S. ou The Walt Disney Company.';

$structure_land = [
    'Frontierland' => [
        [
            'name' => 'Big Thunder Mountain',
            'aliases' => [],
            'summary' => 'Le gros aimant sensations du land, a traiter tot ou en fin de journee.',
            'tip' => 'Si la file reste douce avant la mi-journee, c est souvent un bon moment pour securiser ce classique.',
        ],
        [
            'name' => 'Phantom Manor',
            'aliases' => [],
            'summary' => 'Dark ride narratif relie a l histoire de Thunder Mesa.',
            'tip' => 'Observe le lien visuel entre le manoir et la ville miniere, c est l un des plus beaux recits de parc.',
        ],
        [
            'name' => 'Thunder Mesa Riverboat Landing',
            'aliases' => [],
            'summary' => 'Pause calme avec vues ouvertes sur Frontierland.',
            'tip' => 'Tres utile pour souffler sans quitter l ambiance du land.',
        ],
        [
            'name' => 'Disneyland Railroad - Frontierland Depot',
            'aliases' => ['Disneyland Railroad Frontierland Depot'],
            'summary' => 'Bon point de transition pour changer de zone sans perdre le rythme.',
            'tip' => 'Pense a l utiliser comme vrai outil de deplacement, pas seulement comme attraction.',
        ],
        [
            'name' => 'Frontierland Playground',
            'aliases' => [],
            'summary' => 'Solution simple pour laisser respirer le groupe entre deux temps forts.',
            'tip' => 'Pratique quand les grandes files montent d un coup.',
        ],
        [
            'name' => "Rustler Roundup Shootin' Gallery",
            'aliases' => [],
            'summary' => 'Petite activite Far West qui ajoute du rythme sans grosse attente.',
            'tip' => 'Bon sas de decontraction juste avant de repasser sur du plus intense.',
        ],
    ],
    'Discoveryland' => [
        [
            'name' => 'Star Wars Hyperspace Mountain',
            'aliases' => [],
            'summary' => 'Headliner nerveux a prioriser sur les bons creux.',
            'tip' => 'Quand la file descend, il vaut mieux agir vite: la remontee est souvent rapide.',
        ],
        [
            'name' => 'Star Tours: The Adventures Continue',
            'aliases' => ['Star Tours: The Adventures Continue*'],
            'summary' => 'Simulateur qui encaisse bien les visiteurs et peut devenir une bonne roue de secours.',
            'tip' => 'Ideal quand tu veux garder une thematique forte sans replonger dans une file geante.',
        ],
        [
            'name' => 'Buzz Lightyear Laser Blast',
            'aliases' => [],
            'summary' => 'Attraction familiale interactive, facile a recommander a presque tout le monde.',
            'tip' => 'Bonne option a caser entre deux gros objectifs plus demandants.',
        ],
        [
            'name' => 'Les Mysteres du Nautilus',
            'aliases' => ['Les Mysteres du Nautilus', 'Les Mysteres du Nautilus®'],
            'summary' => 'Walkthrough plus calme, parfait pour reequilibrer le tempo.',
            'tip' => 'Le land raconte surtout un futur imaginaire a la Jules Verne, et cette attraction le rappelle tres bien.',
        ],
        [
            'name' => 'Orbitron',
            'aliases' => ['Orbitron®'],
            'summary' => 'Vue sympa sur la place centrale de Discoveryland.',
            'tip' => 'A garder pour un moment ou tu veux une experience courte mais visuellement forte.',
        ],
        [
            'name' => 'Autopia',
            'aliases' => ['Autopia, presented by Avis'],
            'summary' => 'Classique famille dont la file demande un peu de lecture terrain.',
            'tip' => 'Plus agreable quand tu sais deja que le groupe veut ce type d experience.',
        ],
    ],
    'Fantasyland' => [
        [
            'name' => "Peter Pan's Flight",
            'aliases' => [],
            'summary' => 'Attraction ultra demandee, a viser avec une vraie strategie.',
            'tip' => 'Si le temps baisse franchement, il faut le prendre comme un signal plutot rare.',
        ],
        [
            'name' => "it's a small world",
            'aliases' => ['"it\'s a small world"'],
            'summary' => 'Tres bon refuge quand on veut garder de la capacite et du confort.',
            'tip' => 'Super point d appui pour reprendre la main sur une journee chargee.',
        ],
        [
            'name' => 'Dumbo the Flying Elephant',
            'aliases' => [],
            'summary' => 'Classique familial tres visible, souvent choisi tres tot par les familles.',
            'tip' => 'A prevoir quand le groupe veut les symboles du land plus que le rendement.',
        ],
        [
            'name' => "Mad Hatter's Tea Cups",
            'aliases' => [],
            'summary' => 'Pause ludique et rapide pour casser une sequence de grosses files.',
            'tip' => 'Parfait pour relancer l energie du groupe sans tout reconfigurer.',
        ],
        [
            'name' => 'Le Carrousel de Lancelot',
            'aliases' => [],
            'summary' => 'Repere central photogenique et facile a glisser dans la visite.',
            'tip' => 'Un bon pivot quand vous changez d allure entre deux objectifs.',
        ],
        [
            'name' => 'Casey Jr. - le Petit Train du Cirque',
            'aliases' => ['Casey Jr. – le Petit Train du Cirque'],
            'summary' => 'Petit coaster familial qui demande juste un peu d anticipation.',
            'tip' => 'Souvent plus agreable quand il est choisi deliberement, pas au hasard.',
        ],
        [
            'name' => 'Le Pays des Contes de Fees',
            'aliases' => ['Le Pays des Contes de Fees, presented by Vittel', 'Le Pays des Contes de Fées, presented by Vittel'],
            'summary' => 'Balade douce utile pour redescendre en intensite.',
            'tip' => 'A glisser quand le groupe veut du calme sans quitter le land.',
        ],
        [
            'name' => 'Blanche-Neige et les Sept Nains',
            'aliases' => ['Blanche-Neige et les Sept Nains®'],
            'summary' => 'Dark ride patrimonial a traiter comme une piece d ambiance plus que comme un rendement pur.',
            'tip' => 'Mieux apprecie quand on le prend pour son charme et son heritage.',
        ],
        [
            'name' => 'Les Voyages de Pinocchio',
            'aliases' => [],
            'summary' => 'Autre dark ride court mais riche en scenes compactes.',
            'tip' => 'Le genre d attraction qui justifie une rubrique details caches sur le site.',
        ],
        [
            'name' => "Alice's Curious Labyrinth",
            'aliases' => [],
            'summary' => 'Exploration libre tres utile pour varier la journee.',
            'tip' => 'Le meilleur moment est souvent quand on veut se remettre en mouvement sans pression de file.',
        ],
    ],
    'Adventureland' => [
        [
            'name' => 'Pirates of the Caribbean',
            'aliases' => [],
            'summary' => 'Immersion solide et file souvent mieux absorbee qu elle n en a l air.',
            'tip' => 'Tres bon joker si une autre grosse attraction se tend brutalement.',
        ],
        [
            'name' => 'Indiana Jones and the Temple of Peril',
            'aliases' => ['Indiana Jones™ and the Temple of Peril'],
            'summary' => 'Coaster court et franc, a jouer sur les fenetres efficaces.',
            'tip' => 'Quand le temps est raisonnable, l arbitrage se fait vite en sa faveur.',
        ],
        [
            'name' => 'Adventure Isle',
            'aliases' => [],
            'summary' => 'Zone libre ideale pour offrir autre chose qu une simple file.',
            'tip' => 'Ponts, grottes et points de vue en font une excellente respiration.',
        ],
        [
            'name' => 'La Cabane des Robinson',
            'aliases' => [],
            'summary' => 'Parcours a part, utile pour enrichir la visite au dela des waits.',
            'tip' => 'Tres bonne matiere pour un futur volet easter eggs.',
        ],
        [
            'name' => "Pirates' Beach",
            'aliases' => [],
            'summary' => 'Point pause simple a recommander aux groupes avec enfants.',
            'tip' => 'A garder en reserve quand il faut detendre le rythme sans quitter Adventureland.',
        ],
    ],
];

$content_highlights = [
    [
        'tag' => 'Live',
        'title' => 'Temps d attente utiles, pas juste affiches',
        'copy' => 'Le site doit montrer les bonnes fenetres, les fermetures et les arbitrages malins plus vite que les visiteurs ne les trouvent seuls.',
        'href' => 'wait-times.php',
        'cta' => 'Ouvrir le tableau live',
    ],
    [
        'tag' => 'Editorial',
        'title' => 'Actus resort pensees pour le terrain',
        'copy' => 'Travaux, saisons, spectacles et changements pratiques peuvent vivre dans un format simple, mobile et rapide a lire.',
        'href' => '#timeline',
        'cta' => 'Voir la logique editoriale',
    ],
    [
        'tag' => 'Secrets',
        'title' => 'Easter eggs qui donnent envie de lever les yeux',
        'copy' => 'Ton futur avantage peut venir du regard fan: raconter ce que les visiteurs passent devant sans le voir.',
        'href' => '#secrets',
        'cta' => 'Parcourir les details',
    ],
];

$easter_egg_cards = [
    [
        'land' => 'Frontierland',
        'title' => 'Thunder Mesa raconte une seule grande histoire',
        'copy' => 'Big Thunder Mountain, Phantom Manor et le decor de la ville miniere parlent le meme langage visuel. C est un angle parfait pour une future rubrique lore.',
    ],
    [
        'land' => 'Discoveryland',
        'title' => 'Le futur ici est retro et litteraire',
        'copy' => 'Le land ne copie pas un simple futur science-fiction. Il melange Jules Verne, inventions impossibles et architecture a grande echelle.',
    ],
    [
        'land' => 'Adventureland',
        'title' => 'Les detours sont parfois plus memorables que les rides',
        'copy' => 'Entre grottes, cabanes et passages, Adventureland est ideal pour des anecdotes courtes que les visiteurs peuvent verifier en marchant.',
    ],
];

$visit_timeline = [
    [
        'moment' => 'Avant l ouverture',
        'title' => 'Verifier la sante du parc',
        'copy' => 'Scanner les fermetures et les temps deja hauts evite de construire une journee sur des suppositions.',
    ],
    [
        'moment' => 'Fin de matinee',
        'title' => 'Reequilibrer avec des experiences intelligentes',
        'copy' => 'Quand les grosses files montent, ton site doit proposer des alternatives qui gardent de la valeur sans casser l energie.',
    ],
    [
        'moment' => 'Soiree',
        'title' => 'Retenter les gros objectifs au bon moment',
        'copy' => 'Apres certains shows ou a l approche de la fermeture, quelques attractions redeviennent plus accessibles.',
    ],
];

$brand_principles = [
    [
        'title' => 'Qualite premium, statut fan tres clair',
        'copy' => 'Le design inspire confiance, mais le site dit explicitement qu il est independant. C est la bonne maniere d etre pris au serieux sans semer la confusion.',
    ],
    [
        'title' => 'Une vraie valeur d usage',
        'copy' => 'Temps live, synthese, conseils courts et lecture des lands: le site peut devenir un compagnon, pas juste une vitrine.',
    ],
    [
        'title' => 'Une base deja exploitable pour une appli',
        'copy' => 'Le contenu, les stats et les cartes attractions peuvent ensuite glisser vers une experience mobile plus riche.',
    ],
];

$url = 'https://api.themeparks.wiki/v1/entity/e8d0207f-da8a-4048-bec8-117aa946b2c2/live';
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: DLP-Live-Guide/2.0\r\n",
        'timeout' => 8,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
];

$context = stream_context_create($opts);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    $opts['ssl'] = [
        'verify_peer' => false,
        'verify_peer_name' => false,
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
}

$live_raw_attractions = [];
$mes_attractions = [];
$live_is_available = false;

if ($response) {
    $payload = json_decode($response, true);
    extractLiveAttractions($payload, $live_raw_attractions);
    $mes_attractions = buildLiveLookup($live_raw_attractions);
    $live_is_available = !empty($mes_attractions);
}

$catalogue_by_land = [];
$flat_catalogue = [];
$wait_values = [];
$open_count = 0;
$closed_count = 0;
$untracked_count = 0;

foreach ($structure_land as $landName => $attractions) {
    $catalogue_by_land[$landName] = [];

    foreach ($attractions as $attraction) {
        $resolved = resolveAttractionState($attraction, $mes_attractions, $live_is_available);
        $resolved['land'] = $landName;

        $catalogue_by_land[$landName][] = $resolved;
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

$top_picks = array_values(array_filter($flat_catalogue, function ($attraction) {
    return $attraction['status'] === 'open' && is_numeric($attraction['wait_time']);
}));

usort($top_picks, function ($left, $right) {
    if ($left['wait_time'] === $right['wait_time']) {
        return strcmp($left['name'], $right['name']);
    }

    return $left['wait_time'] <=> $right['wait_time'];
});

$top_picks = array_slice($top_picks, 0, 4);
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

    $landAvgWait = !empty($landOpen)
        ? round(array_sum(array_column($landOpen, 'wait_time')) / count($landOpen))
        : null;

    $landSummariesEntry = [
        'count' => count($attractions),
        'open_count' => count($landOpen),
        'closed_count' => count($landClosed),
        'untracked_count' => count($landUntracked),
        'avg_wait' => $landAvgWait,
        'best_pick' => $landOpen[0] ?? null,
    ];

    $land_summaries[$landName] = $landSummariesEntry;

    if ($landSummariesEntry['open_count'] > 0) {
        $score = ($landSummariesEntry['open_count'] * 1000) - ($landAvgWait ?? 999);
        if ($best_land_score === null || $score > $best_land_score) {
            $best_land_score = $score;
            $best_land_name = $landName;
        }
    }
}

$sync_time = date('H:i');
$sync_short_label = $live_is_available ? 'Mis a jour ' . $sync_time : 'Sync en pause';
$sync_full_label = $live_is_available
    ? 'Derniere mise a jour de la page a ' . $sync_time . ' (heure de Paris).'
    : 'La source live ne repond pas pour le moment. Le guide reste consultable.';

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
?>
