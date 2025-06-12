<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\RepositoryExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class RepositoryExtension extends AbstractExtension
{


    public function getFunctions(): array
    {
        return [
            new TwigFunction('repository', [RepositoryExtensionRuntime::class, 'Repository']),
        ];
    }
}
