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
            $output($this->turboStreamLog("Préparation du prompt et interrogation IA..."));
        }

        foreach (['file', 'method', 'params', 'goal'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $data)) {
                throw new \InvalidArgumentException("Missing required key '$requiredKey' in data array.");
            }
        }

        // Ajout : gestion des fichiers annexes pour le prompt
        $extraFilesPrompt = '';
        if (!empty($data['extra_files']) && is_array($data['extra_files'])) {
            $extraFilesPrompt .= "\n\nPrends en compte les fichiers suivants pour ta proposition :\n";
            foreach ($data['extra_files'] as $file) {
                $extraFilesPrompt .= "Nom du fichier : {$file['name']}\nContenu :\n{$file['content']}\n---\n";
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
            if ($success) {
                return [
                    'success' => true,
                    'iterations' => $iteration,
                    'duration' => $this->formatDuration(microtime(true) - $startTime)
                ];
            }
            $iteration++;
            if ($output) {
                $output($this->turboStreamLog("Itération $iteration / $maxTries"));
            }
            // Ajout : consigne explicite pour corrélation test/méthode en cas d'échec
            $extra = $iteration > 1
                ? "\nVoici le retour de PHPUnit sur ta précédente proposition, corrige la méthode OU le test pour que le test passe. Vérifie bien si le problème vient du test ou de la méthode, et assure-toi que la méthode et le test sont bien en corrélation logique et fonctionnelle."
                : '';

            // Ajoute explicitement la consigne pour la route/docblock si demandé
            if (!empty($data['add_route'])) {
                $extra .= "\nAjoute l'attribut #[Route] Symfony au-dessus de la méthode générée si ce n'est pas déjà fait.";
            }
            if (!empty($data['add_docblock'])) {
                $extra .= "\nAjoute un docblock PHP complet et explicatif à la méthode générée.";
            }

            // Ajoute les fichiers annexes au prompt
            $prompt = $this->promptBuilder->buildPrompt($data, $extra . $extraFilesPrompt);

            if ($output) {
                $output($this->turboStreamLog("Envoi du prompt à l'IA..."));
            }

            $apiResponse = $this->deepseekApiService->generateMethod(['prompt' => $prompt]);
            $content = $apiResponse['choices'][0]['message']['content'] ?? '';
            if ($output) {
                $output($this->turboStreamLog("Réponse IA reçue."));
            }
            $blocks = $this->phpClassEditor->extractPhpCodeBlocks($content);

            if (empty($blocks) || empty($blocks[0])) {
                if ($output) {
                    $output($this->turboStreamLog('Fin du process.'));
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
                $output($this->turboStreamLog("Méthode supprimée (si existait) dans {$data['file']}."));
            }
            $fileContent = preg_replace('/}\s*$/', "\n\n" . $methodCode . "\n}", $fileContent, 1);
            $fileContent = preg_replace("/\n{3,}/", "\n\n", $fileContent);
            file_put_contents($targetFile, $fileContent);
            if ($output) {
                $output($this->turboStreamLog("Méthode créée dans {$data['file']}."));
                if (!empty($data['add_docblock'])) {
                    $output($this->turboStreamLog("Ajout du docblock à la méthode."));
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
                    $output($this->turboStreamLog("Fichier $testFile créé pour le test."));
                }
            } else {
                file_put_contents($testFile, $testSkeleton);
                if ($output) {
                    $output($this->turboStreamLog("Fichier $testFile mis à jour pour le test."));
                }
            }

            // --- Lancer PHPUnit sur le fichier de test généré ---
            if ($output) {
                $output($this->turboStreamLog("Lancement de PHPUnit sur $testFile..."));
            }
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

            $phpunitOutput = $process->getOutput() . $process->getErrorOutput();
            $phpunitSummary = '';
            foreach (preg_split('/\r\n|\r|\n/', $phpunitOutput) as $line) {
                if (preg_match('/^(OK \([^)]+\)|FAILURES!|ERRORS!|WARNINGS!)/', $line)) {
                    $phpunitSummary = $line;
                    break;
                }
            }
            if (!$phpunitSummary) {
                $phpunitLines = preg_split('/\r\n|\r|\n/', trim($phpunitOutput));
                $phpunitSummary = end($phpunitLines);
            }

            if ($output) {
                $output($this->turboStreamLog("Résultat PHPUnit : " . $phpunitSummary));
            }

            if ($process->isSuccessful() && strpos($phpunitSummary, 'FAILURES') === false && strpos($phpunitSummary, 'ERRORS') === false) {
                if ($output) {
                    $output($this->turboStreamLog("Test réussi à l'itération $iteration."));
                    $output($this->turboStreamLog("Fin du process. Nombre d'itérations : $iteration. Durée : " . $this->formatDuration(microtime(true) - $startTime)));
                }
                // Sortie immédiate de la méthode après succès
                return [
                    'success' => true,
                    'iterations' => $iteration,
                    'duration' => $this->formatDuration(microtime(true) - $startTime)
                ];
            } else {
                if ($output) {
                    $output($this->turboStreamLog("Test échoué à l'itération $iteration."));
                }
            }
        }

        $elapsed = microtime(true) - $startTime;
        $elapsedStr = $this->formatDuration($elapsed);

        if ($output) {
            $output($this->turboStreamLog("Fin du process. Nombre d'itérations : $iteration. Durée : $elapsedStr"));
        }

        if ($success) {
            return ['success' => true, 'iterations' => $iteration, 'duration' => $elapsedStr];
        } else {
            return ['success' => false, 'duration' => $elapsedStr];
        }
    }

    private function turboStreamLog(string $msg): string
    {
        return <<<HTML
<turbo-stream action="append" target="logs-frame">
  <template>
    <div style="white-space:pre-wrap;font-family:monospace;">$msg</div>
  </template>
</turbo-stream>
HTML;
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
