<?php

namespace App\EventListener;

use App\Entity\Media;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;

#[AsDoctrineListener(event: Events::preRemove)]
final class MediaDeleteListener
{
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Media) {
            return;
        }

        $path = $entity->getPath();
        if ($path && file_exists($path)) {
            unlink($path);
        }
    }
}
