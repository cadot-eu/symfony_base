<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TestController extends AbstractController
{

    public function DetecteurChiens(string $caracteristique): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $chiens = [
            'petit' => ['Chihuahua', 'Teckel', 'Bouledogue français'],
            'moyen' => ['Beagle', 'Cocker Spaniel', 'Border Collie'],
            'grand' => ['Dogue Allemand', 'Saint-Bernard', 'Lévrier Irlandais'],
            'noir' => ['Labrador noir', 'Caniche noir', 'Schnauzer noir'],
            'blanc' => ['Samoyède', 'Spitz Allemand', 'Bichon Maltais'],
            'poil_long' => ['Shih Tzu', 'Lhassa Apso', 'Yorkshire Terrier'],
            'poil_court' => ['Boxer', 'Doberman', 'Dalmatien']
        ];

        $resultats = $chiens[strtolower($caracteristique)] ?? ['Aucun chien trouvé pour cette caractéristique'];

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'caracteristique' => $caracteristique,
            'races' => $resultats
        ]);
    }

    /**
     * Retourne une évaluation de la beauté de Sarah avec une description correspondante
     * 
     * Cette méthode génère une réponse JSON contenant :
     * - Le niveau de beauté fourni en paramètre (1-10)
     * - Une description correspondante dans la langue demandée (français ou anglais)
     * Les descriptions sont uniques pour chaque niveau de beauté
     *
     * @param int $nombre Niveau de beauté de Sarah (1-10)
     * @param string $langue Langue des descriptions ('fr' ou 'en')
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route('/evaluate-sarah/{nombre}/{langue}', name: 'app_evaluate_sarah')]
    public function sarah(int $nombre, string $langue): \Symfony\Component\HttpFoundation\JsonResponse
    {
        if ($nombre < 1 || $nombre > 10) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Le nombre doit être entre 1 et 10'], 400);
        }

        $descriptionsFr = [
            1 => 'Sarah est... comment dire... très particulière',
            2 => 'Sarah a un style unique disons',
            3 => 'Sarah est passable',
            4 => 'Sarah est plutôt mignonne',
            5 => 'Sarah est jolie',
            6 => 'Sarah est très jolie',
            7 => 'Sarah est belle',
            8 => 'Sarah est très belle',
            9 => 'Sarah est magnifique',
            10 => 'Sarah est absolument sublime'
        ];

        $descriptionsEn = [
            1 => 'Sarah is... how to say... very special',
            2 => 'Sarah has a unique style let\'s say',
            3 => 'Sarah is passable',
            4 => 'Sarah is rather cute',
            5 => 'Sarah is pretty',
            6 => 'Sarah is very pretty',
            7 => 'Sarah is beautiful',
            8 => 'Sarah is very beautiful',
            9 => 'Sarah is gorgeous',
            10 => 'Sarah is absolutely stunning'
        ];

        $descriptions = strtolower($langue) === 'en' ? $descriptionsEn : $descriptionsFr;

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'score' => $nombre,
            'description' => $descriptions[$nombre],
            'language' => strtolower($langue) === 'en' ? 'english' : 'français'
        ]);
    }
}
