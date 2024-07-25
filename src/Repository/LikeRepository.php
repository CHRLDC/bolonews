<?php

namespace App\Repository;

use App\Entity\Like;
use App\Entity\Article;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Like::class);
    }

    /**
     * Trouver si un like existe entre utilisateur et article
     *
     */
    public function findByUserAndArticle(User $user, Article $article): ?Like
    {
        return $this->findOneBy(['User' => $user, 'Article' => $article]);
    }

    /**
     * Bascule le like pour un utilisateur et un article
     * @param User $user l'utilisateur qui a fait le like
     * @param Article $article l'article que l'utilisateur a like
     * @return bool le nouvel etat du like
     */
    public function toggleLike(User $user, Article $article): bool
    {
        // Vérifie si le like existe déjà
        $like = $this->findByUserAndArticle($user, $article);
        $isLiked = false;

        $entityManager = $this->getEntityManager(); // Utiliser getEntityManager()

        if ($like) {
            // Si le like existe, le supprimer
            $entityManager->remove($like);
        } else {
            // Sinon, créer un nouveau like
            $like = new Like();
            $like->setUser($user);
            $like->setArticle($article);
            $entityManager->persist($like);
            $isLiked = true;
        }

        // Appliquer les changements à la base de données
        $entityManager->flush();

        return $isLiked;
    }
}
