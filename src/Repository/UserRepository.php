<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[] Returns an array of all Users objects with the given role for the admin
     */
    public function findByRoles(string $role): array
    {
        $em = $this->getEntityManager();
        $ids = $em->getConnection()->executeQuery(
            'SELECT id FROM "user" WHERE CAST(roles AS TEXT) LIKE :role',
            ['role' => '%' . $role . '%']
        )->fetchFirstColumn();
        
        if (empty($ids)) {
            return [];
        }
        
        return $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[] Returns an array of User objects active with the given role
     */
    public function findActivesByRoles(string $role): array
    {
        $em = $this->getEntityManager();
        $ids = $em->getConnection()->executeQuery(
            'SELECT id FROM "user" WHERE CAST(roles AS TEXT) LIKE :role and deactivation_date IS NULL',
            ['role' => '%' . $role . '%']
        )->fetchFirstColumn();
        
        if (empty($ids)) {
            return [];
        }
        
        return $this->createQueryBuilder('u')
            ->leftJoin('u.medias', 'm')
            ->addSelect('m')
            ->where('u.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find the admin user (user with ROLE_ADMIN)
     */
    public function findOneByAdmin(): ?User
    {
        $em = $this->getEntityManager();
        $result = $em->getConnection()->executeQuery(
            'SELECT id FROM "user" WHERE CAST(roles AS TEXT) LIKE :admin LIMIT 1',
            ['admin' => '%ROLE_ADMIN%']
        )->fetchOne();
        
        return $result ? $this->find($result) : null;
    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
