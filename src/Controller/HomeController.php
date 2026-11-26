<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('front/home.html.twig');
    }

    #[Route('/guests', name: 'guests', methods: ['GET'])]
    public function guests(UserRepository $User): Response
    {
        $guests = $User->findActivesByRoles('ROLE_USER');
        return $this->render('front/guests.html.twig', [
            'guests' => $guests
        ]);
    }

    #[Route('/guest/{id}', name: 'guest', methods: ['GET'])]
    public function guest(User $guest): Response
    {
        return $this->render('front/guest.html.twig', [
            'guest' => $guest
        ]);
    }

    #[Route('/portfolio/{id}', name: 'portfolio', defaults: ['id' => null], requirements: ['id' => '\d+'], methods: ['GET'])]
    public function portfolio(AlbumRepository $albumRepository, UserRepository $userRepository, MediaRepository $mediaRepository, ?int $id = null): Response
    {
        $albums = $albumRepository->findAll();
        $user = $userRepository->findOneByAdmin();
        $album = $id !== null
            ? $albumRepository->find($id)
            : null;
        
        $medias = $album
            ? $mediaRepository->findByAlbum($album)
            : $mediaRepository->findByUser($user);
        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias
        ]);
    }

    #[Route('/about', name: 'about', methods: ['GET'])]
    public function about(): Response
    {
        return $this->render('front/about.html.twig');
    }
}