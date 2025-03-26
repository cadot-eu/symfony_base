# Symfony Base

- Base pour déployer une application symfony webapp avec mini dashboard admin automatique.
- Stimulus & Controller pour modification en directe

## Installation

- git clone
- lancer ./updateFiles.sh (il va mettre pour vous à jour les fichiers

## Lancement

- dc up -d
- caddy run
- supprimer le .git
- git init
- modifier .env.test (entre '') et .env.dev (sans '') en mettant des secrets (différents)
- sc d:f:l: -n pour installer un user <a@aa.aa> avec * comme admin

## utilisation du Dashboard automatique

- créer une méthode dans une entité fieldsCrud qui retourne un tableau de noms en minuscule des méthodes à afficher
- options à la fin du nom:
- - *=>la modification est possible
- - #=>on limite l'affichage à 10 caractère et on créé un infobulle avec le texte complet

## utilisation des scripts de debs

- dsh: entrer dans le docker
- dlogs: voir les logs
