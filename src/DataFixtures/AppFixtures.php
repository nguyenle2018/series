<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Repository\VilleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private readonly Generator $faker;


    public function __construct(private readonly UserPasswordHasherInterface $userPasswordHasher, private LieuFixtures $lieuFixtures, private ParticipantFixtures $participantFixtures, private SortieFixtures $sortieFixtures)
    {
        $this->faker = Factory::create('fr_FR');
    }
    public function load(ObjectManager $manager): void
    {
        $number = 20;

        $this->addEtat($manager);
        $this->addCampus($manager);
        $this->addVille($number, $manager);
        $this->lieuFixtures->load($manager);
        // $this->addLieu($number, $manager, $villeRepository);
        $this->participantFixtures->load($manager);
        //$this->addParticipant($number, $manager);
        $this->sortieFixtures->load($manager);
        //$this->addSortie($number, $manager);



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

        $tousLesEtats = ['Créée', 'Ouverte', 'Cloturée', 'Activité en cours', 'Terminée', 'Annulée', 'Historisée'];
        for ($i = 0; $i < sizeof($tousLesEtats); $i++) {
            $etat = new Etat();
            $etat->setLibelle($tousLesEtats[$i]);

            $manager->persist($etat);
        }
        $manager->flush();
    }

    private function addCampus(ObjectManager $manager){

        $tousLesCampus = ['Rennes', 'Nantes', 'Niort', 'Quimper', 'En ligne'];
        for ($i = 0; $i < sizeof($tousLesCampus); $i++) {
            $campus = new Campus();
            $campus->setNom($tousLesCampus[$i]);

            $manager->persist($campus);
        }
        $manager->flush();
    }

    private function addLieu(int $number, ObjectManager $manager, VilleRepository $villeRepository)
    {
        $faker = Factory::create('fr_FR');
        $typeDeLieu = ['Théatre', 'Bowling', 'Cinéma', 'Stade de Football', 'Musée'];
        $villes = $villeRepository->findAll();


        for ($i = 0; $i < $number; $i++) {
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

    private function addParticipant(int $number, ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < $number; $i++) {
            $participant = new Participant();
            $participant->setNom($faker->lastName())
                ->setPrenom($faker->firstName())
                ->setMail($faker->email())
                ->setTelephone($faker->phoneNumber())
                ->setRoles(['ROLE_USER'])
                ->setPassword($this->userPasswordHasher->hashPassword($participant,'1234'))
                ->setActif($faker->boolean(80));

            $manager->persist($participant);
        }
        $manager->flush();
    }

        private function addSortie(int $number, ObjectManager $manager)
        {

            $faker = Factory::create('fr_FR');
            $dateDebut = new \DateTime();
            $dateDebut->modify('+3 weeks');

            for ($i = 0; $i < $number; $i++) {
                $sortie = new Sortie();
                $sortie->setNom("Sortie $i")
                    ->setDateHeureDebut($faker->dateTimeBetween('now', '+3 weeks'))
                    ->setDuree($faker->randomDigitNotNull());
                $date = $sortie->getDateHeureDebut();
                $date->modify('-2 weeks');
             $sortie->setDateLimiteInscription($faker->dateTimeBetween($date, $sortie->getDateHeureDebut()))
                    ->setNbInscriptionsMax($faker->randomDigitNotNull())
                    ->setInfosSortie($faker->realText("800"));
                $manager->persist($sortie);
            }
            $manager->flush();

        }
}
