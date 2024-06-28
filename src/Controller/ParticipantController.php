<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use App\Repository\ParticipantRepository;
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
        int                    $id = null
    ): Response
    {
        $participant = new Participant();

        $participantForm = $this->createForm(ParticipantType::class, $participant);
        $participantForm->handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){
            /*
             * @var Uploaded File $file
             * */
//            $participant->setNom($participant->getNom());
//            $participant->setPrenom($participant->getPrenom());
//            $participant->setMail($participant->getMail());
//            $participant->setTelephone($participant->getTelephone());
//            $participant->setPassword($participant->getPassword());
//            $participant->setCampus($participant->getCampus());
            $participant->setActif(true);


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
