<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\InterpolateExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class InterpolateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('interpolate', [InterpolateExtensionRuntime::class, 'interpolate'], ['needs_context' => true]),
        ];
    }
}
