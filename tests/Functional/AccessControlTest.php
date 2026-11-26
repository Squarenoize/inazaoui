<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class AccessControlTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private ?User $createdGuest = null;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \LogicException('Entity manager service is unavailable.');
        }
        $this->entityManager = $entityManager;
    }

    protected function tearDown(): void
    {
        if ($this->createdGuest !== null) {
            $guest = $this->entityManager
                ->getRepository(User::class)
                ->find($this->createdGuest->getId());

            if ($guest !== null) {
                $this->entityManager->remove($guest);
                $this->entityManager->flush();
            }
        }

        parent::tearDown();
    }

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
        $existingGuest = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'guest-crawler@example.com']);

        if ($existingGuest !== null) {
            $this->entityManager->remove($existingGuest);
            $this->entityManager->flush();
        }

        $guest = new User();

        $guest->setName('Invité Test');
        $guest->setEmail('guest-crawler@example.com');
        $guest->setDescription('Description visible');
        $guest->setRoles(['ROLE_USER']);
        $guest->setFirstConnection(false);
        $guest->setPassword('hashed-password');

        $this->entityManager->persist($guest);
        $this->entityManager->flush();
        $this->createdGuest = $guest;

        $crawler = $this->client->request('GET', '/guests');
        $this->client->click($crawler->filter('a[href="/guest/' . $guest->getId() . '"]')->link());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Invité Test');
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
