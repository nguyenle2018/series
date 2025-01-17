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
        $mails = ['outlook.com', 'gmail.com', 'proton.com', 'yahoo.com'];

        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < 15; $i++) {
            $participant = new Participant();
            $participant->setNom($faker->lastName())
                ->setPrenom($faker->firstName())
//                ->setPseudo($faker->userName())
//                ->setMail($faker->email())
                ->setTelephone($faker->phoneNumber())
                ->setRoles(['ROLE_USER'])
                ->setPassword($this->userPasswordHasher->hashPassword($participant,'1234'))
                ->setActif($faker->boolean(80))
                ->setCampus($faker->randomElement($campuses));

            $participant->setPseudo(strtolower($participant->getPrenom()) . ' ' . strtoupper(substr($participant->getNom(), 0, 1)));
           // $participant->setMail(strtolower($participant->getPrenom()) . '@' . $faker->randomElement($mails));
            //$participant->setMail(strtolower($participant->getPrenom() . '.' . $participant->getNom()) . '@' . $faker->randomElement($mails));
            $participant->setMail(strtolower($participant->getPrenom()) . rand(1000, 9999) . '@' . $faker->randomElement($mails));

            $manager->persist($participant);
        }
        $manager->flush();

    }
}