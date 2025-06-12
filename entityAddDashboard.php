<?php

/**
 * Script CLI pour générer automatiquement les méthodes CRUD des entités
 */

class CrudGenerator
{
    private $entitiesPath;
    private $entities = [];

    public function __construct($entitiesPath = './src/Entity/')
    {
        $this->entitiesPath = rtrim($entitiesPath, '/') . '/';
        $this->loadEntities();
    }

    /**
     * Charge la liste des entités depuis le répertoire
     */
    private function loadEntities()
    {
        if (!is_dir($this->entitiesPath)) {
            throw new Exception("Le répertoire des entités n'existe pas: {$this->entitiesPath}");
        }

        $files = glob($this->entitiesPath . '*.php');
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $this->entities[] = [
                'name' => $className,
                'file' => $file,
                'class' => $className
            ];
        }

        if (empty($this->entities)) {
            throw new Exception("Aucune entité trouvée dans {$this->entitiesPath}");
        }
    }

    /**
     * Affiche la liste des entités et permet la sélection
     */
    public function selectEntity()
    {
        echo "\n=== Générateur CRUD pour Entités ===\n\n";
        echo "Entités disponibles :\n";

        foreach ($this->entities as $index => $entity) {
            echo ($index + 1) . ". " . $entity['name'] . "\n";
        }

        echo "\nSélectionnez une entité (numéro) : ";
        $choice = trim(fgets(STDIN));

        if (!is_numeric($choice) || $choice < 1 || $choice > count($this->entities)) {
            echo "Sélection invalide.\n";
            return $this->selectEntity();
        }

        return $this->entities[$choice - 1];
    }

    /**
     * Charge la classe et extrait les propriétés avec ReflectionClass
     */
    private function extractPropertiesWithReflection($entity)
    {
        // Inclure le fichier pour charger la classe
        require_once $entity['file'];

        // Détecter le namespace et le nom de classe complet
        $content = file_get_contents($entity['file']);
        $namespace = '';

        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            $namespace = $matches[1] . '\\';
        }

        $fullClassName = $namespace . $entity['class'];

        try {
            $reflection = new ReflectionClass($fullClassName);
            $properties = [];

            // Récupérer toutes les propriétés (publiques, protégées, privées)
            $reflectionProperties = $reflection->getProperties();

            foreach ($reflectionProperties as $property) {
                $propertyName = $property->getName();

                // Exclure 'id' et les propriétés statiques
                if ($propertyName !== 'id' && $propertyName !== 'ordre' && !$property->isStatic()) {
                    $properties[] = $propertyName;
                }
            }

            return $properties;
        } catch (ReflectionException $e) {
            echo "⚠️  Erreur de réflexion pour la classe $fullClassName: " . $e->getMessage() . "\n";
            return $this->fallbackPropertyExtraction($entity['file']);
        } catch (Error $e) {
            echo "⚠️  Erreur lors du chargement de la classe: " . $e->getMessage() . "\n";
            return $this->fallbackPropertyExtraction($entity['file']);
        }
    }

    /**
     * Méthode de secours pour extraire les propriétés par analyse de fichier
     */
    private function fallbackPropertyExtraction($filePath)
    {
        echo "Utilisation de la méthode de secours...\n";
        $content = file_get_contents($filePath);
        $properties = [];

        // Pattern amélioré pour capturer les propriétés Symfony
        $patterns = [
            '/(?:#\[.*?\])?\s*(?:private|protected|public)\s+(?:\w+\s+)?(?:\??\w+\s+)?\$(\w+)/m',
            '/(?:private|protected|public)\s+\$(\w+)/m'
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $property) {
                    if (!in_array($property, ['id']) && !in_array($property, $properties)) {
                        $properties[] = $property;
                    }
                }
            }
        }

        return $properties;
    }

    /**
     * Génère la structure CRUD pour l'entité
     */
    private function generateCrudStructure($entityName, $properties)
    {
        $crud = [
            "//'Ordre' => ['propriete' => 'ordre']",
            "// 'ActionsTableauEntite' => [
             //   'slug' => [
             //       'url' => 'http://google.com', //'http://google.com/{{entity}}/{{ligne.id}}' possible
             //       'target' => '_blank',
             //       'icon' => 'bi bi-globe2',
             //       'texte' => 'Voir le site',
             //       'turbo' => false
             //   ]
            //]",
            "'id' => [
            //'InfoIdCrud' => [
            //'devis' => $this->devis->getLieu(),
            //],
            'Actions' => [] // comme ActionTableauEntite
            ] "
        ];

        foreach ($properties as $property) {
            $crud[] = "'$property' => ['Edition' => true, 'tooltip' => null, 'label' => null]";
        }

        $crudString = "[\n        " . implode(",\n        ", $crud) . "\n    ]";

        return "    public function cruds()\n    {\n        return $crudString;\n    }";
    }

    /**
     * Supprime une méthode existante du contenu de la classe
     */
    private function removeCrudMethod($content)
    {
        $lines = explode("\n", $content);
        $newLines = [];
        $inCrudsMethod = false;
        $braceCount = 0;

        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];

            // Détecter le début de la méthode cruds
            if (!$inCrudsMethod && preg_match('/public\s+function\s+cruds\s*\(/i', $line)) {
                $inCrudsMethod = true;

                // Compter les accolades sur cette ligne
                $openBraces = substr_count($line, '{');
                $closeBraces = substr_count($line, '}');
                $braceCount = $openBraces - $closeBraces;

                // Si la méthode complète est sur une ligne (cas rare)
                if ($braceCount === 0 && $openBraces > 0) {
                    $inCrudsMethod = false;
                }
                continue; // Ne pas ajouter cette ligne
            }

            // Si on est dans la méthode cruds
            if ($inCrudsMethod) {
                // Compter les accolades
                $openBraces = substr_count($line, '{');
                $closeBraces = substr_count($line, '}');
                $braceCount += $openBraces - $closeBraces;

                // Si on a fermé toutes les accolades, on sort de la méthode
                if ($braceCount <= 0) {
                    $inCrudsMethod = false;
                }
                continue; // Ne pas ajouter cette ligne
            }

            // Ajouter la ligne si on n'est pas dans la méthode cruds
            $newLines[] = $line;
        }

        return implode("\n", $newLines);
    }

    /**
     * Ajoute la nouvelle méthode CRUD à la classe
     */
    private function addCrudMethod($content, $crudMethod)
    {
        // Trouve la position avant la dernière accolade fermante
        $lastBracePos = strrpos($content, '}');
        if ($lastBracePos === false) {
            throw new Exception("Structure de classe invalide");
        }

        $before = substr($content, 0, $lastBracePos);
        $after = substr($content, $lastBracePos);

        return   $before . "\n"  . $crudMethod . "" . $after;
    }

    /**
     * Demande confirmation avant de procéder
     */
    private function askConfirmation($message)
    {
        echo "\n$message (y/N) : ";
        $response = trim(fgets(STDIN));
        return strtolower($response) === 'y' || strtolower($response) === 'yes';
    }

    /**
     * Saisie manuelle des propriétés
     */
    private function manualPropertyInput()
    {
        $properties = [];
        echo "\nSaisissez les propriétés une par une (tapez 'fin' pour terminer) :\n";

        while (true) {
            echo "Propriété : ";
            $property = trim(fgets(STDIN));

            if (strtolower($property) === 'fin' || empty($property)) {
                break;
            }

            if (!in_array($property, $properties)) {
                $properties[] = $property;
                echo "✓ Propriété '$property' ajoutée.\n";
            } else {
                echo "⚠️  Propriété '$property' déjà ajoutée.\n";
            }
        }

        return $properties;
    }

    /**
     * Traite l'entité sélectionnée
     */
    public function processEntity($entity)
    {
        echo "\nEntité sélectionnée : " . $entity['name'] . "\n";
        echo "Fichier : " . $entity['file'] . "\n";

        // Lire le contenu du fichier
        $content = file_get_contents($entity['file']);

        // Vérifier si une méthode cruds existe déjà
        $hasCrudMethod = preg_match('/public\s+function\s+cruds\s*\(/i', $content);

        if ($hasCrudMethod) {
            echo "\n⚠️  Une méthode cruds() existe déjà dans cette entité.\n";
            if (!$this->askConfirmation("Voulez-vous la remplacer ?")) {
                echo "Opération annulée.\n";
                return;
            }

            // Supprimer l'ancienne méthode
            $content = $this->removeCrudMethod($content);
            echo "✓ Ancienne méthode cruds() supprimée.\n";
        }

        // Extraire les propriétés avec ReflectionClass
        echo "\nAnalyse des propriétés avec ReflectionClass...\n";
        $properties = $this->extractPropertiesWithReflection($entity);

        if (empty($properties)) {
            echo "\n⚠️  Aucune propriété détectée automatiquement.\n";
            if ($this->askConfirmation("Voulez-vous saisir manuellement les propriétés ?")) {
                $properties = $this->manualPropertyInput();
            } else {
                echo "Impossible de continuer sans propriétés.\n";
                return;
            }
        } else {
            echo "\nPropriétés détectées : " . implode(', ', $properties) . "\n";
        }

        // Générer la nouvelle méthode CRUD
        $crudMethod = $this->generateCrudStructure($entity['name'], $properties);

        // Ajouter la nouvelle méthode
        $newContent = $this->addCrudMethod($content, $crudMethod);



        // Sauvegarder le fichier
        if (file_put_contents($entity['file'], $newContent)) {
            echo "\n✅ Méthode CRUD générée avec succès dans " . $entity['file'] . "\n";

            // Afficher un aperçu de la méthode générée
            echo "\n--- Aperçu de la méthode générée ---\n";
            echo $crudMethod . "\n";
            echo "--- Fin de l'aperçu ---\n";
        } else {
            echo "\n❌ Erreur lors de la sauvegarde du fichier.\n";
        }
    }

    /**
     * Lance le processus principal
     */
    public function run()
    {
        try {
            $selectedEntity = $this->selectEntity();
            $this->processEntity($selectedEntity);
        } catch (Exception $e) {
            echo "\n❌ Erreur : " . $e->getMessage() . "\n";
        }
    }
}

// Point d'entrée du script
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

// Vérifier les arguments
$entitiesPath = isset($argv[1]) ? $argv[1] : './src/Entity/';

echo "Chemin des entités : $entitiesPath\n";

try {
    $generator = new CrudGenerator($entitiesPath);
    $generator->run();
} catch (Exception $e) {
    echo "\n❌ Erreur fatale : " . $e->getMessage() . "\n";
    echo "\nUtilisation : php " . basename(__FILE__) . " [chemin_vers_entities]\n";
    echo "Exemple : php " . basename(__FILE__) . " ./src/Entity/\n";
}
