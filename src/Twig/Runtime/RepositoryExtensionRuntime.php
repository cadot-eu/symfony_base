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
    /**
     * @example
     *  {{ Repository('Client', 'findAll') }}
     *  {{ Repository('Client', 'findBy', { 'nom': 'SARL' }) }}
     *  {{ Repository('Client', 'findOneBy', { 'nom': 'SARL' }) }}
     *  {{ Repository('Client') }}
     */
    public function Repository($name, $command = null, $params = null)
    {
        //si name n'est pas App\Entity\Nom de la class on l'ajoute
        if (strpos($name, 'App\Entity\\') !== 0) {
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
