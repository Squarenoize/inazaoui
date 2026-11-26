<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{

    #[Route('/admin/media', name: 'admin_media_index', methods: ['GET'])]
    public function index(Request $request, MediaRepository $mediaRepository): Response
    {
        $page = $request->query->getInt('page', 1);
        $user = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();

        $medias = $mediaRepository->findPaginatedByUser($user, $page);
        $total = $mediaRepository->countByUser($user);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
            'user' => $user,
        ]);
    }

    #[Route('/admin/media/add', name: 'admin_media_add', methods: ['POST', 'GET'])]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($this->getUser());
            }
            $filename = md5(uniqid()) . '.' . $media->getFile()->guessExtension();
            $media->setPath('uploads/' . $filename);
            $media->getFile()->move('uploads/', $filename);
            $entityManager->persist($media);
            $entityManager->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete', methods: ['POST'])]
    public function delete(Media $media, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($media);
        $entityManager->flush();

        return $this->redirectToRoute('admin_media_index');
    }
}