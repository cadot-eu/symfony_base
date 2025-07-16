<?php

namespace App\Tests\Service;

use App\Service\PhpClassEditorService;
use PHPUnit\Framework\TestCase;

class PhpClassEditorServiceTest extends TestCase
{
    public function testExtractPhpCodeBlocks()
    {
        $service = new PhpClassEditorService();
        $content = "```php\npublic function foo() {}\n```\n```php\npublic function bar() {}\n```";
        $blocks = $service->extractPhpCodeBlocks($content);
        $this->assertCount(2, $blocks);
        $this->assertStringContainsString('foo', $blocks[0]);
        $this->assertStringContainsString('bar', $blocks[1]);
    }

    public function testRemoveMethodFromClass()
    {
        $service = new PhpClassEditorService();
        $class = "<?php\nclass X {\npublic function foo() {}\npublic function bar() {}\n}";
        $result = $service->removeMethodFromClass($class, 'foo');
        $this->assertStringNotContainsString('foo', $result);
        $this->assertStringContainsString('bar', $result);
    }

    public function testRemoveRouteAttributesBeforeMethod()
    {
        $service = new PhpClassEditorService();
        $class = "#[Route('/foo')]\npublic function foo() {}\n";
        $result = $service->removeRouteAttributesBeforeMethod($class, 'foo');
        $this->assertStringNotContainsString('#[Route', $result);
    }

    public function testRemoveDocblockBeforeMethod()
    {
        $service = new PhpClassEditorService();
        $class = "/**\n * doc\n */\npublic function foo() {}\n";
        $result = $service->removeDocblockBeforeMethod($class, 'foo');
        $this->assertStringNotContainsString('/**', $result);
    }
}
