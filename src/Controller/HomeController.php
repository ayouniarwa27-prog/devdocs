<?php

namespace App\Controller;

use App\Repository\TutorialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(TutorialRepository $tutorialRepository): Response
    {
        // Récupère tous les tutoriels, du plus récent au plus ancien
      $tutorials = $tutorialRepository->findBy([], ['createdAt' => 'DESC'], 6);

        // Envoie la liste à la vue Twig
        return $this->render('home/index.html.twig', [
            'tutorials' => $tutorials,
        ]);
    }
}