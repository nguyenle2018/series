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
use App\Service\SortieRecuperation;
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
    public function create(
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $sortie = new Sortie();

        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {

            // Associer le participant à la sortie
            $participant = $this->getUser();
            $sortie->setOrganisateur($participant);
            $sortie->addParticipant($participant);

            //ajout de la sortie à la liste des sorties du lieu
            $lieuDeLaSortieEnBase = $entityManager->getRepository(Lieu::class)->find($sortie->getLieu());
            $lieuDeLaSortieEnBase->addSorty($sortie);
            //enregistrement de la liste des sorties :
            $entityManager->persist($lieuDeLaSortieEnBase);

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
            'sortie' => $sortie,
        ]);
    }

    #[Route('/liste', name: 'liste')]
    public function getAll(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRecuperation $sortieRecuperation,
    ): Response
    {
        $searchEvent = new SearchEvent();
        $formSearchEvent = $this->createForm(SearchEventType::class, $searchEvent);
        $formSearchEvent->handleRequest($request);

        $user = $this->getUser();

        $sorties = $sortieRecuperation->getAllSorties($searchEvent, $user);

        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties,
            'filterForm'=> $formSearchEvent
        ]);
    }

    #[Route('/update/{id}', name: 'update')]
    public function update(
        Sortie $sortie,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
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
        int     $id
    ): Response
    {
        $sortie = $entityManager->getRepository(Sortie::class)->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('La sortie n\'existe pas.');
        }

        $participants = $sortie->getParticipants();

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
        if ($sortie->getEtat()->getLibelle() === 'Ouverte') {
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
        }

        $this->addFlash('error', 'La sortie ne peut pas être annulée car elle a déjà commencé.');
        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties
        ]);
    }

    #[Route('/inscription/{id}', name: 'inscription', requirements: ['id' => '\d+'])]
    public function inscription(
        EntityManagerInterface $entityManager,
        SortieRepository $sortieRepository,
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
        if ($participant === $sortie->getOrganisateur()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous désinscrire d\'une sortie dont vous êtes l\'organisateur');
            return $this->redirectToRoute('sortie_detail', ['id' => $id]);
        } else {
            $sortie->removeParticipant($participant);

            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Vous avez été désinscrit de cette sortie avec succès.');
            return $this->redirectToRoute('sortie_detail', ['id' => $id]);
        }

    }

}
