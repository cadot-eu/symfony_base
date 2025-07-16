<?php

namespace App\Testsia\Controller;

use PHPUnit\Framework\TestCase;

/**
 * @group excluded
 */
class TestControllerTest extends TestCase
{

public function testToto()
{
    $service = new \App\Controller\TestController();
    $result = $service->toto();
    
    $this->assertIsInt($result);
    $this->assertGreaterThanOrEqual(1, $result);
    $this->assertLessThanOrEqual(100, $result);
}
}
