<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\EncodeRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EncodeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('encode', [EncodeRuntime::class, 'encode']),
        ];
    }
    public function getFunctions(): array
    {
        return [
            new TwigFunction('encode', [EncodeRuntime::class, 'encode']),
        ];
    }
}
