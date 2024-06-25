<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class LieuFixtures extends Fixture

{
public function __construct(VilleRepository $villeRepository, LieuRepository $lieuRepository)
{
    $this->ville = $villeRepository;
    $this->lieu = $lieuRepository;
}
    public function load(ObjectManager $manager)
    {

        $faker = Factory::create('fr_FR');
        $typeDeLieu = ['Théatre', 'Bowling', 'Cinéma', 'Stade de Football', 'Musée'];
        $villes = $this->ville->findAll();


        for ($i = 0; $i < 15; $i++) {
            $lieu = new Lieu();
            $lieu->setNom($faker->randomElement($typeDeLieu))
                ->setRue($faker->streetAddress())
                ->setLatitude($faker->latitude())
                ->setLongitude($faker->longitude())
                ->setVille($faker->randomElement($villes));

            $manager->persist($lieu);
        }
        $manager->flush();
    }
}