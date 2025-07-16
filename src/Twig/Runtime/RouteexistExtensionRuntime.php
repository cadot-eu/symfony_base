<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\Routing\RouterInterface;

class RouteexistExtensionRuntime implements RuntimeExtensionInterface
{
    private $router;
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function routeExist(string $routeName): bool
    {
        return $this->router->getRouteCollection()->get($routeName) !== null;
    }
}
