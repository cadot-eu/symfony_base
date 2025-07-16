<?php

namespace App\Testsia\Controller;

use PHPUnit\Framework\TestCase;

/**
 * @group excluded
 */
class TestControllerTest extends TestCase
{

    public function testSarah()
    {
        $controller = new \App\Controller\TestController();

        // Test français
        $response = $controller->sarah(5, 'fr');
        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(5, $content['score']);
        $this->assertEquals('Sarah est jolie', $content['description']);

        // Test anglais
        $response = $controller->sarah(10, 'en');
        $this->assertEquals(200, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);
        $this->assertEquals(10, $content['score']);
        $this->assertEquals('Sarah is absolutely stunning', $content['description']);

        // Test erreur
        $response = $controller->sarah(0, 'fr');
        $this->assertEquals(400, $response->getStatusCode());
    }
}
