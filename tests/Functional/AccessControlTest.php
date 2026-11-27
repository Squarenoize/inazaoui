<?php

namespace App\Tests\Functional;

use App\Entity\User;

class AccessControlTest extends FunctionalTestCase
{
    public function testHomePageIsAccessible(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/guests"]');
        self::assertSelectorExists('a[href="/portfolio"]');
        self::assertSelectorExists('a[href="/about"]');
        self::assertSelectorTextContains('.home-title', 'Photographe');
    }

    public function testGuestsPageIsAccessible(): void
    {
        $this->client->request('GET', '/guests');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Invités');
    }

    public function testLoginPageIsAccessible(): void
    {
        $crawler = $this->client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="_username"]');
        self::assertSelectorExists('input[name="_password"]');
        self::assertSelectorExists('input[name="_csrf_token"]');
        self::assertGreaterThan(0, $crawler->filter('form')->count());
    }

    public function testGuestPageIsAccessible(): void
    {
        $guest = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'john@example.com']);
        self::assertInstanceOf(User::class, $guest);

        $crawler = $this->client->request('GET', '/guests');
        $this->client->click($crawler->filter('a[href="/guest/' . $guest->getId() . '"]')->link());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'John Doe');
    }

    public function testPortfolioPageIsAccessible(): void
    {
        $this->client->request('GET', '/portfolio');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Portfolio');
    }

    public function  testAboutPageIsAccessible(): void
    {
        $this->client->request('GET', '/about');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Qui suis-je ?');
    }

    /**
     * @dataProvider restrictedRouteProvider
     */
    public function testAnonymousUserIsRedirectedFromRestrictedRoutes(string $route): void
    {
        $this->client->request('GET', $route);

        self::assertResponseRedirects('/login');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function restrictedRouteProvider(): iterable
    {
        yield 'password change' => ['/change-password'];
        yield 'media administration' => ['/admin/media'];
        yield 'album administration' => ['/admin/album'];
        yield 'guest administration' => ['/admin/guest'];
    }
}
