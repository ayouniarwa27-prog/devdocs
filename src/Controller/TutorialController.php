<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Tutorial;
use App\Repository\CommentRepository;
use App\Repository\TutorialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TutorialController extends AbstractController
{
    // Page détail d'un tutoriel, trouvé grâce à son slug
    #[Route('/tutorial/{slug:tutorial}', name: 'app_tutorial_show')]
    public function show(Tutorial $tutorial, CommentRepository $commentRepository): Response
    {
        // Commentaires de ce tutoriel, du plus récent au plus ancien
        $comments = $commentRepository->findBy(
            ['tutorial' => $tutorial],
            ['createdAt' => 'DESC']
        );

        return $this->render('tutorial/show.html.twig', [
            'tutorial' => $tutorial,
            'comments' => $comments,
        ]);
    }

    // Tutoriels d'une catégorie
    #[Route('/category/{id:category}', name: 'app_category_show')]
    public function category(Category $category, TutorialRepository $tutorialRepository): Response
    {
        return $this->render('tutorial/list.html.twig', [
            'title' => 'Catégorie : ' . $category->getName(),
            'tutorials' => $tutorialRepository->findBy(['category' => $category], ['createdAt' => 'DESC']),
        ]);
    }

    // Tutoriels associés à un tag
    #[Route('/tag/{id:tag}', name: 'app_tag_show')]
    public function tag(Tag $tag): Response
    {
        return $this->render('tutorial/list.html.twig', [
            'title' => 'Tag : ' . $tag->getName(),
            'tutorials' => $tag->getTutorials(),
        ]);
    }
}