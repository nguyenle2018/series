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

#[Route('/participants', name: 'participant_')]
class ParticipantController extends AbstractController
{
    #[Route('/create', name: 'create')]
    public function create(
        ParticipantRepository   $participantRepository,
        EntityManagerInterface  $entityManager,
        Request                 $request
    ): Response
    {
        $participant = new Participant();

        $participantForm = $this->createForm(ParticipantType::class, $participant);
        $participantForm->handleRequest($request);

        if($participantForm->isSubmitted() && $participantForm->isValid()){
            /*
             * @var Uploaded File $file
             * */
            $participant->setNom($participant->getNom());
            $participant->setPrenom($participant->getNom());
            $participant->setMail($participant->getMail());
            $participant->setTelephone($participant->getTelephone());
            $participant->setMotPasse($participant->getMotPasse());
            $participant->setCampus($participant->getCampus());


            $entityManager->persist($participant);

            $entityManager->flush();
        }

        return $this->render('participant/create.html.twig', [
            'participantForm' => $participantForm
        ]);
    }
}
