<?php

namespace App\EventSubscriber;

use App\Repository\ParamRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface
{
    private $twig,  $params;


    public function __construct(Environment $twig, ParamRepository $paramRepository)
    {
        $this->twig = $twig;
        $tab = [];
        foreach ($paramRepository->findAll() as $parametre) {
            $tab[$parametre->getNom()] = $parametre->getValue();
        }
        $this->params = $tab;
    }

    public function injectGlobalVariables()
    {
        $this->twig->addGlobal('TBparametres', $this->params);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'injectGlobalVariables',
        ];
    }
}
