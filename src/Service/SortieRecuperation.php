<?php

namespace App\Service;

use App\Controller\SortieController;
use App\Entity\Campus;
use App\Entity\Etat;
use App\Form\models\SearchEvent;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SortieRecuperation
{
    private $etatHistorise;

    public function __construct(
        private SortieRepository $sortieRepository,
        private EtatRepository $etatRepository,
        private EntityManagerInterface $entityManager,
    ) {
        $this->etatHistorise = $this->etatRepository->findOneBy(['libelle' => 'Historisée']);
    }

    public function getOneSortie(
        int $id,
    ) {
        //On essaie de récupérer une sortie
        $sortieRepository = $this->sortieRepository;

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'cloturée'
        $this->changementEtatCloturee();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Activité en cours'
        $this->changementEtatEncours();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Cloturée'
        $this->changementEtatTerminee();

        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        //On essaie de récupérer la sortie en question
        $sortieRecherchee = $sortieRepository->find($id);

        //On vérifie si son statut est historisée
        if ($sortieRecherchee && $sortieRecherchee->getEtat() === $this->etatHistorise) {
            //Si sa sortie est historisée, on renvoie null pour générer une erreur générique
            // et ainsi ne pas donner d'information sur le fait que les sorties sont historisées
            return null;
        } else {
            //On retourne la sortie
            return $sortieRecherchee;
        }

    }

    public function getAllSortiesSansFiltres(

    )
    {

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'cloturée'
        $this->changementEtatCloturee();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Activité en cours'
        $this->changementEtatEncours();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Cloturée'
        $this->changementEtatTerminee();

        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        $sortieRepository = $this->sortieRepository;
        $sorties = $sortieRepository->findAll();

        return $sorties;
    }

    public function getAllSortiesAvecFiltres(
        SearchEvent $searchEvent,
        UserInterface $user,
    ) {

        $sortieRepository = $this->sortieRepository;

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'cloturée'
        $this->changementEtatCloturee();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Activité en cours'
        $this->changementEtatEncours();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Cloturée'
        $this->changementEtatTerminee();

        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        $qb = $sortieRepository->createQueryBuilder('s');
        $query = $qb->select('s')
            ->andWhere('s.etat != :etat')
            ->setParameter('etat', $this->etatHistorise);

        // Filtre par campus
        $campus = $searchEvent->getCampus();
        if ($campus) {
            $query->andWhere('s.campus = :campus');
            $query->setParameter('campus', $campus);
        }

        // Filtre par le champs de texte
        $search = $searchEvent->getSearch();
        if ($search) {
            $query->andWhere('s.nom LIKE :search');
            $query->setParameter('search', '%' . $search . '%');
        }

        if ( ($searchEvent->getStartDate() != null) && ($searchEvent->getEndDate() != null)) {

            $query->andWhere('s.dateHeureDebut BETWEEN :min AND :max');
            $query->setParameter('min', $searchEvent->getStartDate());
            $query->setParameter('max', $searchEvent->getEndDate());
        }

        if ($searchEvent->getStartDate() !== null) {
            $query->andWhere('s.dateHeureDebut > :min');
            $query->setParameter('min', $searchEvent->getStartDate());
        }

        if ($searchEvent->getEndDate() !== null) {
            $query->andWhere('s.dateHeureDebut < :max');
            $query->setParameter('max', $searchEvent->getEndDate());
        }

        //Filtrage pour les sorties dont je suis organisateur
        $organisateur = $searchEvent->getSortieOrganisateur();
        if ($organisateur) {
            $organisateur = $user;
            $query->andWhere('s.organisateur = :participant');
            $query->setParameter('participant', $organisateur);
        }

        //Filtrage pour les sorties dont je suis inscrit
        $inscrit = $searchEvent->getSortiesInscrits();
        if ($inscrit) {
            $query->andWhere(':participant MEMBER OF s.participants');
            $query->setParameter('participant', $user);
        }

        //Filtrage pour les sorties dont je ne suis pas inscrit
        $nonInscrit = $searchEvent->getSortiesNonInscrits();
        if ($nonInscrit) {
            $query->andWhere(':participant NOT MEMBER OF s.participants');
            $query->setParameter('participant', $user);
        }

        //Filtrage pour les sorties qui sont passées
        $sortiesPassee = $searchEvent->getSortiesPassees();
        if ($sortiesPassee) {
            $etat = $this->etatRepository->findOneBy(['libelle' => 'Terminée']);
            $query->andWhere('s.etat = :etat');
            $query->setParameter('etat', $etat);
        }

        $sorties = $query->getQuery()->getResult();

        return $sorties;
    }

    public function changementEtatHistorise()
    {
        // Cette fonction scanne la bdd afin de voir si des sorties doivent être historisées

        $sorties = $this->sortieRepository->findAll();
        // Date limite pour considérer une sortie comme historisée (1 mois)
        $dateLimiteHistorisation = new \DateTime();
        $dateLimiteHistorisation->modify('-1 month');

        // Parcourir toutes les sorties pour vérifier la date et mettre à jour l'état si nécessaire
        foreach ($sorties as $sortie) {

            if ($sortie->getDateHeureDebut() < $dateLimiteHistorisation && $sortie->getEtat()->getLibelle() !== 'Historisée') {
                $sortie->setEtat($this->etatHistorise);
                $this->entityManager->persist($sortie);
            }

        }
        $this->entityManager->flush();

        return null;
    }

    public function changementEtatEncours()
    {
        // Cette fonction scanne la bdd afin de changer l'état d'une sortie à activité en cours quand l'activité est en cours

        $sorties = $this->sortieRepository->findAll();

        $etatEnCours = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Activité en cours']);

        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));

        // Parcourir toutes les sorties pour vérifier la date et mettre à jour l'état si nécessaire
        foreach ($sorties as $sortie) {

            $dateHeureDebut = $sortie->getDateHeureDebut();
            $dureeEnMinute = $sortie->getDuree();
            $dateBuffer = clone $sortie->getDateHeureDebut();
            $dateHeureFin = date_modify($dateBuffer, '+' . $dureeEnMinute . ' minutes');


            if (($dateHeureDebut < $now) && ($now < $dateHeureFin)) {
                $sortie->setEtat($etatEnCours);
                $this->entityManager->persist($sortie);
            }
        }

        $this->entityManager->flush();

        return null;
    }

    public function changementEtatCloturee()
    {
        // Cette fonction scanne la bdd afin de changer l'état d'une sortie à cloturee
        // cad que le nombre max de participant est atteint
        // ou/et que la date date du jour est supérieure à la date de fin d'inscription

        $sorties = $this->sortieRepository->findAll();

        $etatEnCours = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Activité en cours']);
        $etatCloturee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Cloturée']);
        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));

        // Parcourir toutes les sorties pour vérifier la date et mettre à jour l'état si nécessaire
        foreach ($sorties as $sortie) {

            $dateLimiteInscription = $sortie->getDateLimiteInscription();

            if($sortie->getEtat() == $etatEnCours) {
                break;
            }

            if ($now > $dateLimiteInscription) {
                $sortie->setEtat($etatCloturee);
                $this->entityManager->persist($sortie);
            }

            if ($sortie->getParticipants()->count() >= $sortie->getNbInscriptionsMax()) {
                $sortie->setEtat($etatCloturee);
                $this->entityManager->persist($sortie);
            }
        }

        $this->entityManager->flush();

        return null;
    }

    public function changementEtatTerminee()
    {
        // Cette fonction scanne la bdd afin de changer l'état d'une sortie à terminee
        // cad lorsque l'activitée et passée et non annulée

        $sorties = $this->sortieRepository->findAll();

//        dump($sorties);

        $etatAnnulee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']);
        $etatHistorisee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Historisée']);
        $etatTerminee = $this->entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Terminée']);

        $now = new \DateTime('now', new DateTimeZone('Europe/Paris'));

        // Parcourir toutes les sorties pour vérifier la date et mettre à jour l'état si nécessaire
        foreach ($sorties as $index => $sortie) {

            $dureeEnMinute = $sortie->getDuree();
            $dateBuffer = $sortie->getDateHeureDebut();
            $dateHeureFin = date_modify($dateBuffer, '+' . $dureeEnMinute . ' minutes');

            if ($now > $dateHeureFin && $sortie->getEtat() != $etatAnnulee && $sortie->getEtat() != $etatHistorisee) {
                $sortie->setEtat($etatTerminee);
                $this->entityManager->persist($sortie);
            }
        }

        $this->entityManager->flush();

        return null;
    }
}