<?php

namespace App\Service;

use App\Entity\Etat;
use App\Form\models\SearchEvent;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
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

    public function  changementEtatHistorise(){
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

    public function getOneSortie(
        int $id,
    ){
        //On essaie de récupérer une sortie
        $sortieRepository = $this->sortieRepository;

        //On vérifie d'abord si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        //On essaie de récupérer la sortie en question
        $sortieRecherchee = $sortieRepository->find($id);

        //On vérifie si son statut est historisée
        if (!$sortieRecherchee && $sortieRecherchee->getEtat() == $this->etatHistorise){
            //Si sa sortie est historisée, on renvoie null pour générer une erreur générique
            // et ainsi ne pas donner d'information sur le fait que les sorties sont historisées
            return null;
        } else {
            //On retourne la sortie
            return $sortieRecherchee;
        }

    }

    public function getAllSortiesSansFiltres(

    ){
        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        $sortieRepository = $this->sortieRepository;
        $sorties = $sortieRepository->findAll();

        return $sorties;
    }

    public function getAllSortiesAvecFiltres(
        SearchEvent $searchEvent,
        UserInterface $user,
    ){
        $sortieRepository = $this->sortieRepository;

        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        $qb = $sortieRepository->createQueryBuilder('s');
        $query = $qb->select('s')
            ->andWhere('s.etat != :etat')
            ->setParameter('etat', $this->etatHistorise);

        // Filtre par campus
        $campus = $searchEvent->getCampus();
        if ($campus){
            $query->andWhere('s.campus = :campus');
            $query->setParameter('campus', $campus);
        }

        // Filtre par le champs de texte
        $search = $searchEvent->getSearch();
        if ($search){
            $query->andWhere('s.nom LIKE :search');
            $query->setParameter('search', '%'.$search.'%');
        }

        // Filtre par les dates
        $dateDebut = $searchEvent->getStartDate();
        if ($dateDebut === null) {
            $dateDebut = new \DateTime();
            $searchEvent->setStartDate($dateDebut);
        }

        $dateFin = $searchEvent->getEndDate();
        if ($dateFin === null) {
            $dateReference = new \DateTime();
            $dateFin = $dateReference->modify('+ 10 years');
            $searchEvent->setEndDate($dateFin);
        }

        if ($dateFin && $dateDebut){
            $query->andWhere('s.dateHeureDebut BETWEEN :min AND :max');
            $query->setParameter('min', $dateDebut);
            $query->setParameter('max', $dateFin);
        }

        if ($dateFin < $dateDebut){
            $this->addFlash('error', 'La date de fin ne peut pas être inférieure à la date de début');
        }

        //Filtrage pour les sorties dont je suis organisateur
        $organisateur = $searchEvent->getSortieOrganisateur();
        if ($organisateur){
            $organisateur = $user;
            $query->andWhere('s.organisateur = :participant');
            $query->setParameter('participant', $organisateur);
        }

        //Filtrage pour les sorties dont je suis inscrit
        $inscrit = $searchEvent->getSortiesInscrits();
        if ($inscrit){
            $query->andWhere(':participant MEMBER OF s.participants');
            $query->setParameter('participant', $user);
        }

        //Filtrage pour les sorties dont je ne suis pas inscrit
        $nonInscrit = $searchEvent->getSortiesNonInscrits();
        if ($nonInscrit){
            $query->andWhere(':participant NOT MEMBER OF s.participants');
            $query->setParameter('participant', $user);
        }

        //Filtrage pour les sorties qui sont passées
        $sortiesPassee = $searchEvent->getSortiesPassees();
        if ($sortiesPassee){
            $etat = $this->etatRepository->findOneBy(['libelle' => 'Terminée']);
            $query->andWhere('s.etat = :etat');
            $query->setParameter('etat', $etat);
        }

        return $query->getQuery()->getResult();
    }
}