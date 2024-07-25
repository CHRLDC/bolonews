<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\LikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LikeController extends AbstractController
{
    // Route pour basculer le like d'un article
    #[Route('/like/toggle/{id}', name: 'toggle_like', methods: ['POST', 'GET'])]
    public function toggleLike(int $id, EntityManagerInterface $entityManager, Security $security, LikeRepository $likeRepository): JsonResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $security->getUser();
        if (!$user) {
            // Si l'utilisateur n'est pas connecté, retourner un échec
            return new JsonResponse(['success' => false, 404]);
        }

        // Récupérer l'article par ID
        $article = $entityManager->getRepository(Article::class)->find($id);
        if (!$article) {
            // Si l'article n'existe pas, retourner un échec
            return new JsonResponse(['success' => false, 404]);
        }

        // Basculer le like et obtenir l'état actuel
        $isLiked = $likeRepository->toggleLike($user, $article);

        // Compter les likes après la mise à jour
        $likeCount = $article->getLikes()->count();

        // Renvoyer le résultat sous forme de JSON avec le nombre de likes mis à jour
        return new JsonResponse([
            'success' => true,
            'isLiked' => $isLiked,
            'likeCount' => $likeCount,
        ]);
    }
}
