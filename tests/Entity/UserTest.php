<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserIdentifierUsesEmail(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertSame('test@example.com', $user->getUserIdentifier());
    }

    public function testNewUserHasUserRole(): void
    {
        $user = new User();

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testSetRolesKeepsAdminRoleAndAddsUserRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testUserRoleIsNotDuplicated(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_USER']);

        $this->assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUserPropertiesCanBeUpdated(): void
    {
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john@example.com');
        $user->setDescription('Utilisateur de test');
        $user->setPassword('hashed-password');

        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame('Utilisateur de test', $user->getDescription());
        $this->assertSame('hashed-password', $user->getPassword());
    }

    public function testDeactivationDateCanBeSetAndCleared(): void
    {
        $user = new User();
        $deactivationDate = new \DateTimeImmutable('2024-01-01');

        $user->setDeactivationDate($deactivationDate);
        $this->assertSame($deactivationDate, $user->getDeactivationDate());

        $user->setDeactivationDate(null);
        $this->assertNull($user->getDeactivationDate());
    }

    public function testFirstConnectionCanBeUpdated(): void
    {
        $user = new User();

        $user->setFirstConnection(true);
        $this->assertTrue($user->isFirstConnection());

        $user->setFirstConnection(false);
        $this->assertFalse($user->isFirstConnection());
    }

    public function testNewUserStartsWithAnEmptyMediaCollection(): void
    {
        $user = new User();

        $this->assertCount(0, $user->getMedias());
    }
}