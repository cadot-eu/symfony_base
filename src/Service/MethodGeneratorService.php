<?php

namespace App\Service;

use App\Service\DeepseekApiService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class MethodGeneratorService
{
    private DeepseekApiService $deepseekApiService;

    public function __construct(DeepseekApiService $deepseekApiService)
    {
        $this->deepseekApiService = $deepseekApiService;
    }

    public function generate(array $data, callable $output = null): array
    {
        // Vérification des clés requises dans $data
        foreach (['file', 'method', 'params', 'goal'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new \InvalidArgumentException("Missing required key '$requiredKey' in data array.");
            }
        }

        $fs = new Filesystem();
        $targetFile = $this->getProjectDir() . '/src/' . $data['file'];
        $testFileRelative = preg_replace('/\.php$/', 'Test.php', $data['file']);
        $testFile = $this->getProjectDir() . '/tests/' . $testFileRelative;
        $testClass = pathinfo($data['file'], PATHINFO_FILENAME) . 'Test';

        $output('Début du process...' . PHP_EOL);
        $output('Payload reçu : ' . print_r($data, true) . PHP_EOL);
        $output('Traitement en cours...' . PHP_EOL);

        $className = pathinfo($data['file'], PATHINFO_FILENAME);
        $classFqcn = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $data['file']);

        $maxTries = 5;
        $iteration = 0;
        $phpunitOutput = '';
        $success = false;

        $model = 'deepseek';
        $output('Modèle IA utilisé  : ' . $model . PHP_EOL);

        $startTime = microtime(true);

        while ($iteration < $maxTries) {
            $iteration++;
            // Prépare le prompt, ajoute le retour PHPUnit si ce n'est pas la première itération
            $extra = '';
            if ($iteration > 1) {
                $extra = "\nVoici le retour de PHPUnit sur ta précédente proposition, corrige la méthode pour que le test passe :\n" . $phpunitOutput;
            }
            $addRoute = !empty($data['add_route']);
            $routeInstruction = $addRoute
                ? "Ajoute l'attribut #[Route('/nom-de-ta-route', name: 'auto_route')] juste au-dessus de la méthode générée, comme pour une action de contrôleur Symfony 6+."
                : "";
            $prompt = sprintf(
                "%s\n%s\n%s\n\n" .
                    "Réponds STRICTEMENT avec deux blocs de code PHP distincts, sans aucun texte autour, dans ce format :\n" .
                    "```php\n// méthode à ajouter dans App\\%s\npublic function %s(%s)\n{\n    // ...\n}\n```\n" .
                    "```php\n// test PHPUnit pour cette méthode\npublic function test%s()\n{\n    \$service = new \\%s();\n    // ...\n}\n```\n" .
                    "Dans le bloc du test, instancie TOUJOURS la classe cible avec son FQCN (exemple : \$service = new \\App\\Service\\MyService();). " .
                    "N'utilise jamais de namespace, de use, ni de balise <?php dans les blocs. " .
                    "La première réponse doit être la méthode seule, la seconde le test seul. " .
                    "Exemple :\n" .
                    "```php\npublic function foo(int \$a)\n{\n    return \$a + 1;\n}\n```\n" .
                    "```php\npublic function testFoo()\n{\n    \$service = new \\App\\Service\\MaClasse();\n    \$this->assertEquals(2, \$service->foo(1));\n}\n```\n",
                "Ajoute dans le fichier {$data['file']} une méthode nommée {$data['method']} avec la signature ({$data['params']}). But : {$data['goal']}.",
                $extra,
                $routeInstruction,
                str_replace('/', '\\', $data['file']),
                $data['method'],
                $data['params'],
                ucfirst($data['method']),
                $classFqcn
            );

            // Appel Deepseek uniquement
            $apiResponse = $this->deepseekApiService->generateMethod(['prompt' => $prompt]);
            $content = $apiResponse['choices'][0]['message']['content'] ?? '';

            $blocks = $this->extractPhpCodeBlocks($content);

            // Vérification : réponse IA vide ou mal formatée
            if (empty($blocks) || empty($blocks[0]) || empty($blocks[1])) {
                $output("ERREUR : La réponse du modèle IA ne contient pas de méthode ou de test valide." . PHP_EOL);
                $output("Réponse brute : " . var_export($content, true) . PHP_EOL);
                return [
                    'success' => false,
                    'error' => "Réponse du modèle IA non exploitable (aucun code PHP détecté)."
                ];
            }

            $methodCodeRaw = $blocks[0];
            $testMethod = $blocks[1] ?? '';

            $output('Extraction de la méthode...' . PHP_EOL);

            $methodCode = $this->extractNamedMethod($methodCodeRaw, $data['method']);
            if (!$methodCode) {
                $methodCode = $methodCodeRaw;
            }
            $output('Méthode générée : ' . $methodCode . PHP_EOL);
            $output('Méthode de test générée : ' . $testMethod . PHP_EOL);

            // Insérer/remplacer la méthode dans le fichier cible
            $output('Insertion de la méthode dans le fichier cible...' . PHP_EOL);
            $fileContent = file_get_contents($targetFile);
            // Supprime toutes les routes #[Route(...)] juste avant la méthode cible
            $fileContent = $this->removeRouteAttributesBeforeMethod($fileContent, $data['method']);
            $fileContent = $this->removeMethodFromClass($fileContent, $data['method']);
            $fileContent = preg_replace('/}\s*$/', "\n\n" . $methodCode . "\n}", $fileContent, 1);

            // Nettoyage des lignes vides consécutives
            $fileContent = preg_replace("/\n{3,}/", "\n\n", $fileContent);

            file_put_contents($targetFile, $fileContent);
            $output("Méthode insérée dans $targetFile" . PHP_EOL);

            // Écrire le test dans le bon fichier de test
            $output('Préparation du fichier de test...' . PHP_EOL);
            $namespaceParts = explode('/', dirname($data['file']));
            $namespace = 'App\\Tests';
            if ($namespaceParts[0] !== '.') {
                foreach ($namespaceParts as $part) {
                    if ($part && $part !== '.') {
                        $namespace .= '\\' . $part;
                    }
                }
            }
            $namespace = rtrim($namespace, '\\');
            $testSkeleton = "<?php\n\nnamespace $namespace;\n\nuse PHPUnit\\Framework\\TestCase;\n\nclass $testClass extends TestCase\n{\n}\n";
            file_put_contents($testFile, $testSkeleton);

            $testMembers = $this->extractTestMembers($testMethod);
            $testContent = file_get_contents($testFile);
            $testContent = $this->removeMethodFromClass($testContent, 'test' . ucfirst($data['method']));
            $testContent = preg_replace('/}\s*$/', "\n\n" . $testMembers . "\n}", $testContent, 1);
            file_put_contents($testFile, $testContent);

            // Lancer PHPUnit sur ce test avec Process
            $output('Lancement de PHPUnit...' . PHP_EOL);
            $phpunitCmd = [
                'php',
                $this->getProjectDir() . '/bin/phpunit',
                '--stop-on-failure',
                '--filter',
                $testClass
            ];
            $process = new \Symfony\Component\Process\Process($phpunitCmd);
            $process->setWorkingDirectory($this->getProjectDir());
            $process->setTimeout(30);
            $process->run();

            $phpunitOutput = $process->getOutput() . $process->getErrorOutput();
            $phpunitLines = preg_split('/\r\n|\r|\n/', trim($phpunitOutput));
            $phpunitSummary = end($phpunitLines);

            $output("Résultat PHPUnit : " . $phpunitSummary . PHP_EOL);

            // Ajout : arrêt immédiat si erreur assertion inconnue
            if (strpos($phpunitOutput, 'Call to undefined method') !== false && strpos($phpunitOutput, 'assertStringEqual') !== false) {
                $output("ERREUR : Le test utilise une assertion inconnue (assertStringEqual). Arrêt du process." . PHP_EOL);
                return [
                    'success' => false,
                    'error' => "Le test utilise une assertion inconnue (assertStringEqual)."
                ];
            }

            if ($process->isSuccessful() && strpos($phpunitOutput, 'FAILURES') === false && strpos($phpunitOutput, 'ERRORS') === false) {
                $success = true;
                break;
            } else {
                // Supprime la méthode générée avant la prochaine itération
                $output("Suppression de la méthode précédente et nouvelle tentative..." . PHP_EOL);
                $fileContent = file_get_contents($targetFile);
                $fileContent = $this->removeMethodFromClass($fileContent, $data['method']);

                // Nettoyage des lignes vides consécutives
                $fileContent = preg_replace("/\n{3,}/", "\n\n", $fileContent);

                file_put_contents($targetFile, $fileContent);
            }
        }

        $elapsed = microtime(true) - $startTime;
        $elapsedStr = $this->formatDuration($elapsed);

        if ($success) {
            $output("SUCCESS en $iteration itération(s) [Durée : $elapsedStr]" . PHP_EOL);
            $output('Fin du process.' . PHP_EOL);
            return ['success' => true, 'iterations' => $iteration, 'duration' => $elapsedStr];
        } else {
            $output("FAIL après 5 tentatives [Durée : $elapsedStr]" . PHP_EOL);
            $output('Fin du process.' . PHP_EOL);
            return ['success' => false, 'error' => $phpunitOutput, 'duration' => $elapsedStr];
        }
    }

    /**
     * Extrait tous les blocs de code PHP (```php ... ```) d'une chaîne.
     * @return string[]
     */
    private function extractPhpCodeBlocks(string $content): array
    {
        preg_match_all('/```php\s*(.*?)```/is', $content, $matches);
        return array_map('trim', $matches[1]);
    }

    /**
     * Extrait la méthode nommée $methodName d'un bloc de code PHP.
     */
    private function extractNamedMethod(string $code, string $methodName): ?string
    {
        if (preg_match('/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s', $code, $matches)) {
            return $matches[0];
        }
        return null;
    }

    /**
     * Extrait toutes les propriétés et méthodes d'un bloc de code PHP (enlève <?php, use, namespace, etc.).
     */
    private function extractTestMembers(string $testBlock): string
    {
        $members = [];
        // Propriétés (ex: private MyService $myService;)
        if (preg_match_all('/(public|protected|private)\s+\$[a-zA-Z0-9_]+\s*;/', $testBlock, $props)) {
            $members = array_merge($members, $props[0]);
        }
        // Méthodes (avec ou sans type de retour)
        if (preg_match_all('/(public|protected|private)\s+function\s+[a-zA-Z0-9_]+\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s', $testBlock, $methods)) {
            $members = array_merge($members, $methods[0]);
        }
        return trim(implode("\n\n", $members));
    }

    private function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    private function removeMethodFromClass(string $classContent, string $methodName): string
    {
        // Supprime toutes les occurrences de la méthode nommée $methodName dans la classe via parsing
        $pattern = '/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(?::\s*[a-zA-Z0-9_\\\\]+)?\s*\{/';
        while (preg_match($pattern, $classContent, $matches, PREG_OFFSET_CAPTURE)) {
            $start = $matches[0][1];
            $openBraces = 0;
            $end = $start;
            $inString = false;
            for ($i = $start; $i < strlen($classContent); $i++) {
                $char = $classContent[$i];
                if ($char === '"' || $char === "'") {
                    $inString = !$inString;
                }
                if (!$inString) {
                    if ($char === '{') {
                        $openBraces++;
                    } elseif ($char === '}') {
                        $openBraces--;
                        if ($openBraces === 0) {
                            $end = $i + 1;
                            break;
                        }
                    }
                }
            }
            // Supprime le bloc
            $classContent = substr($classContent, 0, $start) . substr($classContent, $end);
        }
        // Nettoie les lignes vides consécutives
        $classContent = preg_replace("/\n{3,}/", "\n\n", $classContent);
        return $classContent;
    }

    /**
     * Supprime les attributs #[Route(...)] juste avant la méthode $methodName.
     */
    private function removeRouteAttributesBeforeMethod(string $classContent, string $methodName): string
    {
        // Supprime tous les #[Route(...)] précédant la déclaration de la méthode cible
        $pattern = '/((?:\s*#\[\s*Route\s*\([^\)]*\)\s*\]\s*)+)(\s*(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\()/';
        return preg_replace($pattern, '$2', $classContent);
    }

    /**
     * Formate une durée (en secondes) en texte lisible (h, mn, s).
     */
    private function formatDuration(float $seconds): string
    {
        $seconds = (int)round($seconds);
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = intdiv($seconds, 60);
        $seconds = $seconds % 60;
        if ($minutes < 60) {
            return sprintf('%dmn %ds', $minutes, $seconds);
        }
        $hours = intdiv($minutes, 60);
        $minutes = $minutes % 60;
        return sprintf('%dh %dmn %ds', $hours, $minutes, $seconds);
    }
}
