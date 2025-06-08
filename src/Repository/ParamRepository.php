<?php

namespace App\Repository;

use App\Entity\Param;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Param>
 */
class ParamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Param::class);
    }

    //    /**
    //     * @return Param[] Returns an array of Param objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Param
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getText(string $nom): ?string
    {
        $result = $this->createQueryBuilder('p')
            ->where('p.nom = :nom')
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
        if (!$result) {
            return null;
        }
        return strip_tags(json_decode($result->getValue(), true)['blocks'][0]['data']['text']);
    }

    public function getAll(): array
    {
        $tab = [];
        foreach ($this->findAll() as $parametre) {
            $tab[$parametre->getNom()] = $parametre->getValue();
        }
        return $tab;
    }
}
