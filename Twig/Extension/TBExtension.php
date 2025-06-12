<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\TBExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TBExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('dd', [TBExtensionRuntime::class, 'dd']),
            new TwigFunction('is_numeric', [TBExtensionRuntime::class, 'is_numeric']),
        ];
    }
}
