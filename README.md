# Symfony Base

Base pour déployer une application symfony webapp avec mini dashboard admin automatique.
Stimulus & Controller pour modification en directe

## Installation

- `git clone`
- lancer `./updateFiles.sh` (il va mettre pour vous à jour les fichiers

## Lancement

- `dc up -d`
- `caddy run`
- supprimer le `.git`
- `git init`
- modifier `.env.test` (entre '') et `.env.dev` (sans '') en mettant des secrets (différents)
- `sc d:f:l: -n` pour installer un user `<a@aa.aa>` avec `*` comme admin

## utilisation du Dashboard automatique

- créer une méthode dans une entité `fieldsCrud` qui retourne un tableau de noms en minuscule des **méthodes** à afficher
- options à la fin du nom:
- - `*` => la modification est possible
- - `#` => on limite l'affichage à 10 caractère et on créé un modal avec le texte complet

example:

```php
public function fieldsCrud(): array
{
    return ['tuyaId', 'nom*''];
}
```

- créer une fontion __toSring pour renvoyer le nom à afficher dans les enfants ... au lieu de l'id

- crééer une méthode `AddButtonsToCrud` qui retourne un tableau de champs de la forme:
- - texte de l'entité
- - url: vous pouvet mettre {{ path('') })}}
- - target: pas obligatoire
- - icon: pas obligatoire

example:

```php
public function AddButtonsToCrud(): array
    {
        return [
            'slug' => [
                'url' => 'http://google.com',
                'target' => '_blank',
                'icon' => 'fas fa-globe',

            ]
        ];
    }
```

example:

- créer une méthode `InfoIdCrud` pour afficher une tooltip d'information sur l'id

```php
 public function InfoIdCrud(): array
    {
        return [
            'tuyaId' => $this->getTuyaId(),
            'nom' => $this->getNom(),
            'switchCode' => $this->getSwitchCode(),
            'UUID' => $this->getUuid(),
            'UID' => $this->getUid(),
        ];
    }
```

## utilisation des scripts de debs

- `dsh`: entrer dans le docker
- `dlogs`: voir les logs

# labo

# tuyadomotic
