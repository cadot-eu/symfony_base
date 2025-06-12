<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\EncodeRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class EncodeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('encode', [EncodeRuntime::class, 'encode']),
        ];
    }
}
