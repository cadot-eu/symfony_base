<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController {

public function toto(): array
{
    $words = [
        'Papillon', 'Chouquette', 'Chou-fleur', 'Trompe-l’œil', 'Coquelicot',
        'Pamplemousse', 'Tintamarre', 'Bijou', 'Chouchou', 'Grenouille',
        'Farfelu', 'Hurluberlu', 'Chicouf', 'Escargot', 'Gribouillis',
        'Bric-à-brac', 'Boulevard', 'Panache', 'Babillage', 'Dégueulasse',
        'Bouquiniste', 'Champignon', 'Flâner', 'Gigoter', 'Glouglou',
        'Grommeler', 'Lutin', 'Gargouillis', 'Moufle', 'Papillote',
        'Ratatouille', 'Sabotage', 'Saperlipopette'
    ];

    return ['word' => $words[array_rand($words)]];
}
}