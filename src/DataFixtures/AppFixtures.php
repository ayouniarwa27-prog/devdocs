<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Tag;
use App\Entity\Tutorial;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private SluggerInterface $slugger,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // --- 3 utilisateurs ---
        $users = [];
        foreach (['Alice', 'Bob', 'Charlie'] as $pseudo) {
            $user = new User();
            $user->setEmail(strtolower($pseudo) . '@devdocs.local');
            $user->setPseudonyme($pseudo);
            $user->setPassword($this->hasher->hashPassword($user, 'password'));
            $manager->persist($user);
            $users[] = $user;
        }

        // --- 5 catégories ---
        $categoriesData = [
            'PHP' => 'Tutoriels sur le langage PHP et ses frameworks.',
            'DevOps' => 'Déploiement, conteneurs, intégration continue.',
            'Frontend' => 'HTML, CSS, JavaScript et frameworks front.',
            'Base de données' => 'SQL, modélisation et optimisation des requêtes.',
            'Sécurité' => 'Bonnes pratiques pour sécuriser vos applications.',
        ];
        $categories = [];
        foreach ($categoriesData as $name => $description) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $manager->persist($category);
            $categories[] = $category;
        }

        // --- 10 tags ---
        $tags = [];
        foreach (['Débutant', 'Intermédiaire', 'Avancé', 'Performance', 'Sécurité',
                  'Bonnes pratiques', 'Symfony', 'Docker', 'JavaScript', 'SQL'] as $name) {
            $tag = new Tag();
            $tag->setName($name);
            $manager->persist($tag);
            $tags[] = $tag;
        }

        // --- 15 tutoriels ---
        $titles = [
            'Débuter avec Symfony 7', 'Comprendre Doctrine ORM', 'Les routes en Symfony',
            'Installer Docker sous Ubuntu', 'Créer un pipeline GitHub Actions', 'Flexbox expliqué simplement',
            'Les bases de JavaScript moderne', 'Optimiser ses requêtes SQL', 'Les jointures SQL en pratique',
            'Protéger un site contre les failles XSS', 'Hacher les mots de passe correctement', 'Twig pour les débutants',
            'Déployer une application PHP', 'Bootstrap 5 : la grille responsive', 'Comprendre les index en base de données',
        ];
        $tutorials = [];
        foreach ($titles as $title) {
            $tutorial = new Tutorial();
            $tutorial->setTitle($title);
            $tutorial->setSlug(strtolower($this->slugger->slug($title)));
            $tutorial->setContent(implode("\n\n", $faker->paragraphs(5)));
            $tutorial->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months')));
            $tutorial->setAuthor($faker->randomElement($users));
            $tutorial->setCategory($faker->randomElement($categories));
            foreach ($faker->randomElements($tags, rand(1, 3)) as $tag) {
                $tutorial->addTag($tag);
            }
            $manager->persist($tutorial);
            $tutorials[] = $tutorial;
        }

        // --- 30 commentaires (2 par tutoriel) ---
        $phrases = [
            'Merci, très clair !', 'Exactement ce que je cherchais.',
            'Est-ce que ça marche aussi avec la version précédente ?',
            'Super tutoriel, bien expliqué.', 'J\'ai eu une erreur à la 3e étape, une idée ?',
            'Très utile pour mon projet, merci.', 'Pourrais-tu faire une suite sur ce sujet ?',
        ];
        foreach ($tutorials as $tutorial) {
            for ($i = 0; $i < 2; $i++) {
                $comment = new Comment();
                $comment->setContent($faker->randomElement($phrases));
                $date = $faker->dateTimeBetween($tutorial->getCreatedAt()->format('Y-m-d H:i:s'), 'now');
                $comment->setCreatedAt(\DateTimeImmutable::createFromMutable($date));
                $comment->setAuthor($faker->randomElement($users));
                $comment->setTutorial($tutorial);
                $manager->persist($comment);
            }
        }

        $manager->flush();
    }
}