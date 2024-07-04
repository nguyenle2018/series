<?php

namespace App\Controller;

use App\Form\models\SearchEvent;
use App\Form\SearchEventType;
use App\Service\SortieRecuperation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'non-connecter')]
    public function index(
        SortieRecuperation $sortieRecuperation,
        Request $request
    ): Response
    {
        $searchEvent = new SearchEvent();
        $formSearchEvent = $this->createForm(SearchEventType::class, $searchEvent);

        $formSearchEvent->handleRequest($request);
        $user = $this->getUser();

        if ($formSearchEvent->isSubmitted() && $formSearchEvent->isValid()) {
            $sorties = $sortieRecuperation->getAllSortiesAvecFiltres($searchEvent, $user);
        } else {
            $sorties = $sortieRecuperation->getAllSortiesSansFiltres();
        }

        return $this->render('sortie/liste-non-connecter.html.twig', [
            'sorties' => $sorties,
            'filterForm' => $formSearchEvent

        ]);

    }
}
