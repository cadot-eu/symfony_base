<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\RenderExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class RenderExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('render', [RenderExtensionRuntime::class, 'renderEditorJs'], ['is_safe' => ['html']]),
        ];
    }
}
