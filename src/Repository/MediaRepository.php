<?php

namespace App\Repository;

use App\Entity\Media;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Media>
 *
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    public function findPaginatedByUser(?User $user = null, int $page = 1, int $limit = 25): array
    {
        $qb = $this->createQueryBuilder('m')
            ->orderBy('m.album', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        if ($user !== null) {
            $qb->where('m.user = :user')
               ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function countByUser(?User $user = null): int
    {
        $qb = $this->createQueryBuilder('m');

        if ($user !== null) {
            $qb->where('m.user = :user')
               ->setParameter('user', $user);
        }

        return count($qb->getQuery()->getResult());
    }
}
