<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;


class DashboardService
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->em = $entityManager;
    }

    /**
     * Renvoie les noms des entités définies dans le projet
     *
     * @return array Les noms des entités
     */
    function getEntitiesName()
    {
        //on récupères les noms des entitées
        $entityClasses = [];
        $metaData = $this->em->getMetadataFactory()->getAllMetadata();
        foreach ($metaData as $meta) {
            $entityClasses[] = $meta->getName();
        }
        return array_map(function ($className) {
            return (new ReflectionClass($className))->getShortName();
        }, $entityClasses);
    }
}
