<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Rôle retourner l'id de l'article le plus populaire
     * Like et commentaires
     * @return int id de l'article
     */
    public function getArticleUne(): int
    {
        // Créer la requête pour obtenir l'article le plus populaire
        $qb = $this->createQueryBuilder('a')
            // Jointure avec les likes et les commentaires
            ->leftJoin('a.likes', 'l')
            ->leftJoin('a.commentaires', 'c')
            // Filtrer pour obtenir seulement les articles publiés
            ->where('a.publie = :publie')
            ->setParameter('publie', true)
            // Sélectionner l'article et compter les likes et commentaires
            ->select('a.id')
            ->addSelect('COUNT(l.id) AS HIDDEN likes_count')
            ->addSelect('COUNT(c.id) AS HIDDEN commentaires_count')
            // Grouper par article
            ->groupBy('a.id')
            // Trier pour avoir en premier l'article le plus populaire
            ->orderBy('likes_count', 'DESC')
            ->addOrderBy('commentaires_count', 'DESC')
            // Se limiter au premier article
            ->setMaxResults(1);

        // Exécuter la requête, et récupérer le premier article
        $result = $qb->getQuery()->getOneOrNullResult();

        // Si aucun article n'est trouvé, obtenir l'ID du premier article de la liste existant
        if ($result === null) {
            $qbFallback = $this->createQueryBuilder('a')
                ->select('a.id')
                ->where('a.publie = :publie')
                ->setParameter('publie', true)
                ->setMaxResults(1)
                ->orderBy('a.id', 'ASC'); // Obtenir le premier article par ID croissant

            $fallbackResult = $qbFallback->getQuery()->getOneOrNullResult();

            if ($fallbackResult === null) {
                throw new \RuntimeException('Aucun article trouvé dans la base de données.');
            }

            return $fallbackResult['id'];
        }

        // Retourner l'ID de l'article le plus populaire
        return $result['id'];
    }


    /**
     * Trouver tous les articles publiés
     */
    public function findAllPublie()
    {
        return $this->createQueryBuilder('a')
            ->where('a.publie = :publie')
            ->setParameter('publie', true)
            ->getQuery()
            ->getResult();
    }


    /**
     * Rechercher des articles par titre, chapeau ou texte.
     *
     * @param string $search
     * @return Article[]
     */
    public function rechercheArticle(string $search): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.titre LIKE :search')
            ->orWhere('a.chapeau LIKE :search')
            ->orWhere('a.texte LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->getQuery()
            ->getResult();
    }


    //    /**
    //     * @return Article[] Returns an array of Article objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Article
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
