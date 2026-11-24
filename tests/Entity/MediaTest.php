<?php

namespace App\Tests\Entity;

use App\Entity\Album;
use App\Entity\Media;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaTest extends TestCase
{
    public function testMediaPropertiesCanBeUpdated(): void
    {
        $media = new Media();
        $media->setTitle('Beach Sunset');
        $media->setPath('uploads/media/beach-sunset.jpg');

        $this->assertSame('Beach Sunset', $media->getTitle());
        $this->assertSame('uploads/media/beach-sunset.jpg', $media->getPath());
        $this->assertNull($media->getId());
    }

    public function testMediaCanBeAssociatedWithAUser(): void
    {
        $media = new Media();
        $user = new User();
        $user->setName('John Doe');

        $media->setUser($user);

        $this->assertSame($user, $media->getUser());
    }

    public function testMediaUserCanBeRemoved(): void
    {
        $media = new Media();
        $media->setUser(new User());
        $media->setUser(null);

        $this->assertNull($media->getUser());
    }

    public function testMediaCanBeAssociatedWithAnAlbum(): void
    {
        $media = new Media();
        $album = new Album();
        $album->setName('Vacances 2024');

        $media->setAlbum($album);

        $this->assertSame($album, $media->getAlbum());
        $this->assertSame('Vacances 2024', $media->getAlbum()->getName());
    }

    public function testMediaCanBeWithoutAlbum(): void
    {
        $media = new Media();
        $media->setAlbum(null);

        $this->assertNull($media->getAlbum());
    }

    public function testUploadedFileCanBeAssociatedWithMedia(): void
    {
        $media = new Media();
        $temporaryFile = tempnam(sys_get_temp_dir(), 'media-test');

        $this->assertNotFalse($temporaryFile);

        $file = new UploadedFile($temporaryFile, 'photo.jpg', 'image/jpeg', null, true);
        $media->setFile($file);

        $this->assertSame($file, $media->getFile());

        unlink($temporaryFile);
    }

    public function testMediaCanBeWithoutUploadedFile(): void
    {
        $media = new Media();
        $media->setFile(null);

        $this->assertNull($media->getFile());
    }
}
