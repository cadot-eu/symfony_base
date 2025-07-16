<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;
use Doctrine\ORM\EntityManagerInterface;

class RepositoryExtensionRuntime implements RuntimeExtensionInterface
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function Repository($name, $command = null, $params = null)
    {
        //si le début de la chaine n'est pas 'App\Entity', on l'ajoute
        if (strpos($name, 'App\Entity') !== 0) {
            $name = 'App\Entity\\' . $name;
        }
        $repo = $this->em->getRepository($name);
        if ($command) {
            return $repo->$command($params);
        } else {
            return $repo->findAll();
        }
    }
}
