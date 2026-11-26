<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\UserRepository;

final class GuestController extends AbstractController
{

    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/index.html.twig', [
            'controller_name' => 'GuestController',
            'guests' => $userRepository->findByRoles('ROLE_USER'),
        ]);
    }

    #[Route('/admin/guest/deactivate/{id}', name: 'admin_guest_deactivate', methods: ['POST'])]
    public function deactivate(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setDeactivationDate(new \DateTimeImmutable());
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/reactivate/{id}', name: 'admin_guest_reactivate', methods: ['POST'])]
    public function activate(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setDeactivationDate(null);
        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete', methods: ['POST'])]
    public function delete(EntityManagerInterface $entityManager, User $user): Response
    {
        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/add', name: 'admin_guest_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $newGuest = new User();
        $form = $this->createForm(UserType::class, $newGuest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $newGuest->setRoles(['ROLE_USER']);
            $newGuest->setDeactivationDate(null);
            $newGuest->setFirstConnection(true);
            $temporaryPassword = $this->generateTemporaryPassword();
            $newGuest->setPassword($passwordHasher->hashPassword($newGuest, $temporaryPassword));

            $entityManager->persist($newGuest);
            $entityManager->flush();

            $this->addFlash('success', 'Invité ajouté avec succès. Communiquez les informations ci-dessous à l\'invité.');

            // Display the email and temporary password to the admin
            //TO DO: Consider sending this information via email instead of flash message for security reasons
            $this->addFlash('info', 'Email: ' . $newGuest->getEmail() . ' Temporary password: ' . $temporaryPassword);
            
            return $this->redirectToRoute('admin_guest_index');
        }

        return $this->render('admin/guest/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function generateTemporaryPassword(): string
    {
        return bin2hex(random_bytes(8));
    }

}

