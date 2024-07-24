<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Form\CommentaireFormType;
use App\Form\RechercheArticleType;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
        $liste_derniers_articles = $articleRepository->findBy([], ['id' => 'DESC'], 4);

        // Rendre la page principale à l'Index avec ses paramètres
        return $this->render('article/index.html.twig', [
            'articleUne' => $articleUne,
            'liste_derniers_articles' => $liste_derniers_articles
        ]);
    }

    #[Route('/articles/{libelle}', name: 'liste_article', methods: ['GET', 'POST'])]
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
            $articles = $articleRepository->findAll();
        }

        // Obtenir la liste de toutes les catégories
        $categories = $categorieRepository->findAll();

        // Rendre la page des articles
        return $this->render('article/liste_article.html.twig', [
            'articles' => $articles,
            'form' => $form->createView(),
            'isSearch' => $isSearch,
            'categories' => $categories,
            'search' => $search
        ]);
    }

    #[Route('/article/{id}', name: 'article_details', methods: ['GET', 'POST'])]
    public function article(Request $request, int $id, ArticleRepository $articleRepository, EntityManagerInterface $entityManager): Response
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
            return $this->redirectToRoute('article_details', ['id' => $article->getId()]);
        }

        // Rendre la page d'un article à l'Index avec ses paramètres
        return $this->render('article/details_article.html.twig', [
            'article' => $article,
            'form' => $commentaireForm->createView(),
        ]);
    }
}
