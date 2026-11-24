<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testActiveUserIsAllowedBeforeAuthentication(): void
    {
        $user = new User();
        $user->setDeactivationDate(null);

        $this->expectNotToPerformAssertions();
        $this->checker->checkPreAuth($user);
    }

    public function testDeactivatedUserIsRejectedBeforeAuthentication(): void
    {
        $user = new User();
        $user->setDeactivationDate(new \DateTimeImmutable('2024-01-01'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Ce compte est désactivé. Contactez l\'administrateur.');

        $this->checker->checkPreAuth($user);
    }

    public function testActiveUserIsAllowedAfterAuthentication(): void
    {
        $user = new User();
        $user->setDeactivationDate(null);

        $this->expectNotToPerformAssertions();
        $this->checker->checkPostAuth($user);
    }

    public function testDeactivatedUserIsRejectedAfterAuthentication(): void
    {
        $user = new User();
        $user->setDeactivationDate(new \DateTimeImmutable('2024-01-01'));

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Ce compte est désactivé. Contactez l\'administrateur.');

        $this->checker->checkPostAuth($user);
    }

    public function testForeignUserInterfaceIsIgnored(): void
    {
        $foreignUser = $this->createMock(UserInterface::class);

        $this->expectNotToPerformAssertions();
        $this->checker->checkPreAuth($foreignUser);
        $this->checker->checkPostAuth($foreignUser);
    }
}
