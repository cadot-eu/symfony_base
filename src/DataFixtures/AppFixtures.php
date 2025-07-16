<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher, $em;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em)
    {
        $this->passwordHasher = $passwordHasher;
        $this->em = $em;
    }


    public function load(ObjectManager $manager): void
    {
        //on vérifie que l'utilisateur superadmin n'existe pas
        if (!$this->em->getRepository(User::class)->findOneBy(['email' => 'superadmin@aa.aa'])) {
            $user = new User();
            $user->setRoles(['ROLE_SUPERADMIN']);
            $user->setEmail('superadmin@aa.aa');
            $user->setNom('superadmin');
            $user->setPrenom('super_administrateur');
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                '*'
            );
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            $manager->flush();
        }
        if (!$this->em->getRepository(User::class)->findOneBy(['email' => 'a@aa.aa'])) {
            $user = new User();
            $user->setRoles(['ROLE_ADMIN']);
            $user->setEmail('a@aa.aa');
            $user->setNom('admin');
            $user->setPrenom('administrateur');
            $hashedPassword = $this->passwordHasher->hashPassword(
                $user,
                '*'
            );
            $user->setPassword($hashedPassword);
            $manager->persist($user);
            $manager->flush();
        }
    }
}
