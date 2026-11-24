<?php

namespace App\Tests\Functional;

use App\Entity\Media;
use App\Entity\User;
use App\Entity\Album;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MediaAddTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private array $createdMediaPaths = [];
    private array $createdEmails = [];

    public function testGuestCanAddAMedia(): void
    {
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser(
            'media-guest@example.com',
            'Guest@12345!',
            ['ROLE_USER']
        );

        $imagePath = $this->createValidPng();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/admin/media/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Sunset Test',
        ]);

        $form['media[file]']->upload($imagePath);
        $client->submit($form);

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
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $admin = $this->createUser(
            'media-admin@example.com',
            'Admin@12345!',
            ['ROLE_ADMIN']
        );

        $album = new Album();
        $album->setName('Admin Album');
        $this->entityManager->persist($album);
        $this->entityManager->flush();

        $imagePath = $this->createValidPng();
        $client->loginUser($admin);

        $crawler = $client->request('GET', '/admin/media/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Admin Sunset Test',
        ]);

        $form['media[file]']->upload($imagePath);

        $form['media[user]']->select($admin->getId());
        $form['media[album]']->select($album->getId());
        $client->submit($form);

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
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser(
            'invalid-media-guest@example.com',
            'Guest@12345!',
            ['ROLE_USER']
        );

        $textPath = tempnam(sys_get_temp_dir(), 'invalid-media');
        self::assertNotFalse($textPath);
        file_put_contents($textPath, 'This is not an image');

        $client->loginUser($user);

        $crawler = $client->request('GET', '/admin/media/add');
        $form = $crawler->selectButton('Ajouter')->form([
            'media[title]' => 'Invalid Image',
        ]);

        $form['media[file]']->upload($textPath);
        $client->submit($form);

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

            $albums = $this->entityManager
                ->getRepository(Album::class)
                ->findBy(['name' => 'Admin Album']);

            foreach ($albums as $album) {
                $this->entityManager->remove($album);
            }

            $medias = $this->entityManager
                ->getRepository(Media::class)
                ->findBy(['title' => ['Sunset Test', 'Invalid Image']]);

            foreach ($medias as $media) {
                $this->entityManager->remove($media);
            }

            foreach ($this->createdEmails as $email) {
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($user !== null) {
                    $this->entityManager->remove($user);
                }
            }

            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    private function createUser(string $email, string $plainPassword, array $roles): User
    {
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser !== null) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new User();
        $user->setName('Media Test User');
        $user->setEmail($email);
        $user->setDescription('User created for media functional tests');
        $user->setRoles($roles);
        $user->setFirstConnection(false);
        $user->setDeactivationDate(null);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->createdEmails[] = $email;

        return $user;
    }

    private function createValidPng(): string
    {
        if (!function_exists('imagecreatetruecolor')) {
            self::markTestSkipped('La bibliothèque GD est requise pour créer une image PNG valide.');
        }

        $image = imagecreatetruecolor(1080, 1080);
        $background = imagecolorallocate($image, 30, 90, 150);
        imagefill($image, 0, 0, $background);

        $path = tempnam(sys_get_temp_dir(), 'valid-media');
        self::assertNotFalse($path);
        imagepng($image, $path);

        return $path;
    }
}
