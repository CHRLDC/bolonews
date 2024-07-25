<?php

namespace App\Services\Twig;

use App\Repository\LikeRepository;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

class MyFilters extends AbstractExtension
{
    private $likeRepository;

    public function __construct(LikeRepository $likeRepository)
    {
        $this->likeRepository = $likeRepository;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('isLiked', [$this, 'isLiked']),
        ];
    }

    /**
     * Vérifie si l'article est liké par l'utilisateur
     *
     * @param $article
     * @param $user
     * @return bool
     */
    public function isLiked($article, $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->likeRepository->findByUserAndArticle($user, $article) !== null;
    }
}
