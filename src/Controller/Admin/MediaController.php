<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class MediaController extends AbstractController
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[Route('/admin/media', name: 'admin_media_index')]
    public function index(Request $request)
    {
        $page = $request->query->getInt('page', 1);
        $user = $this->isGranted('ROLE_ADMIN') ? null : $this->getUser();

        $medias = $this->doctrine->getRepository(Media::class)->findPaginatedByUser($user, $page);
        $total = $this->doctrine->getRepository(Media::class)->countByUser($user);

        return $this->render('admin/media/index.html.twig', [
            'medias' => $medias,
            'total' => $total,
            'page' => $page,
            'user' => $user,
        ]);
    }

    #[Route('/admin/media/add', name: 'admin_media_add', methods: ['POST', 'GET'])]
    public function add(Request $request)
    {
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
            $em = $this->doctrine->getManager();
            $em->persist($media);
            $em->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/admin/media/delete/{id}', name: 'admin_media_delete')]
    public function delete(int $id)
    {
        $media = $this->doctrine->getRepository(Media::class)->find($id);
        $em = $this->doctrine->getManager();
        $em->remove($media);
        $em->flush();
        unlink($media->getPath());

        return $this->redirectToRoute('admin_media_index');
    }
}