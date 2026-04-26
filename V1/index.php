<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$url = "https://api.themeparks.wiki/v1/entity/e8d0207f-da8a-4048-bec8-117aa946b2c2/live";

$opts = array(
        "http" => array("method" => "GET", "header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 5),
        "ssl" => array("verify_peer" => false, "verify_peer_name" => false)
);

$context = stream_context_create($opts);
$reponse = @file_get_contents($url, false, $context);

$mes_attractions = array();
$statut_msg = "";

if ($reponse) {
    $donnees = json_decode($reponse, true);

    if (!function_exists('chercherAttractions')) {
        function chercherAttractions($tableau, &$resultats) {
            if (!is_array($tableau)) return;

            // 1. liste à supprimer
            $exclure = array("Liberty Arcade", "Discovery Arcade", "Frontierland Playground", "Entry to World of Frozen", "La Galerie de la Belle au Bois Dormant", "Pirate Galleon", "Sleeping Beauty Castle");

            foreach ($tableau as $cle => $valeur) {
                if (isset($valeur['entityType']) && $valeur['entityType'] == "ATTRACTION") {
                    $nom = $valeur['name'];

                    // 2. On vérifie si le nom n'est PAS dans la liste noire
                    if (!in_array($nom, $exclure)) {
                        if (isset($valeur['queue']['STANDBY']['waitTime'])) {
                            $resultats[$nom] = $valeur['queue']['STANDBY']['waitTime'];
                        } else {
                            $resultats[$nom] = "Fermé";
                        }
                    }
                }

                if (is_array($valeur)) {
                    chercherAttractions($valeur, $resultats);
                }
            }
        }
    }

    chercherAttractions($donnees, $mes_attractions);
    ksort($mes_attractions);

    if (count($mes_attractions) > 0) {
        $statut_msg = "";
    } else {
        $statut_msg = "⚠️ Connecté, mais aucune attraction trouvée.";
    }
} else {
    $statut_msg = "❌ Erreur réseau.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DLP Live - Attractions Uniquement</title>
    <style>
        body { font-family: sans-serif; background: #eee; padding: 20px; }
        .debug { background: #333; color: #fff; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 0.8em; }
        .card { background: white; padding: 15px; margin: 8px 0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .nom { font-weight: bold; color: #333; }
        .temps { font-weight: bold; padding: 5px 10px; border-radius: 5px; min-width: 80px; text-align: center; }
        .ouvert { background: #d4edda; color: #155724; }
        .ferme { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<?php if ($statut_msg !== "") : ?>
    <div class="debug">Statut : <?php echo $statut_msg; ?></div>
<?php endif; ?>

<h1>🎡 Disneyland Paris - Attractions</h1>

<?php if (empty($mes_attractions)) : ?>
    <p>Aucune attraction trouvée.</p>
<?php else : ?>
    <?php foreach ($mes_attractions as $nom => $temps) : ?>
        <div class="card">
            <span class="nom"><?php echo $nom; ?></span>
            <span class="temps <?php echo ($temps === 'Fermé') ? 'ferme' : 'ouvert'; ?>">
                <?php echo ($temps === 'Fermé') ? $temps : $temps . ' min'; ?>
            </span>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>