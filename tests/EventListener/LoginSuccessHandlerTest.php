<?php

namespace App\Tests\Security;

use App\Entity\User;
use App\EventListener\LoginSuccessHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginSuccessHandlerTest extends TestCase
{
    public function testGuestUsingFirstConnectionIsRedirectedToPasswordChange(): void
    {
        $user = new User();
        $user->setFirstConnection(true);
        $user->setRoles(['ROLE_USER']);

        $router = $this->createRouterMock('app_change_password', '/change-password');
        $handler = new LoginSuccessHandler($router);
        $token = $this->createTokenMock($user);

        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/change-password', $response->getTargetUrl());
    }

    public function testGuestNotUsingFirstConnectionIsRedirectedToMediaIndex(): void
    {
        $user = new User();
        $user->setFirstConnection(false);
        $user->setRoles(['ROLE_USER']);

        $router = $this->createRouterMock('admin_media_index', '/admin/media');
        $handler = new LoginSuccessHandler($router);
        $token = $this->createTokenMock($user);

        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertSame('/admin/media', $response->getTargetUrl());
    }

    public function testAdminUsingFirstConnectionIsRedirectedToMediaIndex(): void
    {
        $user = new User();
        $user->setFirstConnection(true);
        $user->setRoles(['ROLE_ADMIN']);

        $router = $this->createRouterMock('admin_media_index', '/admin/media');
        $handler = new LoginSuccessHandler($router);
        $token = $this->createTokenMock($user);

        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertSame('/admin/media', $response->getTargetUrl());
    }

    public function testUserNotMatchingAppUserEntityIsRedirectedToMediaIndex(): void
    {
        $foreignUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $router = $this->createRouterMock('admin_media_index', '/admin/media');
        $handler = new LoginSuccessHandler($router);
        $token = $this->createTokenMock($foreignUser);

        $response = $handler->onAuthenticationSuccess(new Request(), $token);

        $this->assertSame('/admin/media', $response->getTargetUrl());
    }

    private function createRouterMock(string $routeName, string $url): RouterInterface
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with($routeName)
            ->willReturn($url);

        return $router;
    }

    private function createTokenMock(\Symfony\Component\Security\Core\User\UserInterface $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        return $token;
    }
}
