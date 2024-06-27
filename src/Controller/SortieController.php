<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

            // Récupérer l'utilisateur connecté
            //$user = $this->getUser();

            $participant = $entityManager->getRepository(Participant::class)->find(196);
            $etat = $entityManager->getRepository(Etat::class)->find(115);
            //dd($participant);

            // Associer le participant à la sortie
            $sortie->setOrganisateur($participant);
            $sortie->setEtat($etat);

            //condition sur les dates
//            $dateHeureDebut = $sortie->getDateHeureDebut();
//            $dateLimiteInscription = $sortie->getDateLimiteInscription();
//
//            if ($dateHeureDebut <= $dateLimiteInscription) {
//                $this->addFlash('error', 'La date et heure de début doivent être après la date limite d\'inscription.');
//                return $this->redirectToRoute('/create');
//            }

            $entityManager->persist($sortie);
            $entityManager->flush();

            //ajout de la sortie à la liste des sorties du lieu
            $lieuDeLaSortieEnBase = $entityManager->getRepository(Lieu::class)->find($sortie->getLieu());
            $lieuDeLaSortieEnBase->addSorty($sortie);
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
        EntityManagerInterface $entityManager,
        Request $request
    ): Response
    {
        $sorties = $entityManager->getRepository(Sortie::class)->findAll();

        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties,
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
        Request $request,
        SortieRepository $sortieRepository,
        int     $id
    ): Response
    {
        $sorties = $entityManager->getRepository(Sortie::class)->findAll();

        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/annuler/{id}', name: 'annuler', requirements: ['id' => '\d+'])]
    public function annuler(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        int     $id
    ): Response
    {
        $sorties = $entityManager->getRepository(Sortie::class)->findAll();

        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties,
        ]);
    }

    #[Route('/inscription/{id}/{idParticipant}', name: 'inscription', requirements: ['id' => '\d+', 'idParticipants' => '\d+'])]
    public function inscription(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        ParticipantRepository $participantRepository,
        int     $id,
        int $idParticipant
    ): Response
    {
        $sortie = $sortieRepository->find($id);
        $participant = $entityManager->getRepository(Participant::class)->find($idParticipant);
        if(!$sortie || !$participant){
            throw $this->createNotFoundException('Sortie ou Participant non trouvée !!');
        }
        $now = new \DateTime();

        //vérifier des conditions
        if ($sortie->getEtat()->getLibelle() !== 'Ouverte' ||
            $sortie->getDateLimiteInscription() < $now ||
            count($sortie->getParticipants()) >= $sortie->getNbInscriptionsMax()
        ) {
            // Gérer ici le cas où les conditions d'inscription ne sont pas remplies
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire à cette sortie pour le moment.');

            return $this->redirectToRoute('sortie_liste');
        }

        // Ajouter le participant à la sortie
        $sortie->addParticipant($participant);
        $entityManager->flush();


        return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);

    }

    #[Route('/desistement/{id}/{idParticipant}', name: 'desistement', requirements: ['id' => '\d+', 'idParticipants' => '\d+'])]
    public function desistement(
        EntityManagerInterface $entityManager,
        Request $request,
        SortieRepository $sortieRepository,
        int     $id,
        int $idParticipant
    ): Response
    {
        $sorties = $entityManager->getRepository(Sortie::class)->findAll();

        return $this->render('sortie/liste.html.twig', [
            'sorties' => $sorties,
        ]);
    }

}

