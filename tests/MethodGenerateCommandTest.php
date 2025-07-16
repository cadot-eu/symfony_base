<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class MethodGenerateCommandTest extends KernelTestCase
{
    public function testCommandRunsSuccessfully()
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:method-generate');
        $commandTester = new CommandTester($command);

        $methodName = 'doSomething' . uniqid();

        // Réinitialise le fichier cible à chaque test
        $targetFile = self::$kernel->getProjectDir() . '/src/Service/MyService.php';
        if (!file_exists(dirname($targetFile))) {
            mkdir(dirname($targetFile), 0777, true);
        }
        // Toujours créer le fichier cible avant le test
        if (!file_exists($targetFile)) {
            file_put_contents($targetFile, "<?php\n\nnamespace App\Service;\n\nclass MyService\n{\n}\n");
        }

        // Payload réaliste pour la génération de méthode
        $payload = base64_encode(json_encode([
            'file' => 'Service/MyService.php',
            'method' => $methodName,
            'params' => 'int $a, string $b',
            'returns' => 'bool',
            'goal' => 'Effectue une opération sur $a et $b et retourne true si succès.',
        ]));

        $commandTester->execute([
            'payload' => $payload,
        ]);

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Début du process...', $output);
        $this->assertStringContainsString('Fin du process.', $output);
        $this->assertSame(0, $commandTester->getStatusCode());

        // Nettoyage : supprime la méthode générée du fichier cible
        if (file_exists($targetFile)) {
            $fileContent = file_get_contents($targetFile);
            $pattern = '/(public|protected|private)\s+function\s+' . preg_quote($methodName, '/') . '\s*\([^\)]*\)\s*(:\s*[a-zA-Z0-9_\\\\]+)?\s*\{(?:[^{}]++|(?R))*\}/s';
            $fileContent = preg_replace($pattern, '', $fileContent, 1);
            file_put_contents($targetFile, $fileContent);
        }

        // Vérifie que le fichier de test a bien été créé
        $testClass = 'MyServiceTest';
        $testFile = self::$kernel->getProjectDir() . '/tests/Service/MyServiceTest.php';
        $this->assertFileExists($testFile, "Le fichier de test $testFile devrait exister.");

        // Affiche le contenu du fichier de test pour debug si besoin
        $testContent = file_get_contents($testFile);
        fwrite(STDOUT, "\nContenu du fichier de test $testFile :\n" . $testContent . "\n");

        // Nettoyage : supprime le fichier de test généré s'il existe
        if (file_exists($testFile)) {
            unlink($testFile);
        }

        // Nettoyage : supprime le fichier cible généré s'il existe
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
    }
}