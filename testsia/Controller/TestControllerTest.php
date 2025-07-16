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
    $controller = new \App\Controller\TestController();
    $result = $controller->toto();
    
    $this->assertArrayHasKey('word', $result);
    $this->assertIsString($result['word']);
    $this->assertNotEmpty($result['word']);
    
    // Vérifie que le mot retourné fait partie de la liste attendue
    $validWords = [
        'Papillon', 'Chouquette', 'Chou-fleur', 'Trompe-l’œil', 'Coquelicot',
        'Pamplemousse', 'Tintamarre', 'Bijou', 'Chouchou', 'Grenouille',
        'Farfelu', 'Hurluberlu', 'Chicouf', 'Escargot', 'Gribouillis',
        'Bric-à-brac', 'Boulevard', 'Panache', 'Babillage', 'Dégueulasse',
        'Bouquiniste', 'Champignon', 'Flâner', 'Gigoter', 'Glouglou',
        'Grommeler', 'Lutin', 'Gargouillis', 'Moufle', 'Papillote',
        'Ratatouille', 'Sabotage', 'Saperlipopette'
    ];
    $this->assertContains($result['word'], $validWords);
}
}
