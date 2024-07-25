<?php

namespace App\Controller;


use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController
{
    // Route pour lister tous les utilisateurs
    #[Route('/admin/users', name: 'user_liste')]
    public function liste(UserRepository $userRepository): Response
    {
        // Récupérer tous les utilisateurs
        $users = $userRepository->findAll();

        // Rendre la vue avec les utilisateurs
        return $this->render('admin/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    // Route pour modifier un utilisateur
    #[Route('/admin/user/{id}/edit', name: 'user_edit')]
    public function edit(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur par son ID
        $user = $userRepository->find($id);

        // Si l'utilisateur n'existe pas, rediriger ou afficher une erreur
        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur n\'existe pas.');
        }

        // Créer et gérer le formulaire d'édition
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        // Si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            // Ajouter un message flash pour indiquer le succès
            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');

            // Rediriger vers la liste des utilisateurs ou vers les détails de l'utilisateur
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('user_liste');
        }

        // Rendre la vue avec le formulaire d'édition
        return $this->render('admin/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
