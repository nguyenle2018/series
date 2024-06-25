<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private readonly Generator $faker;

    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }

    private function addVille(int $number, ObjectManager $manager){

        $faker = Factory::create('fr_FR');
        for ($i = 0; $i < $number; $i++) {
            $ville = new Ville();
            $ville->setNom($faker->city())
                  ->setCodePostal($faker->postcode());

            $manager->persist($ville);
        }
        $manager->flush();
    }

    private function addEtat(ObjectManager $manager){

    }

    private function addCampus(ObjectManager $manager){

    }

    private function addLieu(int $number, ObjectManager $manager)
    {

    }

    private function addParticipant(int $number, ObjectManager $manager)
    {

    }

        private function addSortie(int $number, ObjectManager $manager)
    {

    }

}
