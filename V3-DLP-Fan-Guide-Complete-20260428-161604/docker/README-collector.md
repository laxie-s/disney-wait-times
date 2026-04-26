# Collecte automatique des temps d attente

Le JavaScript du site ne peut pas lancer une releve tout seul si personne n ouvre une page.

Pour garder l historique meme sans visiteurs, il faut executer ce script serveur toutes les 30 minutes :

- `jobs/collect-waits.php`

Ce script :

- appelle l API live
- recharge le catalogue
- enregistre l historique dans `storage/wait-history.json`
- ecrit un etat de sante dans `storage/collector-status.json`
- retourne un code erreur si l API ne repond pas

## Option simple dans Docker

Si ton conteneur web contient deja `php`, tu peux ajouter un cron interne avec le fichier :

- `docker/collect-waits.cron`

Exemple de commande cron :

```cron
*/30 * * * * php /var/www/html/jobs/collect-waits.php >> /proc/1/fd/1 2>> /proc/1/fd/2
```

## Option recommandee

Le plus propre est souvent :

1. un conteneur web pour le site
2. un cronjob separe qui lance `php /var/www/html/jobs/collect-waits.php`

Comme ca, meme si le trafic tombe a zero, l historique continue de grandir.

## Fichiers de stockage utiles

- `storage/wait-history.json`
- `storage/collector-status.json`

## Important

Le dossier `storage/` doit etre persistant dans Docker, sinon tu perdras l historique a chaque redemarrage du conteneur.
