<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\DataExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DataExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('data', [DataExtensionRuntime::class, 'Data'], ['is_safe' => ['html']]),
        ];
    }
}
