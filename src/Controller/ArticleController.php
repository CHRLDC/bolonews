<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Form\NewArticleFormType;
use App\Form\CommentaireFormType;
use App\Form\RechercheArticleType;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    /**
     * Rôle: Récupérer l'article le plus populaire
     *       Récupérer la liste des derniers articles parus
     * Pour affichage de la page principale à l'Index
     */
    public function index(ArticleRepository $articleRepository): Response
    {
        // Obtenir l'id de l'article le plus populaire
        $articleId = $articleRepository->getArticleUne();

        // Récupérer l'article
        $articleUne = $articleRepository->find($articleId);

        // Obtenir la liste des dernières articles parus
        $liste_derniers_articles = $articleRepository->findBy(['publie' => true], ['id' => 'DESC'], 4);

        // Rendre la page principale à l'Index avec ses paramètres
        return $this->render('article/index.html.twig', [
            'articleUne' => $articleUne,
            'liste_derniers_articles' => $liste_derniers_articles
        ]);
    }

    #[Route('/articles/categorie={libelle}', name: 'liste_article', methods: ['GET', 'POST'], requirements: ['libelle' => '^(?!new$).*'])]
    public function liste(
        ArticleRepository $articleRepository,
        CategorieRepository $categorieRepository,
        Request $request,
        // Si il y a un libelle en paramètre, sinon null
        ?string $libelle = null,
    ): Response {

        // Créer le formulaire de recherche
        $form = $this->createForm(RechercheArticleType::class);
        $form->handleRequest($request);

        // Valeurs par défaut
        $search = '';
        $isSearch = false;
        $articles = [];

        // Si une recherche est soumise et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->get('search')->getData();
            if (!empty($search)) {
                // Chercher les articles correspondant à la valeur du champ
                $articles = $articleRepository->rechercheArticle($search);
                $isSearch = true;
            }
        }

        // Filtrer par catégorie si libelle est fourni en method GET
        if ($request->isMethod('GET')) {
            $categorie = $categorieRepository->findOneBy(['libelle' => $libelle]);
            if (!empty($categorie)) {
                $articles = $categorie->getArticles();
                $isSearch = true;
            }
        }

        // Si aucun article n'est trouvé, chercher tous les articles
        if (empty($articles)) {
            $articles = $articleRepository->findAll(['publie' => true]);
        }

        // Obtenir la liste de toutes les catégories
        $categories = $categorieRepository->findAll(['publie' => true]);

        // Rendre la page des articles
        return $this->render('article/liste_article.html.twig', [
            'articles' => $articles,
            'form' => $form->createView(),
            'isSearch' => $isSearch,
            'categories' => $categories,
            'search' => $search
        ]);
    }

    #[Route('/article/id={id}', name: 'article_details', methods: ['GET', 'POST'], requirements: ['libelle' => '^(?!new$).*'])]
    public function article(Request $request, int $id, ArticleRepository $articleRepository, EntityManagerInterface $entityManager, LikeRepository $likeRepository): Response
    {
        // Obtenir l'article par son id
        $article = $articleRepository->find($id);

        // Si aucun article n'est trouvé, rediriger vers l'accueil
        if (!$article) {
            return $this->redirectToRoute('app_index');
        }

        // Créer le formulaire d'ajout de commentaire
        $commentaire = new Commentaire();
        $commentaireForm = $this->createForm(CommentaireFormType::class, $commentaire);
        $commentaireForm->handleRequest($request);

        // Si le formulaire est envoyé et valide
        if ($commentaireForm->isSubmitted() && $commentaireForm->isValid()) {
            // Associer le commentaire à l'article
            $commentaire->setArticle($article);

            // Associer le commentaire à l'utilisateur connecté (si l'utilisateur est connecté)
            $user = $this->getUser();
            if ($user) {
                $commentaire->setUser($user);
            }
            // Définir la date de publication
            $commentaire->setDatePublication(new \DateTime());

            // Ajouter le commentaire à la base de données
            $entityManager->persist($commentaire);
            $entityManager->flush();

            // Rediriger vers l'article
            return $this->redirectToRoute('article_details', [
                'id' => $article->getId(),
            ]);
        }

        // Rendre la page d'un article à l'Index avec ses paramètres
        return $this->render('article/details_article.html.twig', [
            'article' => $article,
            'form' => $commentaireForm->createView(),

        ]);
    }


    #[Route('/espace', name: 'espace_user', methods: ['GET', 'POST'])]
    public function espaceUser(ArticleRepository $articleRepository, Security $security): Response
    {
        // Récupère l'utilisateur connecté
        $user = $security->getUser();

        // Obtenir les articles de l'utilisateur non publiés
        $articlesNonPublies = $articleRepository->findBy([
            'User' => $user,
            'publie' => false,
        ]);

        // Obtenir les articles de l'utilisateur publiés
        $articlesPublies = $articleRepository->findBy([
            'User' => $user,
            'publie' => true,
        ]);

        return $this->render('user/espace_user.html.twig', [
            'articlesNonPublies' => $articlesNonPublies,
            'articlesPublies' => $articlesPublies,
        ]);
    }

    #[Route('/article/new', name: 'article_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // Créer un nouvel objet Article
        $article = new Article();

        // Créer le formulaire de création d'un nouvel article
        $form = $this->createForm(NewArticleFormType::class, $article);
        $form->handleRequest($request);

        // Si le formulaire est soumis
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer l'utilisateur connecté
            $user = $this->getUser();

            if ($user) {
                // Associer l'article à l'utilisateur connecté
                $article->setUser($user);
            }

            // Récupérer la photo envoyée par l'utilisateur
            $photoFichier = $form->get('image')->getData();

            // Si une photo a été envoyée
            if ($photoFichier) {
                // Générer un nom unique pour le fichier
                $originalFilename = pathinfo($photoFichier->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFichier->guessExtension();

                try {
                    // Déplacer le fichier vers le répertoire configuré
                    $photoFichier->move(
                        $this->getParameter('photos_article_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    // Gérer l'exception si nécessaire
                    $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de l\'image.');
                    return $this->redirectToRoute('article_new');
                }

                // Mettre à jour le nom de la photo dans l'entité
                $article->setImage($newFilename);
            }

            // Définir la date de création et de modification avec la date du jour
            $article->setDateCreation(new \DateTime());
            $article->setDateModification(new \DateTime());

            // Enregistrer l'article dans la base de données
            $entityManager->persist($article);
            $entityManager->flush();

            // Ajouter un message flash et rediriger vers les détails de l'article
            $this->addFlash('success', 'Article créé avec succès.');
            return $this->redirectToRoute('article_details', ['id' => $article->getId()]);
        }

        // Sinon afficher de nouveau le formulaire avec les données saisies
        return $this->render('article/new_article.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/article/{id}/edit', name: 'article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        // Trouver l'article à éditer
        $article = $entityManager->getRepository(Article::class)->find($id);

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Vérifier si l'utilisateur est soit le propriétaire de l'article, soit un administrateur
        if (!$user || $article->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas les droits nécessaires pour modifier cet article.');
            return $this->redirectToRoute('liste_article');
        }

        // Si l'article n'existe pas, rediriger vers la liste des articles
        if (!$article) {
            $this->addFlash('error', 'Article non trouvé.');
            return $this->redirectToRoute('liste_article');
        }

        // Créer le formulaire avec les données de l'article
        $form = $this->createForm(NewArticleFormType::class, $article);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Définir la date de modification avec la date du jour
            $article->setDateModification(new \DateTime());

            // Sauvegarder les modifications dans la base de données
            $entityManager->persist($article);
            $entityManager->flush();

            // Ajouter un message flash pour indiquer le succès
            $this->addFlash('success', 'Article mis à jour avec succès.');

            // Rediriger vers la page de détails de l'article
            return $this->redirectToRoute('article_details', ['id' => $article->getId()]);
        }

        // Rendre le formulaire d'édition
        return $this->render('article/edit_article.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }
}
