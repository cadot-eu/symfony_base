<?php

namespace App\Tests\Service;

use App\Service\PromptBuilderService;
use PHPUnit\Framework\TestCase;

class PromptBuilderServiceTest extends TestCase
{
    public function testBuildPromptContainsGoalAndMethod()
    {
        $service = new PromptBuilderService();
        $data = [
            'file' => 'Controller/FooController.php',
            'method' => 'bar',
            'params' => 'int $id',
            'goal' => 'fait quelque chose',
            'add_route' => true,
            'add_docblock' => true,
        ];
        $prompt = $service->buildPrompt($data, 'EXTRA');
        $this->assertStringContainsString('fait quelque chose', $prompt);
        $this->assertStringContainsString('public function bar(int $id)', $prompt);
        $this->assertStringContainsString('Ajoute un docblock', $prompt);
        $this->assertStringContainsString('Ajoute l\'attribut #[Route', $prompt);
        $this->assertStringContainsString('EXTRA', $prompt);
    }
}
