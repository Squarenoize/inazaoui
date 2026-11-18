<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Repository\UserRepository;

final class GuestController extends AbstractController
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/guest/index.html.twig', [
            'controller_name' => 'GuestController',
            'guests' => $userRepository->findByRoles('ROLE_USER'),
        ]);
    }

    #[Route('/admin/guest/deactivate/{id}', name: 'admin_guest_deactivate', methods: ['POST'])]
    public function deactivate(int $id, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $this->validateCsrfToken($request, 'admin_guest_deactivate');

        $user = $userRepository->find($id);
        if ($user) {
            $user->setDeactivationDate(new \DateTimeImmutable());
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_guest_index');
    }

    #[Route('/admin/guest/reactivate/{id}', name: 'admin_guest_reactivate', methods: ['POST'])]
    public function activate(int $id, Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        $this->validateCsrfToken($request, 'admin_guest_reactivate');

        $user = $userRepository->find($id);
        if ($user) {
            $user->setDeactivationDate(null);
            $entityManager->persist($user);
            $entityManager->flush();
        }
        return $this->redirectToRoute('admin_guest_index');
    }

    private function validateCsrfToken(Request $request, string $tokenId): void
    {
        $token = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new \Symfony\Component\Security\Csrf\CsrfToken($tokenId, $token))) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}

