<?php

namespace App\Service;

class MethodGeneratorService
{
    private DeepseekApiService $deepseekApiService;
    private PromptBuilderService $promptBuilder;
    private PhpClassEditorService $phpClassEditor;

    public function __construct(
        DeepseekApiService $deepseekApiService,
        PromptBuilderService $promptBuilder,
        PhpClassEditorService $phpClassEditor
    ) {
        $this->deepseekApiService = $deepseekApiService;
        $this->promptBuilder = $promptBuilder;
        $this->phpClassEditor = $phpClassEditor;
    }

    public function generate(array $data, callable $output = null): array
    {
        if ($output) {
            $output("\nLancement du process...");
            $output("Début du process...");
            $output("Envoi à l'IA...");
        }

        foreach (['file', 'method', 'params', 'goal'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new \InvalidArgumentException("Missing required key '$requiredKey' in data array.");
            }
        }

        $targetFile = $this->getProjectDir() . '/src/' . $data['file'];
        $testFileRelative = preg_replace('/\.php$/', 'Test.php', $data['file']);
        $testFile = $this->getProjectDir() . '/testsia/' . $testFileRelative;
        $testClass = pathinfo($data['file'], PATHINFO_FILENAME) . 'Test';

        $maxTries = 5;
        $iteration = 0;
        $success = false;
        $startTime = microtime(true);

        while ($iteration < $maxTries) {
            $iteration++;
            $extra = $iteration > 1 ? "\nVoici le retour de PHPUnit sur ta précédente proposition, corrige la méthode pour que le test passe :\n" : '';
            $prompt = $this->promptBuilder->buildPrompt($data, $extra);

            $apiResponse = $this->deepseekApiService->generateMethod(['prompt' => $prompt]);
            $content = $apiResponse['choices'][0]['message']['content'] ?? '';
            $blocks = $this->phpClassEditor->extractPhpCodeBlocks($content);

            if (empty($blocks) || empty($blocks[0])) {
                if ($output) {
                    $output('Fin du process.');
                }
                return [
                    'success' => false,
                    'error' => "Réponse du modèle IA non exploitable (aucun code PHP détecté)."
                ];
            }

            $methodCodeRaw = $blocks[0];
            $methodCode = $this->phpClassEditor->extractNamedMethod($methodCodeRaw, $data['method']) ?: $methodCodeRaw;

            // --- Génération du fichier source ---
            $fileContent = file_get_contents($targetFile);
            $fileContent = $this->phpClassEditor->removeRouteAttributesBeforeMethod($fileContent, $data['method']);
            $fileContent = $this->phpClassEditor->removeDocblockBeforeMethod($fileContent, $data['method']);
            $fileContent = $this->phpClassEditor->removeMethodFromClass($fileContent, $data['method']);
            if ($output) {
                $output("Méthode supprimée (si existait) dans {$data['file']}.");
            }
            $fileContent = preg_replace('/}\s*$/', "\n\n" . $methodCode . "\n}", $fileContent, 1);
            $fileContent = preg_replace("/\n{3,}/", "\n\n", $fileContent);
            file_put_contents($targetFile, $fileContent);
            if ($output) {
                $output("Méthode créée dans {$data['file']}.");
                if (!empty($data['add_docblock'])) {
                    $output("Ajout du docblock à la méthode.");
                }
            }

            // --- Génération du fichier de test ---
            $namespaceParts = explode('/', dirname($data['file']));
            $namespace = 'App\\Testsia';
            if ($namespaceParts[0] !== '.') {
                foreach ($namespaceParts as $part) {
                    if ($part && $part !== '.') {
                        $namespace .= '\\' . $part;
                    }
                }
            }
            $namespace = rtrim($namespace, '\\');
            $testSkeleton = "<?php\n\nnamespace $namespace;\n\nuse PHPUnit\\Framework\\TestCase;\n\n/**\n * @group excluded\n */\nclass $testClass extends TestCase\n{\n";
            // Ajout du test généré si présent
            if (!empty($blocks[1])) {
                $testMethod = trim($blocks[1]);
                $testMethod = preg_replace('/^<\?php\s*/', '', $testMethod);
                $testMethod = preg_replace('/namespace\s+[^\;]+;/', '', $testMethod);
                $testSkeleton .= "\n" . $testMethod . "\n";
            }
            $testSkeleton .= "}\n";
            if (!file_exists($testFile)) {
                if (!is_dir(dirname($testFile))) {
                    mkdir(dirname($testFile), 0777, true);
                }
                file_put_contents($testFile, $testSkeleton);
                if ($output) {
                    $output("Fichier $testFile créé pour le test.");
                }
            } else {
                file_put_contents($testFile, $testSkeleton);
                if ($output) {
                    $output("Fichier $testFile mis à jour pour le test.");
                }
            }

            // --- Lancer PHPUnit sur le fichier de test généré ---
            $phpunitBin = $this->getProjectDir() . '/vendor/bin/phpunit';
            if (!file_exists($phpunitBin)) {
                $phpunitBin = 'phpunit'; // fallback global
            }
            $phpunitCmd = [
                'php',
                $phpunitBin,
                $testFile
            ];
            $process = new \Symfony\Component\Process\Process($phpunitCmd);
            $process->setWorkingDirectory($this->getProjectDir());
            $process->setTimeout(30);
            $process->run();

            if ($output) {
                // Affichage succinct du retour PHPUnit
                $phpunitOutput = $process->getOutput() . $process->getErrorOutput();
                $lines = preg_split('/\r\n|\r|\n/', $phpunitOutput);
                foreach ($lines as $line) {
                    if (
                        preg_match('/^OK\b|^FAILURES!|^ERRORS!|^WARNINGS!|^Tests:|^Time:|^Memory:|^\d+ \/\s*\d+/', $line)
                        || strpos($line, 'PHPUnit') === 0
                        || strpos($line, 'Testing') === 0
                        || strpos($line, 'Résultat PHPUnit') !== false
                    ) {
                        $output("phpunit: " . $line);
                    }
                }
            }

            $phpunitOutput = $process->getOutput() . $process->getErrorOutput();
            $phpunitLines = preg_split('/\r\n|\r|\n/', trim($phpunitOutput));
            $phpunitSummary = end($phpunitLines);

            if ($output) {
                $output("Résultat PHPUnit : " . $phpunitSummary);
            }

            if ($process->isSuccessful() && strpos($phpunitOutput, 'FAILURES') === false && strpos($phpunitOutput, 'ERRORS') === false) {
                $success = true;
                break;
            }
        }

        $elapsed = microtime(true) - $startTime;
        $elapsedStr = $this->formatDuration($elapsed);

        if ($output) {
            $output('Fin du process.');
        }

        if ($success) {
            return ['success' => true, 'iterations' => $iteration, 'duration' => $elapsedStr];
        } else {
            return ['success' => false, 'duration' => $elapsedStr];
        }
    }

    private function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

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
