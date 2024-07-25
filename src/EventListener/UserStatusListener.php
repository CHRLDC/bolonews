<?php

/**
 * Rôle: Interdire la connexion d'un utilisateur ayant pour statut "bloque"
 * (Enregistrer dans services.yaml)
 */

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use App\Entity\User;

class UserStatusListener implements EventSubscriberInterface
{
    // Récupérer l'événement CheckPassportEvent
    public static function getSubscribedEvents(): array
    {
        return [
            // Appeler la méthode onCheckPassport lorsque l'événement est déclenché
            CheckPassportEvent::class => 'onCheckPassport',
        ];
    }

    // Quand une tentative de connexion à lieu
    public function onCheckPassport(CheckPassportEvent $event): void
    {
        // On récupère le passeport de l'événement
        $passport = $event->getPassport();

        // On vérifie que le passeport est bien une instance de passport
        if (!$passport instanceof Passport) {
            return;
        }

        // On récupère l'utilisateur du passeport
        $user = $passport->getUser();

        // On vérifie que l'utilisateur est une instance User
        if ($user instanceof User) {
            // Si le statut de l'utilisateur est 'bloque', créer une exception pour bloquer la connexion
            if ($user->getStatut() === 'bloque') {
                throw new CustomUserMessageAuthenticationException();
            }
        }
    }
}
