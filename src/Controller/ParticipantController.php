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
use Symfony\Component\Routing\Attribute\Route;


#[Route('/participants', name: 'participants_')]
class ParticipantController extends AbstractController
{
    #[Route('/create', name: 'create')]
    #[Route('/update/{id}', name: 'update')]
    public function create(
        ParticipantRepository   $participantRepository,
        EntityManagerInterface  $entityManager,
        Request                 $request,
        Participant             $participant,
        FileUpLoader            $fileUploader,
        int                    $id = null
    ): Response
    {
        //créer une instance de l'entité
        $participant = new Participant();

        //creation du formulaire associé à l'instance de serie
        $participantForm = $this->createForm(ParticipantType::class, $participant);

        //extraie des informations de la requête HTTP
        $participantForm->handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){

            $participant->setActif(true);

            $file = $participantForm->get('photoFilename')->getData();
            $newFilename = $fileUploader->upload($file, $this->getParameter('participant_photoFilename_directory'), $participant->getNom());
            $participant->setPhotoFilename($newFilename);

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
