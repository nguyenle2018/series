<?php

namespace App\Controller;

use App\Entity\Etat;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\SerieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/sortie', name: 'sortie_')]
class SortieController extends AbstractController
{
    #[Route('/create', name: 'sortie_create')]
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
            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'Sortie créée avec succès !');

            return $this->redirectToRoute('sortie_detail', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/sortie.html.twig', [
            'sortieForm' => $sortieForm->createView(),
            'sortie' => $sortie, // Passer la variable sortie à la vue Twig

        ]);
    }

}

