<?php

namespace App\Controller;

use App\Form\UserType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class UserController extends AbstractController
{
    #[Route('/profil', name: 'app_profilUser')]
    public function profilUser(Request $request, EntityManagerInterface $entityManager, Security $security, SluggerInterface $slugger): Response
    {
        $user = $security->getUser(); // Récupère l'utilisateur connecté

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Récupérer la photo envoyée par l'utilisateur
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/profile_pictures',
                        $newFilename
                    );
                } catch (FileException $e) {
                }
            }

            // Mettre à jour le nom de la photo dans l'entity
            $entityManager->flush();


            return $this->redirectToRoute('app_profil');
        }

        return $this->render('user/profil_user.html.twig', [
            'form' => $form->createView(),
            'User' => $user,
        ]);
    }
}
