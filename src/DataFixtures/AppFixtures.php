<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }


    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $user->setEmail('a@aa.aa');
        $user->setNom('admin');
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            '*'
        );
        $user->setPassword($hashedPassword);
        $manager->persist($user);
        $manager->flush();


        $manager->flush();
    }
}
