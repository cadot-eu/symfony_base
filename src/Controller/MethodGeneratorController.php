<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Uid\Uuid;

class MethodGeneratorController extends AbstractController
{
    #[Route('/method-generator', name: 'method_generator')]
    public function index(): Response
    {
        $files = $this->listPhpFiles();

        return $this->render('method_generator/index.html.twig', [
            'files' => $files,
        ]);
    }

    #[Route('/method-generator/generate', name: 'method_generator_generate', methods: ['POST'])]
    public function generate(Request $request, KernelInterface $kernel): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Ajout : log du payload pour debug
        file_put_contents(
            $kernel->getProjectDir() . '/var/methodgen_last_payload.log',
            print_r($data, true)
        );

        $jobId = Uuid::v4();
        $logFile = $this->getLogFile($kernel, $jobId);

        $this->logToFile($logFile, "Lancement du process...\n");

        $this->launchBackgroundProcess($kernel, $data, $logFile);

        return new JsonResponse(['jobId' => $jobId]);
    }

    #[Route('/method-generator/log/{jobId}', name: 'method_generator_log', methods: ['GET'])]
    public function log(string $jobId, KernelInterface $kernel): JsonResponse
    {
        $logFile = $this->getLogFile($kernel, $jobId);
        $logs = file_exists($logFile) ? file_get_contents($logFile) : '';
        return new JsonResponse(['logs' => $logs]);
    }

    #[Route('/method-generator/methods', name: 'method_generator_methods', methods: ['GET'])]
    public function methods(Request $request): JsonResponse
    {
        $file = $request->query->get('file');
        if (!$file) {
            return new JsonResponse(['methods' => []]);
        }
        $path = $this->getParameter('kernel.project_dir') . '/src/' . $file;
        if (!file_exists($path)) {
            return new JsonResponse(['methods' => []]);
        }
        $content = file_get_contents($path);
        preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(/', $content, $matches);
        $methods = $matches[1] ?? [];
        // Exclure __construct et méthodes magiques
        $methods = array_filter($methods, fn($m) => strpos($m, '__') !== 0);
        sort($methods);
        return new JsonResponse(['methods' => array_values($methods)]);
    }

    // ----------- Méthodes privées utilitaires -----------

    private function listPhpFiles(): array
    {
        $baseDir = $this->getParameter('kernel.project_dir') . '/src/';
        $files = [];
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($baseDir));
        foreach ($rii as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = substr($file->getPathname(), strlen($baseDir));
                $files[] = $relativePath;
            }
        }
        sort($files);
        return $files;
    }

    private function getLogFile(KernelInterface $kernel, $jobId): string
    {
        return $kernel->getProjectDir() . '/var/methodgen_' . $jobId . '.log';
    }

    private function logToFile(string $logFile, string $message): void
    {
        file_put_contents($logFile, $message, FILE_APPEND);
    }

    private function launchBackgroundProcess(KernelInterface $kernel, array $data, string $logFile): void
    {
        $cmd = sprintf(
            'php %s/bin/console app:method-generate "%s" >> "%s" 2>&1 &',
            $kernel->getProjectDir(),
            base64_encode(json_encode($data)),
            $logFile
        );
        exec($cmd);
    }

    /**
     * Extrait la première méthode PHP d'un bloc de code.
     */
    private function extractFirstMethod(string $code): string
    {
        $code = preg_replace('/^```php|^```/m', '', $code);
        if (preg_match('/(public|protected|private)\s+function\s+[a-zA-Z0-9_]+\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s', $code, $matches)) {
            return $matches[0];
        }
        return trim($code);
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
     * Insère proprement une méthode dans une classe PHP existante.
     */
    private function insertMethodInClass(string $fileContent, string $methodCode, string $methodName): string
    {
        $pattern = '/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s';
        $fileContent = preg_replace($pattern, '', $fileContent, 1);
        return preg_replace('/}\s*$/', "\n\n" . $methodCode . "\n}", $fileContent, 1);
    }

    /**
     * Génère le chemin du fichier de test à partir du chemin du fichier source.
     * Exemple : Controller/TestController.php => tests/Controller/TestControllerTest.php
     */
    private function getTestFilePath(string $sourceFile): string
    {
        // Retirer l'extension .php et ajouter Test.php
        $testFile = preg_replace('/\.php$/', 'Test.php', $sourceFile);
        // Préfixer par tests/
        return 'tests/' . $testFile;
    }
}
