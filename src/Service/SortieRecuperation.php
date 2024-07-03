<?php

namespace App\Service;

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

    public function getOneSortie(
        int $id,
    ) {
        //On essaie de récupérer une sortie
        $sortieRepository = $this->sortieRepository;

        //On vérifie d'abord si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Activité en cours'
        $this->changementEtatEncours();

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
        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Activité en cours'
        $this->changementEtatEncours();

        $sortieRepository = $this->sortieRepository;
        $sorties = $sortieRepository->findAll();

        return $sorties;
    }

    public function getAllSortiesAvecFiltres(
        SearchEvent $searchEvent,
        UserInterface $user,
    ) {
        $sortieRepository = $this->sortieRepository;

        // Vérification si des sorties doivent avoir leurs statuts changées à 'Historisée'
        $this->changementEtatHistorise();

        //On vérifie d'abord si des sorties doivent avoir leurs status changées à 'Activité en cours'
        $this->changementEtatEncours();

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

        if ($dateFin && $dateDebut) {
            $query->andWhere('s.dateHeureDebut BETWEEN :min AND :max');
            $query->setParameter('min', $dateDebut);
            $query->setParameter('max', $dateFin);
        }

        if ($dateFin < $dateDebut) {
            $this->addFlash('error', 'La date de fin ne peut pas être inférieure à la date de début');
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

        return $query->getQuery()->getResult();
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
        foreach ($sorties as $index => $sortie) {

            $dateHeureDebut = $sortie->getDateHeureDebut();
//            if ($index == 0) {
//                echo("1) dateheuredebut" . $dateHeureDebut->format('Y-m-d H:i:s') . " \n" . "<br>");
//            }
            $dureeEnMinute = $sortie->getDuree();
            $dateBuffer = clone $sortie->getDateHeureDebut();
            $dateHeureFin = date_modify($dateBuffer, '+' . $dureeEnMinute . ' minutes');

            if ($index == 47) {
                echo("date de début: " . $dateHeureDebut->format('Y-m-d H:i:s') . "\n");
                echo("now: " . $now->format('Y-m-d H:i:s') . "\n");
                echo("date de début < now " . ($now > $dateHeureDebut? "true" : "false"));
            }


//            if ($index == 0) {
//                echo("2) dateheurefin" . $dateHeureFin->format('Y-m-d H:i:s') . " \n" . "<br>");
//                echo("3) dateheuredebut" . $dateHeureDebut->format('Y-m-d H:i:s') . " \n" . "<br>");
//            }
//            $dateHeureFin = (clone $dateHeureDebut)->modify('+' . $dureeEnMinute . ' minutes');

//            $resultDateFin = $dateHeureFin->format('Y-m-d H:i:s');
//            echo($resultDateFin);

//            $isNowSuperiorToDateDebut = ($dateHeureDebut < $now);

//            if ($index == 0) {
//                echo("4) isNowSuperiorToDateDebut " . $isNowSuperiorToDateDebut . " \n" . "<br>");
//            }
//
//            $isNowInferiorToDateFin = ($now < $dateHeureFin);
//
//            if ($index == 0) {
//                echo("5) isNowInferiorToDateFin " . $isNowInferiorToDateFin . " \n" . "<br>");
//            }

//            $resultat = 0;
//            if ($dateHeureDebut < $now) {
//                $resultat = 1;
//            }
//
//            $resultatDeux = 0;
//            if ($now < $dateHeureFin) {
//                $resultatDeux = 1;
//            }

//            if ($index == 47) {
////                echo "isNowSuperiorToDateDebut=[".$isNowSuperiorToDateDebut."]";
//
//                echo("now ". $now->format('Y-m-d H:i:s'). "\n");
//                echo("date debut ". $dateHeureDebut->format('Y-m-d H:i:s'). "\n");
//                echo("date fin ". $dateHeureFin->format('Y-m-d H:i:s'). "\n");
//
//                echo "Test résultat (dateHeureDebut < now)=[".$resultat.")". "\n";
//                echo "Test résultat (now < dateHeureFin))=[".$resultatDeux."]". "\n";
//                echo("6) double condition " . ($isNowSuperiorToDateDebut && $isNowInferiorToDateFin) . "\n" . "<br>");
//            }

//            echo($isNowSuperiorToDateDebut && $isNowInferiorToDateFin);

            if (($now > $dateHeureDebut ) && ($now < $dateHeureFin)) {
//                if ($index == 45) {
//                    echo('7) test' . "\n" . "<br>");
//                }

                $sortie->setEtat($etatEnCours);
                $this->entityManager->persist($sortie);
            }
        }
        $this->entityManager->flush();

        return null;
    }
}