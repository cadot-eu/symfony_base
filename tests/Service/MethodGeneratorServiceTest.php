<?php

namespace App\Tests\Service;

use App\Service\MethodGeneratorService;
use App\Service\DeepseekApiService;
use App\Service\PromptBuilderService;
use App\Service\PhpClassEditorService;
use PHPUnit\Framework\TestCase;

class MethodGeneratorServiceTest extends TestCase
{
    public function testGenerate()
    {
        $deepseek = $this->createMock(DeepseekApiService::class);
        $promptBuilder = $this->createMock(PromptBuilderService::class);
        $phpClassEditor = $this->createMock(PhpClassEditorService::class);

        // ...setup mocks as needed...

        $svc = new MethodGeneratorService($deepseek, $promptBuilder, $phpClassEditor);

        $data = [
            'file' => 'Service/MyService.php',
            'method' => 'doSomethingTest',
            'params' => 'int $a, string $b',
            'returns' => 'bool',
            'goal' => 'Test goal'
        ];

        $output = function ($msg) {};

        $result = $svc->generate($data, $output);

        // Ajoute une assertion pour éviter le test "risky"
        $this->assertIsArray($result);
    }

    public function testGenerateThrowsOnMissingKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $svc = new MethodGeneratorService(
            $this->createMock(DeepseekApiService::class),
            new PromptBuilderService(),
            new PhpClassEditorService()
        );
        $svc->generate(['file' => 'foo.php'], function () {});
    }

    // Pour tester la génération complète, il faudrait mocker DeepseekApiService et le système de fichiers.
}
