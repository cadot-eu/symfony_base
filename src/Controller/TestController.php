<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function index(): Response
    {
        return $this->render('test/index.html.twig', [
            'controller_name' => 'TestController',
        ]);
    }

    #[Route('/gros-string', name: 'auto_route')]
    public function gros(): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 234; $i++) {
            $randomString .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $randomString;
    }

    public function dummyMethod()
    {
        return true;
    }

/**
 * Génère une chaîne numérique aléatoire de longueur spécifiée.
 *
 * Cette méthode crée une chaîne de nombres aléatoires (0 ou 1) de la longueur demandée.
 * Chaque caractère de la chaîne est généré indépendamment avec une probabilité égale d'être 0 ou 1.
 *
 * @param int $longueur La longueur de la chaîne numérique à générer (doit être positive)
 * @return int La chaîne numérique aléatoire sous forme d'entier
 */
#[Route('/random-binary-string/{longueur}', name: 'auto_route')]
public function chainelong(int $longueur): int
{
    if ($longueur <= 0) {
        return 0;
    }

    $result = '';
    for ($i = 0; $i < $longueur; $i++) {
        $result .= random_int(0, 1);
    }

    return (int)$result;
}
}