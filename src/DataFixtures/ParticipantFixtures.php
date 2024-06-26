<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Repository\LieuRepository;
use App\Repository\ParticipantRepository;
use App\Repository\VilleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture

{


    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher, CampusRepository $campusRepository, ParticipantRepository $participantRepository)
    {
        $this->participant = $participantRepository;
        $this->campus = $campusRepository;
    }
    public function load(ObjectManager $manager)
    {
        $campuses = $this->campus->findAll();


        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 15; $i++) {
            $participant = new Participant();
            $participant->setNom($faker->lastName())
                ->setPrenom($faker->firstName())
                ->setPseudo($faker->userName())
                ->setMail($faker->email())
                ->setTelephone($faker->phoneNumber())
                ->setRoles(['ROLE_USER'])
                ->setPassword($this->userPasswordHasher->hashPassword($participant,'1234'))
                ->setActif($faker->boolean(80))
                ->setCampus($faker->randomElement($campuses));

            $manager->persist($participant);
        }
        $manager->flush();

    }
}