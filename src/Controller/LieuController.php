<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Ville;
use App\Form\LieuType;
use App\Form\SortieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/lieu', name: 'lieu_')]
class LieuController extends AbstractController
{
    #[Route('/create', name: 'creation')]
    public function create(
        EntityManagerInterface $entityManager,
        Request $request,
        FormInterface $sortieForm

    ): Response
    {

        $lieuRepository = $entityManager->getRepository(Lieu::class);
        $villeRepository = $entityManager->getRepository(Ville::class);
        $lieu = new Lieu();

        $lieuForm = $this->createForm(LieuType::class, $lieu);
        $lieuForm =$lieuForm->handleRequest($request);


        if ($lieuForm->isSubmitted() && $lieuForm->isValid()){

            // on vérifie si le lieu n'est pas déjà enregistrer en base
            $lieuExistant = $lieuRepository->findBy(
                ['nom' => $lieu->getNom(),
                'rue' => $lieu->getRue()]
            );
            //et si ce lieu n'existe pas dans la ville selectionnée
            $villeDuLieu = $villeRepository->findBy(
                ['nom' => $lieu->getVille()->getNom(),
                'codePostal' => $lieu->getVille()->getCodePostal()]
            );
            $isLieucontenuDansVille = $villeDuLieu[0]->getLieus()->contains($lieu);

            //si le lieu existe déjà
            if(!empty($lieuExistant) && $isLieucontenuDansVille){
                $this->addFlash('error', 'Ce lieu existe déjà en base, merci de créer un lieu inexistant');
                return $this->redirectToRoute('app_login');
            }

            $entityManager->persist($lieu);
            $entityManager->flush();
            $this->addFlash('success', 'Le lieu a été ajouté en base de donnée avec succès');


            return $this->redirectToRoute('sortie_create');
        }

        return $this->render('lieu/index.html.twig', [
            'lieuForm' => $lieuForm
        ]);
    }
}
