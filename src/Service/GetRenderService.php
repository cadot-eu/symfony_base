<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;

class GetRenderService
{
    private $em;
    private $twig;

    public function __construct(EntityManagerInterface $em, Environment $twig)
    {
        $this->em = $em;
        $this->twig = $twig;
    }

    public function render(string $entity, string $id, string $field): Response
    {
        $entityClass = 'App\\Entity\\' . ucfirst($entity);
        $entity = $this->em->getRepository($entityClass)->find($id);
        $getter = 'get' . ucfirst($field);
        $json = $entity->$getter();
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
        return new Response($this->twig->render('editorjs_render.html.twig', [
            'blocks' => $blocks
        ]));
    }
}
