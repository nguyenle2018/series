<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
use App\Utils\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/participants', name: 'participants_')]
class ParticipantController extends AbstractController
{
    #[Route('/update/{id}', name: 'update', requirements: ['id' => '\d+'])]
    public function update(
        ParticipantRepository   $participantRepository,
        EntityManagerInterface  $entityManager,
        Request                 $request,
        UserPasswordHasherInterface $userPasswordHasher,
        FileUpLoader            $fileUploader,
        int                    $id = null
    ): Response
    {

        // Récupérer le participant à partir de l'ID
        $participant = $participantRepository->find($id);

        // Vérifier si l'utilisateur connecté est bien le propriétaire du profil
        $user = $this->getUser();
        if (!$user || $participant->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce profil.');
        }


        //creation du formulaire associé à l'instance de sortie
        $participantForm = $this->createForm(ParticipantType::class, $participant);

        //extraie des informations de la requête HTTP
        $participantForm->handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){

            $participant->setActif(true);

            $plainPassword = $participant->getPassword();
            $participant->setPassword($userPasswordHasher->hashPassword($participant,$plainPassword));

            $file = $participantForm->get('photoFilename')->getData();
            if ($file) {
                $newFilename = $fileUploader->upload($file, $this->getParameter('participant_photoFilename_directory'), $participant->getNom());
                $participant->setPhotoFilename($newFilename);
            }

            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('participants_detail', ['id' => $participant->getId()]);

        }

        return $this->render('participant/create.html.twig', [
            'participantForm' => $participantForm
        ]);
    }

    #[Route('/detail/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(
        ParticipantRepository   $participantRepository,
        int                     $id
    ): Response
    {
        $participant = $participantRepository->find($id);

        return $this->render('participant/detail.html.twig', [
            'participant' => $participant
        ]);
    }

}
