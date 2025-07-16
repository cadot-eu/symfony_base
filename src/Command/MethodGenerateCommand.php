<?php

namespace App\Command;

use App\Service\DeepseekApiService;
use App\Service\MethodGeneratorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;

#[AsCommand(
    name: 'app:method-generate',
    description: 'Génère une méthode et son test via l’IA, puis lance PHPUnit.'
)]
class MethodGenerateCommand extends Command
{
    private DeepseekApiService $deepseekApiService;
    private MethodGeneratorService $generatorService;

    public function __construct(
        DeepseekApiService $deepseekApiService,
        MethodGeneratorService $generatorService
    ) {
        parent::__construct();
        $this->deepseekApiService = $deepseekApiService;
        $this->generatorService = $generatorService;
    }

    protected function configure(): void
    {
        $this->addArgument('payload', InputArgument::REQUIRED, 'Payload encodé');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $data = json_decode(base64_decode($input->getArgument('payload')), true);
        $result = $this->generatorService->generate($data, function ($msg) use ($output) {
            $output->writeln($msg);
        });
        // ...gérer le code retour selon $result...
        return Command::SUCCESS;
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

    private function extractFirstMethod(string $code): string
    {
        // Retire les balises markdown éventuelles
        $code = preg_replace('/^```php|^```/m', '', $code);

        // Cherche la première méthode PHP
        if (preg_match('/(public|protected|private)\s+function\s+[a-zA-Z0-9_]+\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s', $code, $matches)) {
            return $matches[0];
        }
        // Si rien trouvé, retourne tout (fallback)
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

    private function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }

    private function removeMethodFromClass(string $classContent, string $methodName): string
    {
        // Supprime la méthode nommée $methodName dans la classe
        $pattern = '/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s';
        return preg_replace($pattern, '', $classContent, 1);
    }
}
