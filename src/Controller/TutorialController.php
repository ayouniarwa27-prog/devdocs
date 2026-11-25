<?php

namespace App\Controller;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Tag;
use App\Entity\Tutorial;
use App\Form\CommentType;
use App\Form\TutorialType;
use App\Repository\CommentRepository;
use App\Repository\TutorialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class TutorialController extends AbstractController
{
    // Création d'un tutoriel (membres connectés uniquement)
    #[Route('/tutorial/new', name: 'app_tutorial_new', priority: 10)]
    #[IsGranted('ROLE_USER')]
        public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        MailerInterface $mailer,
        #[Autowire('%env(ADMIN_EMAIL)%')] string $adminEmail,
    ): Response
    {
        $tutorial = new Tutorial();
        $form = $this->createForm(TutorialType::class, $tutorial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tutorial->setAuthor($this->getUser());
            $tutorial->setCreatedAt(new \DateTimeImmutable());
            $tutorial->setSlug(strtolower($slugger->slug($tutorial->getTitle())));

            $entityManager->persist($tutorial);
            $entityManager->flush();
                        // Notification à l'administrateur
            $email = (new TemplatedEmail())
                ->from(new Address('noreply@devdocs.local', 'DevDocs'))
                ->to($adminEmail)
                ->subject('Nouveau tutoriel : ' . $tutorial->getTitle())
                ->htmlTemplate('emails/new_tutorial.html.twig')
                ->context(['tutorial' => $tutorial]);

            $mailer->send($email);


            return $this->redirectToRoute('app_tutorial_show', ['slug' => $tutorial->getSlug()]);
        }

        return $this->render('tutorial/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Page détail d'un tutoriel + ajout de commentaire
    #[Route('/tutorial/{slug:tutorial}', name: 'app_tutorial_show')]
    public function show(Tutorial $tutorial, CommentRepository $commentRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Seuls les membres connectés peuvent commenter
            $this->denyAccessUnlessGranted('ROLE_USER');

            $comment->setAuthor($this->getUser());
            $comment->setTutorial($tutorial);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('app_tutorial_show', ['slug' => $tutorial->getSlug()]);
        }

        $comments = $commentRepository->findBy(
            ['tutorial' => $tutorial],
            ['createdAt' => 'DESC']
        );

        return $this->render('tutorial/show.html.twig', [
            'tutorial' => $tutorial,
            'comments' => $comments,
            'form' => $form,
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
        // Modification : seulement l'auteur (vérifié par le Voter)
    #[Route('/tutorial/{slug:tutorial}/edit', name: 'app_tutorial_edit')]
    #[IsGranted('TUTORIAL_EDIT', subject: 'tutorial')]
    public function edit(Tutorial $tutorial, Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TutorialType::class, $tutorial);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tutorial_show', ['slug' => $tutorial->getSlug()]);
        }

        return $this->render('tutorial/edit.html.twig', [
            'form' => $form,
            'tutorial' => $tutorial,
        ]);
    }

    // Suppression : seulement l'auteur (vérifié par le Voter)
    #[Route('/tutorial/{slug:tutorial}/delete', name: 'app_tutorial_delete', methods: ['POST'])]
    #[IsGranted('TUTORIAL_DELETE', subject: 'tutorial')]
    public function delete(Tutorial $tutorial, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $tutorial->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tutorial);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_home');
    }
}