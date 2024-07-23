<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // Créer le formulaire de création d'un nouvel utilisateur
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est envoyé
        if ($form->isSubmitted() && $form->isValid()) {
            // Crypter le mot de passe
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Récupérer la photo envoyée par l'utilisateur
            $photoFichier = $form->get('photo')->getData();

            // Si une photo a été envoyée:
            if ($photoFichier) {
                // Générer un nom unique pour le fichier
                $nomFichier = uniqid() . '.' . $photoFichier->guessExtension();

                // Déplacer le fichier vers le répertoire private/img_profils
                $photoFichier->move($this->getParameter('photos_directory'), $nomFichier);

                // Mettre à jour le nom de la photo dans l'entity
                $user->setPhoto($nomFichier);
            }

            // Enregistrer l'utilisateur dans la base de données
            $entityManager->persist($user);
            $entityManager->flush();

            // Rediriger vers l'accueil
            return $this->redirectToRoute('app_index');
        }


        // Sinon afficher de nouveau le formulaire avec les données saisies
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
