<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;

class TestControllerTest extends TestCase
{


public function testDummyMethod()
{
    $service = new \App\Controller\TestController();
    $this->assertTrue($service->dummyMethod());
}
}