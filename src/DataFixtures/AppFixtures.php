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
        $user->setPrenom('administrateur');
        $user->setNote("C'est un super administrateur qui gère bien <b>le groupe</b> <br>Et <a href='Google'>Google</a> est <i>ton ami!</i><br>mais les IA te disent<br><blockquote>C'est nous les meilleures!</blockquote>");
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
