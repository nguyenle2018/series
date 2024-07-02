<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\models\SearchEvent;
use App\Form\SearchEventType;
use App\Form\SortieType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SortieRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sortie', name: 'sortie_')]
class SortieController extends AbstractController
{
    #[Route('/create', name: 'create')]
    public function create(EntityManagerInterface $entityManager, Request $request): Response
    {
        $sortie = new Sortie();

        $sortieForm = $this->createForm(SortieType::class, $sortie);

        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {

            //associer l'état
            $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
            $sortie->setEtat($etat);

            // Associer le participant à la sortie
            $participant = $this->getUser();
            $sortie->setOrganisateur($participant);

            $entityManager->persist($sortie);
            $entityManager->flush();

            //ajout de la sortie à la liste des sorties du lieu
            $lieuDeLaSortieEnBase = $entityManager->getRepository(Lieu::class)->find($sortie->getLieu());
            $lieuDeLaSortieEnBase->addSorty($sortie);

            if ($sortieForm->get('enregistrer')->isClicked())
            {
                $etatCree = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Créée']);
                $sortie->setEtat($etatCree);
            }

            if ($sortieForm->get('publier')->isClicked())
            {
                $etatOuverte = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
                $sortie->setEtat($etatOuverte);
            }

            //enregistrement de la liste des sorties :
            $entityManager->persist($lieuDeLaSortieEnBase);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie créée avec succès !');

            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);

        }

        return $this->render('sortie/sortie.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'sortie' => $sortie, // Passer la variable sortie à la vue Twig

        ]);
    }

    #[Route('/liste', name: 'liste')]
    public function getAll(
        SortieRepository $sortieRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {

        $qb = $sortieRepository->createQueryBuilder('s');
        $query = $qb->select('s');

        $searchEvent = new SearchEvent();
        $formSearchEvent = $this->createForm(SearchEventType::class, $searchEvent);
        $formSearchEvent->handleRequest($request);

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
            $organisateur = $this->getUser();
            $query->andWhere('s.organisateur = :participant');
            $query->setParameter('participant', $organisateur);
        }

        //Filtrage pour les sorties dont je suis inscrit
        $inscrit = $searchEvent->getSortiesInscrits();
        if ($inscrit){
            $user = $this->getUser();
            $query->andWhere(':participant MEMBER OF s.participants');
            $query->setParameter('participant', $user);
        }

        //Filtrage pour les sorties dont je ne suis pas inscrit
        $nonInscrit = $searchEvent->getSortiesNonInscrits();
        if ($nonInscrit){
            $user = $this->getUser();
            $query->andWhere(':participant NOT MEMBER OF s.participants');
            $query->setParameter('participant', $user);
        }

        //Filtrage pour les sorties qui sont passées
        $sortiesPassee = $searchEvent->getSortiesPassees();
        if ($sortiesPassee){
            $etat = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Terminée']);
            $query->andWhere('s.etat = :etat');
            $query->setParameter('etat', $etat);
        }

        $sorties = $query->getQuery()->getResult();

        // faire une variable pour vérifier si l'utilisateur est l'organisateur et passer le booléen à la vue
        // faire une variable pour vérifier si l'utilisateur est membre de la sortie et passer le booléen à la vue pour afficher le boutton


        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties,
            'filterForm'=> $formSearchEvent
        ]);
    }

    #[Route('/update/{id}', name: 'update')]
    public function update(Sortie $sortie, EntityManagerInterface $entityManager, Request $request): Response
    {
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Sortie mise à jour avec succès !');

            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/updated.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'sortie' => $sortie,
        ]);
    }
    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        int     $id
    ): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);
        $participants = $sortie->getParticipants();

        if (!$sortie) {
            throw $this->createNotFoundException('Sortie non trouvée');
        }
        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
            'participants'=>$participants
        ]);
    }

    #[Route('/publier/{id}', name: 'publier', requirements: ['id' => '\d+'])]
    public function publier(
        EntityManagerInterface $entityManager,
        SortieRepository $sortieRepository,
        int $id
    ): Response
    {
        // Récupérer la sortie par son ID
        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('La sortie n\'existe pas.');
        }

        // Vérifier que la sortie n'est pas déjà publiée
        if ($sortie->getEtat() && $sortie->getEtat()->getLibelle() === 'Ouverte') {
            $this->addFlash('warning', 'La sortie est déjà publiée.');
            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        // Changer l'état de la sortie à "Ouverte"
        $etatOuverte = $entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);
        $sortie->setEtat($etatOuverte);

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été publiée avec succès.');
        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
    }

    #[Route('/annuler/{id}', name: 'annuler', requirements: ['id' => '\d+'])]
    public function annuler(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        int     $id,
    ): Response
    {
        $sorties = $entityManager->getRepository(Sortie::class)->findAll();
        $sortie = $sortieRepository->find($id);
        $now = new \DateTime();

        // Vérifier si l'utilisateur est l'organisateur de la sortie
        if($sortie->getOrganisateur() !== $this->getUser() ) {
            throw $this->createNotFoundException('Vous n\'êtes pas autorisé à annuler cette sortie.');
        }

        // Vérifier si la sortie n'a pas encore commencé
        if($sortie->getDateHeureDebut() > $now ) {
            $sortie->setEtat($entityManager->getRepository(Etat::class)->findOneBy(['libelle' => 'Annulée']));

            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été annulée avec succès.');
            return $this->render('sortie/annuler.html.twig', [
                'sortie' => $sortie
            ]);
            //return $this->redirectToRoute('sortie_annuler', ['id' => $sortie->getId()]);
        }
        $this->addFlash('error', 'La sortie ne peut pas être annulée car elle a déjà commencé.');

        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties
        ]);
    }

    #[Route('/inscription/{id}', name: 'inscription', requirements: ['id' => '\d+'])]
    public function inscription(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        int     $id,
    ): Response
    {
        $sortie = $sortieRepository->find($id);
        $participant = $this->getUser();

        if(!$sortie || !$participant){
            throw $this->createNotFoundException('Sortie ou Participant non trouvée !!');
        }
        $now = new \DateTime();

        //vérifier des conditions
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte' ||
            $sortie->getDateLimiteInscription() <= $now ||
            count($sortie->getParticipants()) >= $sortie->getNbInscriptionsMax()
        ) {
            // Gérer ici le cas où les conditions d'inscription ne sont pas remplies
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire à cette sortie car elle a été annulé ou à déjà commencer.');

            return $this->redirectToRoute('sortie_liste');
        }

        // Ajouter le participant à la sortie
        $sortie->addParticipant($participant);
        $entityManager->flush();


        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);

    }

    #[Route('/desistement/{id}', name: 'desistement', requirements: ['id' => '\d+'])]
    public function desistement(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        int $id,
    ): Response {
        // Récupérer la sortie et le participant
        $sortie = $sortieRepository->find($id);

        //On prend l'utilisateur de la sortie
        $participant = $this->getUser();

        // Vérifier si la sortie existe
        if (!$sortie || !$participant) {
            throw $this->createNotFoundException('Sortie ou participant non trouvée.');
        }

        if (new \DateTime() >= $sortie->getDateHeureDebut()) {
            $this->addFlash('error', 'Vous ne pouvez plus vous désister car l\'événement a déjà commencé.');
            return $this->redirectToRoute('sortie_detail', ['id' => $id]);
        }
        if (new \DateTime() >= $sortie->getDateLimiteInscription()) {
            $this->addFlash('error', 'Vous ne pouvez plus vous désister car la date limite d\'inscription est dépassé.');
            return $this->redirectToRoute('sortie_detail', ['id' => $id]);
        }

        // Retirer le participant de la sortie
        $sortie->removeParticipant($participant);
        //Verifier aprés
        $entityManager->persist($sortie);
        $entityManager->flush();

        // Ajouter un message de succès
        $this->addFlash('success', 'Vous avez été désinscrit de cette sortie avec succès.');

        // Rediriger vers la page de détail de la sortie
        return $this->redirectToRoute('sortie_detail', ['id' => $id]);
    }

}
