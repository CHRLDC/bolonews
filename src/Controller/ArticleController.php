<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
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

    #[Route('/article/{id}', name: 'article_details', methods: ['GET'])]
    /**
     * Rôle: Afficher un article par son id
     * Pour affichage de la page d'un article à l'Index
     * @param Article $article
     */
    public function article(int $id, ArticleRepository $articleRepository): Response
    {
        // Obtenire l'article par son id
        $article = $articleRepository->find($id);

        // Si aucun article n'est trouvé, rediriger vers l'accueil
        if (!$article) {
            return $this->redirectToRoute('app_index');
        }

        // Rendre la page d'un article à l'Index avec ses paramètres
        return $this->render('article/article.html.twig', [
            'article' => $article
        ]);
    }
}
