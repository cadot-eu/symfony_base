<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use App\Service\GetRenderService;

class RenderExtensionRuntime implements RuntimeExtensionInterface
{
    private $renderService;

    public function __construct(GetRenderService $renderService)
    {
        $this->renderService = $renderService;
    }


    public function renderEditorJs($json, $inline = false)
    {
        return $this->renderService->render($json, $inline);
    }
}
