<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\LieuRepository;
use App\Repository\ParticipantRepository;
use App\Repository\VilleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SortieFixtures extends Fixture

{


    public function __construct(CampusRepository $campusRepository, ParticipantRepository $participantRepository, EtatRepository $etatRepository, LieuRepository $lieuRepository)
    {
        $this->participant = $participantRepository;
        $this->campus = $campusRepository;
        $this->etat = $etatRepository;
        $this->lieu = $lieuRepository;
    }
    public function load(ObjectManager $manager)
    {

        $campuses = $this->campus->findAll();
        $etats = $this->etat->findAll();
        $lieus = $this->lieu->findAll();
        $participants = $this->participant->findAll();

        $faker = Factory::create('fr_FR');
        $dateAujourdHui = new \DateTime();
        $dateDebutSortie = new \DateTime();
        $dateDebutSortie->modify('+ 3 weeks');

        for ($i = 0; $i < 15; $i++) {
            $sortie = new Sortie();
            $sortie->setNom("Sortie $i")
                ->setDateHeureDebut($faker->dateTimeBetween($dateAujourdHui, $dateDebutSortie))
                ->setDuree($faker->numberBetween($min = 1, $max = 300));
            $sortie->setDateLimiteInscription($faker->dateTimeBetween(('- 1 week'), $sortie->getDateHeureDebut()))
                ->setNbInscriptionsMax($faker->numberBetween(2, 5))
                ->setCampus($faker->randomElement($campuses))
                ->setOrganisateur($faker->randomElement($participants))
                ->setEtat($faker->randomElement($etats))
                ->setLieu($faker->randomElement($lieus))
                ->setInfosSortie($faker->realText("800"));
            $manager->persist($sortie);
        }
        $manager->flush();

    }
}