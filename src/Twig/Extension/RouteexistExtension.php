<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\RouteexistExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RouteexistExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('routeExist', [RouteexistExtensionRuntime::class, 'routeExist']),
        ];
    }
}
