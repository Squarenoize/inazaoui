<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

    #[Route('/admin/guest/deactivate/{id}', name: 'admin_guest_deactivate')]
    public function deactivate(int $id, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if ($user) {
            $user->setDeactivationDate(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/reactivate/{id}', name: 'admin_guest_reactivate')]
    public function activate(int $id, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if ($user) {
            $user->setDeactivationDate(null);
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_guest_index');
    }
}
