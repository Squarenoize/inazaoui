<?php

namespace App\Tests\EventListener;

use App\Entity\Media;
use App\EventListener\MediaDeleteListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use PHPUnit\Framework\TestCase;

class MediaDeleteListenerTest extends TestCase
{
    public function testExistingMediaFileIsDeletedBeforeMediaRemoval(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inazaoui-media-listener';
        mkdir($directory, 0777, true);

        $path = $directory . DIRECTORY_SEPARATOR . 'photo.jpg';
        file_put_contents($path, 'test');

        $media = new Media();
        $media->setPath($path);

        $listener = new MediaDeleteListener();
        $listener->preRemove($this->createPreRemoveEventArgs($media));

        $this->assertFileDoesNotExist($path);

        rmdir($directory);
    }

    public function testMissingMediaFileDoesNotCauseAnError(): void
    {
        $media = new Media();
        $media->setPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'missing-media-' . uniqid() . '.jpg');

        $listener = new MediaDeleteListener();
        $listener->preRemove($this->createPreRemoveEventArgs($media));

        $this->addToAssertionCount(1);
    }

    public function testEntityOtherThanMediaIsIgnored(): void
    {
        $listener = new MediaDeleteListener();
        $listener->preRemove($this->createPreRemoveEventArgs(new \stdClass()));

        $this->addToAssertionCount(1);
    }

    private function createPreRemoveEventArgs(object $entity): PreRemoveEventArgs
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        return new PreRemoveEventArgs($entity, $entityManager);
    }
}
