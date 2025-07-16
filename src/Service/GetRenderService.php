<?php

namespace App\Service;

use Twig\Environment;

class GetRenderService
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(?string $json, bool $inline = false): String
    {
        if (empty($json)) {
            return '';
        }
        $content = json_decode($json, true);
        $blocks = $content['blocks'] ?? [];
        foreach ($blocks as $number => $block) {
            $templatePath = "editorjs/blocks/{$block['type']}.html.twig";
            //en mode dev
            if ((getenv('APP_ENV') === 'prod' and  !file_exists('/app/templates/' . $templatePath)) or (in_array($block['type'], explode(',', getenv('EDITORJS_PLUGINS_INTERDITS') ?? '')))) {
                //on le supprime
                unset($blocks[$number]);
                //et on passe
                continue;
            } else
            if (!file_exists('/app/templates/' . $templatePath)) {
                //modification du bloc en bloc inconnu si en prod
                $blocks[$number]['type'] = 'unknown-' . $block['type'];
            }
        }

        $html = $this->twig->render('editorjs_render.html.twig', [
            'blocks' => $blocks
        ]);
        if ($inline) {
            $dom = new \DOMDocument();
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

            // Find the first paragraph element and replace it with a span
            if ($firstP = $dom->getElementsByTagName('p')->item(0)) {
                $span = $dom->createElement('span', $firstP->nodeValue);
                //on ajoute les class
                $span->setAttribute('class', $firstP->getAttribute('class'));
                $firstP->parentNode->replaceChild($span, $firstP);
            }

            // Extract HTML content from body
            if ($body = $dom->getElementsByTagName('body')->item(0)) {
                $html = '';
                foreach ($body->childNodes as $child) {
                    $html .= $dom->saveHTML($child);
                }
            }
        }
        return $html;
    }
}
