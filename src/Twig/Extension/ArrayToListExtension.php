<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use App\Twig\Runtime\ArrayToListExtensionRuntime;

class ArrayToListExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ToList', [ArrayToListExtensionRuntime::class, 'ArrayToList'], ['is_safe' => ['html']]),
        ];
    }
}
