<?php

namespace App\Tests\Functional;

use App\Entity\Media;
use App\Entity\User;
use App\Entity\Album;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Field\FileFormField;

class MediaAddTest extends FunctionalTestCase
{
    /** @var list<string> */
    private array $createdMediaPaths = [];
    /** @var list<string> */
    private array $createdUploadPaths = [];

    public function testGuestCanAddAMedia(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'john@example.com']);
        self::assertInstanceOf(User::class, $user);

        $imagePath = $this->createValidPng();
        $this->createdUploadPaths[] = $imagePath;
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/media/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Sunset Test',
        ]);

        $fileField = $form->get('media[file]');
        self::assertInstanceOf(FileFormField::class, $fileField);
        $fileField->upload($imagePath);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/media');

        $media = $this->entityManager
            ->getRepository(Media::class)
            ->findOneBy(['title' => 'Sunset Test']);

        self::assertNotNull($media);
        self::assertSame($user->getId(), $media->getUser()?->getId());
        self::assertSame('Sunset Test', $media->getTitle());
        self::assertNull($media->getAlbum());
        self::assertFileExists($media->getPath());

        $this->createdMediaPaths[] = $media->getPath();
    }

    public function testAdminCanAddAMediaWithAlbum(): void
    {
        $admin = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'admin@example.com']);
        self::assertInstanceOf(User::class, $admin);

        $album = $this->entityManager
            ->getRepository(Album::class)
            ->findOneBy(['name' => 'Nature']);
        self::assertInstanceOf(Album::class, $album);

        $imagePath = $this->createValidPng();
        $this->createdUploadPaths[] = $imagePath;
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/media/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Admin Sunset Test',
        ]);

        $fileField = $form->get('media[file]');
        self::assertInstanceOf(FileFormField::class, $fileField);
        $fileField->upload($imagePath);

        $userField = $form->get('media[user]');
        self::assertInstanceOf(ChoiceFormField::class, $userField);
        $userField->select((string) $admin->getId());

        $albumField = $form->get('media[album]');
        self::assertInstanceOf(ChoiceFormField::class, $albumField);
        $albumField->select((string) $album->getId());
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/media');

        $media = $this->entityManager
            ->getRepository(Media::class)
            ->findOneBy(['title' => 'Admin Sunset Test']);

        self::assertNotNull($media);
        self::assertSame($admin->getId(), $media->getUser()?->getId());
        self::assertSame('Admin Sunset Test', $media->getTitle());
        self::assertSame($album->getId(), $media->getAlbum()?->getId());
        self::assertFileExists($media->getPath());

        $this->createdMediaPaths[] = $media->getPath();
    }

    public function testInvalidFileIsRejected(): void
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'john@example.com']);
        self::assertInstanceOf(User::class, $user);

        $textPath = tempnam(sys_get_temp_dir(), 'invalid-media');
        self::assertNotFalse($textPath);
        file_put_contents($textPath, 'This is not an image');

        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Invalid Image',
        ]);

        $fileField = $form->get('media[file]');
        self::assertInstanceOf(FileFormField::class, $fileField);
        $fileField->upload($textPath);
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.invalid-feedback');

        $media = $this->entityManager
            ->getRepository(Media::class)
            ->findOneBy(['title' => 'Invalid Image']);

        self::assertNull($media);

        unlink($textPath);
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            foreach ($this->createdMediaPaths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }

            foreach ($this->createdUploadPaths as $path) {
                if (file_exists($path)) {
                    unlink($path);
                }
            }
        }

        parent::tearDown();
    }

    private function createValidPng(): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('La bibliothèque GD est requise pour créer une image PNG valide.');
        }

        $image = imagecreatetruecolor(1080, 1080);
        $background = imagecolorallocate($image, 30, 90, 150);
        if ($background === false) {
            self::markTestSkipped('Impossible d’allouer une couleur pour l’image PNG.');
        }
        imagefill($image, 0, 0, $background);

        $path = tempnam(sys_get_temp_dir(), 'valid-media');
        self::assertNotFalse($path);
        imagepng($image, $path);

        return $path;
    }
}
