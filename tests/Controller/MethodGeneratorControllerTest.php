<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\MethodGeneratorController;
use Symfony\Component\HttpKernel\KernelInterface;

class MethodGeneratorControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/method-generator');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form#method-form');
    }

    public function testGenerate()
    {
        $client = static::createClient();
        $payload = [
            'file' => 'Controller/TestController.php',
            'method' => 'dummyMethod',
            'params' => '',
            'returns' => 'void',
            'goal' => 'Méthode factice pour test.',
        ];
        $client->request(
            'POST',
            '/method-generator/generate',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('jobId', $data);
        $this->assertNotEmpty($data['jobId']);
    }

    public function testLog()
    {
        $client = static::createClient();
        $client->request('GET', '/method-generator/log/fake-job-id');
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('logs', $data);
    }

    public function testPrivateUtilityMethods()
    {
        $controller = new \App\Controller\MethodGeneratorController();

        self::bootKernel();
        $container = static::getContainer();
        $controller->setContainer($container);

        $reflection = new \ReflectionClass($controller);

        // Test listPhpFiles (doit retourner un tableau de fichiers PHP)
        $listPhpFiles = $reflection->getMethod('listPhpFiles');
        $listPhpFiles->setAccessible(true);
        $files = $listPhpFiles->invoke($controller);
        $this->assertIsArray($files);

        // Test extractFirstMethod
        $extractFirstMethod = $reflection->getMethod('extractFirstMethod');
        $extractFirstMethod->setAccessible(true);
        $code = "public function foo() { return 1; }\nprivate function bar() { return 2; }";
        $this->assertStringContainsString('public function foo()', $extractFirstMethod->invoke($controller, $code));

        // Test extractNamedMethod
        $extractNamedMethod = $reflection->getMethod('extractNamedMethod');
        $extractNamedMethod->setAccessible(true);
        $this->assertStringContainsString('private function bar()', $extractNamedMethod->invoke($controller, $code, 'bar'));

        // Test insertMethodInClass
        $insertMethodInClass = $reflection->getMethod('insertMethodInClass');
        $insertMethodInClass->setAccessible(true);
        $classContent = "<?php\nclass X {\npublic function foo() {}\n}";
        $methodCode = "public function bar() {}";
        $result = $insertMethodInClass->invoke($controller, $classContent, $methodCode, 'bar');
        $this->assertStringContainsString('public function bar()', $result);
    }

}
