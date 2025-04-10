<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class DashboardADminController extends AbstractController
{
    private $em;
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    #[Route('/getEnvEditorjs', name: 'get_env', methods: ['GET'])]
    public function getEnvEditorjs(): JsonResponse
    {
        return new JsonResponse(\explode(',', $_ENV['EDITORJS_PLUGINS_INTERDITS'] ?? ''));
    }
    #[Route('/getEnvMode', name: 'get_env_mode', methods: ['GET'])]
    public function getEnvMode(): JsonResponse
    {
        return new JsonResponse(\explode(',', $_ENV['APP_ENV']));
    }
}
