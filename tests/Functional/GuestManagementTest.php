<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GuestManagementTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    /** @var list<string> */
    private array $createdEmails = [];

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testAdminCanAddGuest(): void
    {
        $admin = $this->createUser(
            'guest-admin@example.com',
            'Admin@12345!',
            ['ROLE_ADMIN']
        );
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/guest/add');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Ajouter')->form([
            'user[name]' => 'Invité Fonctionnel',
            'user[description]' => 'Compte créé par un test',
            'user[email]' => 'new-functional-guest@example.com',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/admin/guest');

        $guest = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'new-functional-guest@example.com']);

        self::assertNotNull($guest);
        self::assertSame('Invité Fonctionnel', $guest->getName());
        self::assertContains('ROLE_USER', $guest->getRoles());
        self::assertTrue($guest->isFirstConnection());
        self::assertNull($guest->getDeactivationDate());
    }

    public function testAdminCanDeactivateReactivateAndDeleteGuest(): void
    {
        $admin = $this->createUser(
            'guest-actions-admin@example.com',
            'Admin@12345!',
            ['ROLE_ADMIN']
        );
        $guest = $this->createUser(
            'guest-actions@example.com',
            'Guest@12345!',
            ['ROLE_USER']
        );
        $this->client->loginUser($admin);

        $crawler = $this->client->request('GET', '/admin/guest');
        self::assertResponseIsSuccessful();

        $deactivateForm = $crawler
            ->filter(sprintf('form[action="/admin/guest/deactivate/%d"]', $guest->getId()))
            ->form();
        $this->client->submit($deactivateForm);

        self::assertResponseRedirects('/admin/guest');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $deactivatedGuest = $this->entityManager
            ->getRepository(User::class)
            ->find($guest->getId());
        self::assertNotNull($deactivatedGuest?->getDeactivationDate());

        $crawler = $this->client->followRedirect();
        $reactivateForm = $crawler
            ->filter(sprintf('form[action="/admin/guest/reactivate/%d"]', $guest->getId()))
            ->form();
        $this->client->submit($reactivateForm);

        self::assertResponseRedirects('/admin/guest');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $reactivatedGuest = $this->entityManager
            ->getRepository(User::class)
            ->find($guest->getId());
        self::assertNull($reactivatedGuest?->getDeactivationDate());

        $crawler = $this->client->followRedirect();
        $deleteForm = $crawler
            ->filter(sprintf('form[action="/admin/guest/delete/%d"]', $guest->getId()))
            ->form();
        $this->client->submit($deleteForm);

        self::assertResponseRedirects('/admin/guest');
        $deletedGuest = $this->entityManager
            ->getRepository(User::class)
            ->find($guest->getId());
        self::assertNull($deletedGuest);
    }

    protected function tearDown(): void
    {
        foreach (array_unique($this->createdEmails) as $email) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if ($user !== null) {
                $this->entityManager->remove($user);
            }
        }

        $addedGuest = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'new-functional-guest@example.com']);

        if ($addedGuest !== null) {
            $this->entityManager->remove($addedGuest);
        }

        $this->entityManager->flush();
        parent::tearDown();
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(string $email, string $plainPassword, array $roles): User
    {
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser !== null) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User();
        $user->setName('Guest Test User');
        $user->setEmail($email);
        $user->setDescription('User created for guest functional tests');
        $user->setRoles($roles);
        $user->setFirstConnection(false);
        $user->setDeactivationDate(null);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->createdEmails[] = $email;

        return $user;
    }
}
