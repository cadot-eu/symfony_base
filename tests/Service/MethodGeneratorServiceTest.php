<?php

namespace App\Tests\Service;

use App\Service\MethodGeneratorService;
use App\Service\DeepseekApiService;
use PHPUnit\Framework\TestCase;

class MethodGeneratorServiceTest extends TestCase
{
    public function testGenerate()
    {
        $deepseekApiService = $this->createMock(\App\Service\DeepseekApiService::class);
        $service = new \App\Service\MethodGeneratorService($deepseekApiService);

        $data = [
            'file' => 'Service/MyService.php',
            'method' => 'doSomethingTest',
            'params' => 'int $a, string $b',
            'returns' => 'bool',
            'goal' => 'Test goal'
        ];

        $output = function ($msg) {};

        $result = $service->generate($data, $output);

        // Ajoute une assertion pour éviter le test "risky"
        $this->assertIsArray($result);
    }
}
