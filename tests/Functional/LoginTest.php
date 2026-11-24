<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginTest extends WebTestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private array $createdEmails = [];

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager !== null) {
            foreach ($this->createdEmails as $email) {
                $user = $this->entityManager
                    ->getRepository(User::class)
                    ->findOneBy(['email' => $email]);

                if ($user !== null) {
                    $this->entityManager->remove($user);
                }
            }

            $this->entityManager->flush();
        }

        parent::tearDown();
    }

    public function testAdminCanLogIn(): void
    {
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->createUser(
            'admin-functional@example.com',
            'Admin@12345!',
            ['ROLE_ADMIN'],
            false
        );

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'admin-functional@example.com',
            '_password' => 'Admin@12345!',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/admin/media');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testGuestUsingFirstConnectionIsRedirectedToPasswordChange(): void
    {
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->createUser(
            'guest-functional@example.com',
            'Guest@12345!',
            ['ROLE_USER'],
            true
        );

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'guest-functional@example.com',
            '_password' => 'Guest@12345!',
        ]);

        $client->submit($form);

        self::assertResponseRedirects('/change-password');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form');
    }

    public function testInvalidCredentialsDisplayAnError(): void
    {
        $client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Connexion')->form([
            '_username' => 'unknown@example.com',
            '_password' => 'WrongPassword!',
        ]);

        $client->submit($form);

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
    }

    private function createUser(
        string $email,
        string $plainPassword,
        array $roles,
        bool $firstConnection
    ): User {
        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $email]);

        if ($existingUser !== null) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        $user = new User();
        $user->setName('Functional Test User');
        $user->setEmail($email);
        $user->setDescription('User created for functional tests');
        $user->setRoles($roles);
        $user->setFirstConnection($firstConnection);
        $user->setDeactivationDate(null);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        $this->createdEmails[] = $email;

        return $user;
    }
}
